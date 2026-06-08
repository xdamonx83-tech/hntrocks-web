<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupTeam;
use App\Models\CupTeamFinderPost;
use App\Models\FeedPost;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CupTeamController extends Controller
{

    public function index(Request $request, Cup $cup): View|RedirectResponse
    {
        if ($cup->isSoloLeaderboard()) {
            return redirect()->route('cups.show', $cup);
        }

        abort_unless($cup->visibility === 'public' || $cup->isOwner($request->user()) || $cup->canManage($request->user()), 404);

        $cup->load([
            'teams.owner:id,name,username,avatar_path',
            'teams.members.user:id,name,username,avatar_path',
        ]);

        $viewerTeam = $cup->teamFor($request->user());
        $canManage = $cup->canManage($request->user());
        $viewerTeamChatMessages = collect();
        $viewerTeamChatMessagesCount = 0;
        $teamFinderEnabled = Schema::hasTable('cup_team_finder_posts') && Schema::hasColumn('cup_teams', 'is_recruiting');
        $teamFinderPosts = collect();
        $viewerFinderPost = null;
        $recruitingTeams = collect();

        if ($viewerTeam && Schema::hasTable('cup_team_chat_messages')) {
            $viewerTeamChatMessages = $viewerTeam->chatMessages()
                ->with('user:id,name,username,avatar_path,level')
                ->limit(30)
                ->get()
                ->reverse()
                ->values();
            $viewerTeamChatMessagesCount = $viewerTeam->chatMessages()->count();
        }

        if ($teamFinderEnabled) {
            $teamFinderPosts = CupTeamFinderPost::query()
                ->where('cup_id', $cup->id)
                ->where('status', 'active')
                ->with('user:id,name,username,avatar_path')
                ->latest()
                ->get();

            if ($request->user()) {
                $viewerFinderPost = $teamFinderPosts->firstWhere('user_id', $request->user()->id);
            }

            $recruitingTeams = $cup->activeTeams()
                ->where('is_recruiting', true)
                ->with(['owner:id,name,username,avatar_path', 'members.user:id,name,username,avatar_path'])
                ->get()
                ->filter(fn (CupTeam $team): bool => $team->isRecruiting())
                ->values();
        }

        return view(HntTheme::resolve('cups.teams'), compact('cup', 'viewerTeam', 'canManage', 'viewerTeamChatMessages', 'viewerTeamChatMessagesCount', 'teamFinderEnabled', 'teamFinderPosts', 'viewerFinderPost', 'recruitingTeams'));
    }

    public function store(Request $request, Cup $cup, GamificationService $gamification, NotificationService $notifications): RedirectResponse
    {
        if (! $cup->isRegistrationOpen()) {
            return back()->withErrors(['team' => __('ui.cup_team_error_registration_closed')]);
        }

        $cup->loadMissing('teams.members');
        if ($cup->teamFor($request->user())) {
            return back()->withErrors(['team' => $cup->isSoloLeaderboard() ? __('ui.cup_team_error_already_registered') : __('ui.cup_team_error_already_in_team')]);
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! $eligibility['eligible']) {
            return back()->withErrors(['team' => implode(' ', $eligibility['messages'])]);
        }

        if ($cup->isSoloLeaderboard()) {
            $request->validate([
                'name' => ['nullable', 'string', 'max:100'],
            ]);

            $displayName = $this->uniqueSoloParticipantName(
                $cup,
                $request->user()->username ?: $request->user()->name ?: __('ui.cup_player_fallback', ['id' => $request->user()->id]),
                (int) $request->user()->id
            );
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
            ]);

            $displayName = trim((string) $validated['name']);
        }

        $team = DB::transaction(function () use ($cup, $request, $displayName): CupTeam {
            $team = CupTeam::create([
                'cup_id' => $cup->id,
                'owner_id' => $request->user()->id,
                'name' => $displayName,
                'status' => 'active',
            ]);

            $team->members()->create([
                'user_id' => $request->user()->id,
                'role' => $cup->isSoloLeaderboard() ? 'participant' : 'captain',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $team;
        });

        $gamification->award($request->user(), 'cup_team_created', source: $team, description: $cup->isSoloLeaderboard() ? __('ui.cup_participant_gamification_created') : __('ui.cup_team_gamification_created'));

        if ($cup->isSoloLeaderboard()) {
            $notifications->send($cup->owner, $request->user(), 'cup_participant_registered', __('ui.cup_participant_notification_created_title'), __('ui.cup_participant_notification_created_body', ['player' => $team->displayName(), 'cup' => $cup->title]), route('cups.show.section', [$cup, 'participants']));
        } else {
            $notifications->send($cup->owner, $request->user(), 'cup_team_created', __('ui.cup_team_notification_created_title'), __('ui.cup_team_notification_created_body', ['team' => $team->name, 'cup' => $cup->title]), route('cups.show.section', [$cup, 'participants']));
            $this->closeTeamFinderPost($cup, $request->user());
            $this->publishTeamCreatedFeedPost($team);
        }

        return redirect()->route($cup->isSoloLeaderboard() ? 'cups.show.section' : 'cups.teams.index', $cup->isSoloLeaderboard() ? [$cup, 'participants'] : $cup)->with('status', $cup->isSoloLeaderboard() ? __('ui.cup_participant_created_status') : __('ui.cup_team_created_status'));
    }

    public function update(Request $request, Cup $cup, CupTeam $team): RedirectResponse
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);
        abort_if($cup->isSoloLeaderboard(), 404);
        abort_unless($team->canManage($request->user()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
        ]);

        $name = trim((string) $validated['name']);
        if ($name === '') {
            return back()->withInput()->withErrors(['name' => __('ui.cup_team_name_required')]);
        }

        $team->update(['name' => $name]);

        return redirect()->route('cups.teams.index', $cup)->with('status', __('ui.cup_team_name_updated_status'));
    }

    public function join(Request $request, Cup $cup, string $token, GamificationService $gamification, NotificationService $notifications): RedirectResponse
    {
        $team = CupTeam::query()
            ->where('cup_id', $cup->id)
            ->where('join_token', $token)
            ->where('status', 'active')
            ->with(['cup', 'members'])
            ->firstOrFail();

        $cup->loadMissing('teams.members');

        if ($cup->isSoloLeaderboard()) {
            return redirect()->route('cups.show', $cup)->withErrors(['join' => __('ui.cup_solo_join_link_disabled')]);
        }

        if (! $cup->isRegistrationOpen()) {
            return redirect()->route('cups.show', $cup)->withErrors(['join' => __('ui.cup_team_error_registration_closed_short')]);
        }

        if ($cup->teamFor($request->user())) {
            return redirect()->route('cups.show', $cup)->withErrors(['join' => __('ui.cup_team_error_already_in_team')]);
        }

        if ($team->isRosterLocked()) {
            return redirect()->route('cups.teams.index', $cup)->withErrors(['join' => __('ui.cup_team_error_roster_locked')]);
        }

        if ($team->slotsOpen() <= 0) {
            return redirect()->route('cups.show', $cup)->withErrors(['join' => __('ui.cup_team_error_full')]);
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! $eligibility['eligible']) {
            return redirect()->route('cups.show', $cup)->withErrors(['join' => implode(' ', $eligibility['messages'])]);
        }

        $team->members()->create([
            'user_id' => $request->user()->id,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $this->closeTeamFinderPost($cup, $request->user());

        $gamification->award($request->user(), 'cup_team_joined', source: $team, description: __('ui.cup_team_gamification_joined'));
        $notifications->send($team->owner, $request->user(), 'cup_team_joined', __('ui.cup_team_notification_joined_title'), __('ui.cup_team_notification_joined_body', ['name' => $request->user()->name]), route('cups.show.section', [$cup, 'participants']));

        return redirect()->route('cups.teams.index', $cup)->with('status', __('ui.cup_team_joined_status'));
    }

    public function storeFinderPost(Request $request, Cup $cup): RedirectResponse
    {
        abort_if($cup->isSoloLeaderboard(), 404);
        abort_unless($cup->visibility === 'public' || $cup->canManage($request->user()), 404);

        if (! Schema::hasTable('cup_team_finder_posts')) {
            return back()->withErrors(['finder' => __('ui.cup_team_finder_not_ready')]);
        }

        if (! $cup->isRegistrationOpen()) {
            return back()->withErrors(['finder' => __('ui.cup_team_error_registration_closed')]);
        }

        $cup->loadMissing('teams.members');
        if ($cup->teamFor($request->user())) {
            return back()->withErrors(['finder' => __('ui.cup_team_finder_error_already_in_team')]);
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! $eligibility['eligible']) {
            return back()->withErrors(['finder' => implode(' ', $eligibility['messages'])]);
        }

        $validated = $request->validate([
            'platform' => ['nullable', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        $platform = trim((string) ($validated['platform'] ?? ''));
        if (! in_array($platform, ['playstation', 'xbox', 'flexible'], true)) {
            $platform = null;
        }

        CupTeamFinderPost::updateOrCreate(
            ['cup_id' => $cup->id, 'user_id' => $request->user()->id],
            [
                'platform' => $platform,
                'message' => trim((string) ($validated['message'] ?? '')) ?: null,
                'status' => 'active',
                'closed_at' => null,
            ]
        );

        return redirect()->route('cups.teams.index', $cup)->with('status', __('ui.cup_team_finder_post_saved'));
    }

    public function closeFinderPost(Request $request, Cup $cup): RedirectResponse
    {
        abort_if($cup->isSoloLeaderboard(), 404);

        if (Schema::hasTable('cup_team_finder_posts')) {
            $this->closeTeamFinderPost($cup, $request->user());
        }

        return redirect()->route('cups.teams.index', $cup)->with('status', __('ui.cup_team_finder_post_closed'));
    }

    public function updateRecruiting(Request $request, Cup $cup, CupTeam $team): RedirectResponse
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);
        abort_if($cup->isSoloLeaderboard(), 404);
        abort_unless($team->canManage($request->user()), 403);

        if (! Schema::hasColumn('cup_teams', 'is_recruiting')) {
            return back()->withErrors(['recruiting' => __('ui.cup_team_finder_not_ready')]);
        }

        $enabled = $request->boolean('is_recruiting');

        if ($enabled && (! $team->canChangeRoster() || $team->slotsOpen() <= 0 || ! $cup->isRegistrationOpen())) {
            return back()->withErrors(['recruiting' => __('ui.cup_team_recruiting_not_available')]);
        }

        $team->forceFill(['is_recruiting' => $enabled])->save();

        return redirect()->route('cups.teams.index', $cup)->with('status', $enabled ? __('ui.cup_team_recruiting_enabled') : __('ui.cup_team_recruiting_disabled'));
    }

    public function leave(Request $request, Cup $cup, CupTeam $team): RedirectResponse
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);

        if ($team->isDisqualified()) {
            return back()->withErrors(['team' => __('ui.cup_team_error_disqualified_locked')]);
        }

        if ($team->isRosterLocked()) {
            return back()->withErrors(['team' => __('ui.cup_team_error_roster_locked')]);
        }

        $member = $team->members()->where('user_id', $request->user()->id)->first();
        if (! $member) {
            return back()->withErrors(['team' => __('ui.cup_team_error_not_member')]);
        }

        if ($team->isOwner($request->user())) {
            $team->update(['status' => 'withdrawn']);
            $team->members()->update(['status' => 'left']);

            return redirect()->route($cup->isSoloLeaderboard() ? 'cups.show.section' : 'cups.teams.index', $cup->isSoloLeaderboard() ? [$cup, 'participants'] : $cup)->with('status', $cup->isSoloLeaderboard() ? __('ui.cup_participant_withdrawn_status') : __('ui.cup_team_withdrawn_status'));
        }

        $member->update(['status' => 'left']);

        return redirect()->route($cup->isSoloLeaderboard() ? 'cups.show.section' : 'cups.teams.index', $cup->isSoloLeaderboard() ? [$cup, 'participants'] : $cup)->with('status', $cup->isSoloLeaderboard() ? __('ui.cup_participant_withdrawn_status') : __('ui.cup_team_left_status'));
    }


    public function disqualify(Request $request, Cup $cup, CupTeam $team): RedirectResponse
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);
        abort_unless($cup->canManage($request->user()), 403);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1200'],
        ]);

        DB::transaction(function () use ($request, $team, $validated): void {
            $lockedTeam = CupTeam::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            $lockedTeam->forceFill([
                'status' => 'disqualified',
                'disqualified_at' => now(),
                'disqualified_by' => $request->user()->id,
                'disqualification_reason' => trim((string) ($validated['reason'] ?? '')) ?: null,
            ])->save();
        });

        return redirect()->route('cups.show.section', [$cup, 'participants'])->with('status', $cup->isSoloLeaderboard() ? __('ui.cup_participant_disqualified_status') : __('ui.cup_team_disqualified_status'));
    }

    public function reinstate(Request $request, Cup $cup, CupTeam $team): RedirectResponse
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);
        abort_unless($cup->canManage($request->user()), 403);

        DB::transaction(function () use ($team): void {
            $lockedTeam = CupTeam::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();

            $lockedTeam->forceFill([
                'status' => 'active',
                'disqualified_at' => null,
                'disqualified_by' => null,
                'disqualification_reason' => null,
            ])->save();
        });

        return redirect()->route('cups.show.section', [$cup, 'participants'])->with('status', $cup->isSoloLeaderboard() ? __('ui.cup_participant_reinstated_status') : __('ui.cup_team_reinstated_status'));
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

    private function publishTeamCreatedFeedPost(CupTeam $team): void
    {
        try {
            $team->loadMissing(['cup', 'owner', 'members']);

            $cup = $team->cup;
            $owner = $team->owner;

            if (! $cup || ! $owner || $cup->isSoloLeaderboard() || $cup->visibility !== 'public') {
                return;
            }

            $teamUrl = route('cups.teams.index', $cup);

            $body = implode("

", array_filter([
                __('ui.cup_team_activity_created_intro', [
                    'team' => $team->displayName(),
                    'cup' => $cup->title,
                ]),
                __('ui.cup_team_activity_created_hint'),
                __('ui.cup_team_activity_created_comment_cta'),
                __('ui.cup_team_activity_created_link', ['url' => $teamUrl]),
            ]));

            $payload = [
                'user_id' => $owner->id,
                'body' => $body,
                'source_language' => app()->getLocale() === 'en' ? 'en' : 'de',
                'visibility' => 'public',
                'status' => 'published',
            ];

            if (Schema::hasColumn('feed_posts', 'cup_team_id')) {
                $payload['cup_team_id'] = $team->id;
            }

            FeedPost::create($payload);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function uniqueSoloParticipantName(Cup $cup, string $displayName, int $userId): string
    {
        $displayName = trim($displayName) !== '' ? trim($displayName) : __('ui.cup_player_fallback', ['id' => $userId]);
        $displayName = Str::limit($displayName, 100, '');

        if (! CupTeam::where('cup_id', $cup->id)->where('name', $displayName)->exists()) {
            return $displayName;
        }

        $suffix = ' #'.$userId;
        $base = Str::limit($displayName, max(1, 100 - strlen($suffix)), '');

        return $base.$suffix;
    }
}
