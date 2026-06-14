<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CupTeamFinderPostResource;
use App\Http\Resources\Api\CupTeamResource;
use App\Models\Cup;
use App\Models\CupTeam;
use App\Models\CupTeamFinderPost;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ApiCupTeamsController extends Controller
{
    public function index(Request $request, Cup $cup): JsonResponse
    {
        $this->authorizeCupAccess($request, $cup);
        $this->loadTeamContext($cup);

        $viewer = $request->user();
        $viewerTeam = $cup->teamFor($viewer);
        $activeTeams = $this->activeTeams($cup);
        $teamFinderEnabled = $this->teamFinderEnabled($cup);
        $eligibility = $cup->participationEligibility($viewer);

        $finderPosts = collect();
        $viewerFinderPost = null;
        $recruitingTeams = collect();

        if ($teamFinderEnabled) {
            $finderPosts = CupTeamFinderPost::query()
                ->where('cup_id', $cup->id)
                ->where('status', 'active')
                ->with('user.profile')
                ->latest()
                ->get();

            $viewerFinderPost = $viewer
                ? $finderPosts->firstWhere('user_id', $viewer->id)
                : null;

            $recruitingTeams = $activeTeams
                ->filter(fn (CupTeam $team): bool => $team->isRecruiting())
                ->values();
        }

        return response()->json([
            'data' => [
                'team_finder_enabled' => $teamFinderEnabled,
                'registration_open' => $cup->isRegistrationOpen(),
                'submission_open' => $cup->isSubmissionOpen(),
                'viewer' => [
                    'registered' => $viewerTeam !== null,
                    'can_register' => $viewerTeam === null && $cup->isRegistrationOpen() && (bool) ($eligibility['eligible'] ?? false),
                    'eligibility' => $eligibility,
                    'team' => $viewerTeam ? new CupTeamResource($viewerTeam) : null,
                    'finder_post' => $viewerFinderPost ? new CupTeamFinderPostResource($viewerFinderPost) : null,
                ],
                'teams' => CupTeamResource::collection($activeTeams),
                'recruiting_teams' => CupTeamResource::collection($recruitingTeams),
                'finder_posts' => CupTeamFinderPostResource::collection($finderPosts),
            ],
        ]);
    }

    public function join(Request $request, Cup $cup, CupTeam $team, GamificationService $gamification, NotificationService $notifications): JsonResponse
    {
        $this->authorizeCupAccess($request, $cup);
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);
        abort_if($cup->isSoloLeaderboard(), 404);

        if (! $cup->isRegistrationOpen()) {
            return $this->error(__('ui.cup_team_error_registration_closed_short'), 422, 'cup');
        }

        $this->loadTeamContext($cup);
        if ($cup->teamFor($request->user())) {
            return $this->error(__('ui.cup_team_error_already_in_team'), 422, 'team');
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! ($eligibility['eligible'] ?? false)) {
            return $this->error(implode(' ', $eligibility['messages'] ?? []), 422, 'team');
        }

        $joinedTeam = DB::transaction(function () use ($request, $cup, $team): CupTeam {
            $lockedTeam = CupTeam::query()
                ->whereKey($team->id)
                ->where('cup_id', $cup->id)
                ->with(['cup', 'members'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTeam->status !== 'active') {
                abort(422, __('ui.cup_team_status_locked'));
            }

            if ($lockedTeam->isRosterLocked()) {
                abort(422, __('ui.cup_team_error_roster_locked'));
            }

            if ($lockedTeam->slotsOpen() <= 0) {
                abort(422, __('ui.cup_team_error_full'));
            }

            $member = $lockedTeam->members()->where('user_id', $request->user()->id)->first();
            if ($member && $member->status === 'active') {
                abort(422, __('ui.cup_team_error_already_in_team'));
            }

            if ($member) {
                $member->update([
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            } else {
                $lockedTeam->members()->create([
                    'user_id' => $request->user()->id,
                    'role' => 'member',
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }

            return $lockedTeam;
        });

        $this->closeTeamFinderPost($cup, $request->user());
        $gamification->award($request->user(), 'cup_team_joined', source: $joinedTeam, description: __('ui.cup_team_gamification_joined'));
        $notifications->send($joinedTeam->owner, $request->user(), 'cup_team_joined', __('ui.cup_team_notification_joined_title'), __('ui.cup_team_notification_joined_body', ['name' => $request->user()->name]), route('cups.show.section', [$cup, 'participants']));

        $joinedTeam = $joinedTeam->fresh(['owner.profile', 'members.user.profile']) ?? $joinedTeam;
        $joinedTeam->setRelation('cup', $cup);

        return response()->json([
            'message' => __('ui.cup_team_joined_status'),
            'registered' => true,
            'team' => new CupTeamResource($joinedTeam),
        ], 201);
    }

    public function storeFinderPost(Request $request, Cup $cup): JsonResponse
    {
        $this->authorizeCupAccess($request, $cup);
        abort_if($cup->isSoloLeaderboard(), 404);

        if (! $this->teamFinderEnabled($cup)) {
            return $this->error(__('ui.cup_team_finder_not_ready'), 503, 'finder');
        }

        if (! $cup->isRegistrationOpen()) {
            return $this->error(__('ui.cup_team_error_registration_closed'), 422, 'finder');
        }

        $this->loadTeamContext($cup);
        if ($cup->teamFor($request->user())) {
            return $this->error(__('ui.cup_team_finder_error_already_in_team'), 422, 'finder');
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! ($eligibility['eligible'] ?? false)) {
            return $this->error(implode(' ', $eligibility['messages'] ?? []), 422, 'finder');
        }

        $validated = $request->validate([
            'platform' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $platform = trim((string) ($validated['platform'] ?? ''));
        if (! in_array($platform, ['playstation', 'xbox', 'flexible'], true)) {
            $platform = null;
        }

        $post = CupTeamFinderPost::updateOrCreate(
            ['cup_id' => $cup->id, 'user_id' => $request->user()->id],
            [
                'platform' => $platform,
                'message' => trim((string) ($validated['message'] ?? '')) ?: null,
                'status' => 'active',
                'closed_at' => null,
            ]
        );
        $post->loadMissing('user.profile');

        return response()->json([
            'message' => __('ui.cup_team_finder_post_saved'),
            'finder_post' => new CupTeamFinderPostResource($post),
        ]);
    }

    public function closeFinderPost(Request $request, Cup $cup): JsonResponse
    {
        $this->authorizeCupAccess($request, $cup);
        abort_if($cup->isSoloLeaderboard(), 404);

        if ($this->teamFinderEnabled($cup)) {
            $this->closeTeamFinderPost($cup, $request->user());
        }

        return response()->json([
            'message' => __('ui.cup_team_finder_post_closed'),
        ]);
    }

    public function updateRecruiting(Request $request, Cup $cup, CupTeam $team): JsonResponse
    {
        $this->authorizeCupAccess($request, $cup);
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);
        abort_if($cup->isSoloLeaderboard(), 404);
        abort_unless($team->canManage($request->user()), 403);

        if (! $this->teamFinderEnabled($cup)) {
            return $this->error(__('ui.cup_team_finder_not_ready'), 503, 'recruiting');
        }

        $team->loadMissing(['cup', 'members']);
        $enabled = $request->boolean('is_recruiting');

        if ($enabled && (! $team->canChangeRoster() || $team->slotsOpen() <= 0 || ! $cup->isRegistrationOpen())) {
            return $this->error(__('ui.cup_team_recruiting_not_available'), 422, 'recruiting');
        }

        $team->forceFill(['is_recruiting' => $enabled])->save();
        $team = $team->fresh(['owner.profile', 'members.user.profile']) ?? $team;
        $team->setRelation('cup', $cup);

        return response()->json([
            'message' => $enabled ? __('ui.cup_team_recruiting_enabled') : __('ui.cup_team_recruiting_disabled'),
            'team' => new CupTeamResource($team),
        ]);
    }

    public function leave(Request $request, Cup $cup, CupTeam $team): JsonResponse
    {
        $this->authorizeCupAccess($request, $cup);
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);

        $team->loadMissing(['cup', 'members']);

        if ($team->isDisqualified()) {
            return $this->error(__('ui.cup_team_error_disqualified_locked'), 422, 'team');
        }

        if ($team->isRosterLocked()) {
            return $this->error(__('ui.cup_team_error_roster_locked'), 422, 'team');
        }

        $member = $team->members()->where('user_id', $request->user()->id)->first();
        if (! $member) {
            return $this->error(__('ui.cup_team_error_not_member'), 422, 'team');
        }

        if ($team->isOwner($request->user())) {
            $team->update(['status' => 'withdrawn', 'is_recruiting' => false]);
            $team->members()->update(['status' => 'left']);

            return response()->json([
                'message' => $cup->isSoloLeaderboard() ? __('ui.cup_participant_withdrawn_status') : __('ui.cup_team_withdrawn_status'),
                'registered' => false,
            ]);
        }

        $member->update(['status' => 'left']);

        return response()->json([
            'message' => __('ui.cup_team_left_status'),
            'registered' => false,
        ]);
    }

    private function authorizeCupAccess(Request $request, Cup $cup): void
    {
        abort_unless($cup->visibility === 'public' || $cup->canManage($request->user()), 404);
    }

    private function loadTeamContext(Cup $cup): void
    {
        $cup->loadMissing([
            'teams.owner.profile',
            'teams.members.user.profile',
        ]);

        $cup->teams->each(fn (CupTeam $team): CupTeam => $team->setRelation('cup', $cup));
    }

    private function activeTeams(Cup $cup)
    {
        return $cup->teams
            ->where('status', 'active')
            ->values();
    }

    private function teamFinderEnabled(Cup $cup): bool
    {
        return ! $cup->isSoloLeaderboard()
            && Schema::hasTable('cup_team_finder_posts')
            && Schema::hasColumn('cup_teams', 'is_recruiting');
    }

    private function closeTeamFinderPost(Cup $cup, ?\App\Models\User $user): void
    {
        if (! $user || ! Schema::hasTable('cup_team_finder_posts')) {
            return;
        }

        CupTeamFinderPost::query()
            ->where('cup_id', $cup->id)
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->get()
            ->each(fn (CupTeamFinderPost $post): mixed => $post->close());
    }

    private function error(string $message, int $status, string $key): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'errors' => [
                $key => [$message],
            ],
        ], $status);
    }
}
