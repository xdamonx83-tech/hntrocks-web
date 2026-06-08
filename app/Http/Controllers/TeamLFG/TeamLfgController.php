<?php

namespace App\Http\Controllers\TeamLFG;

use App\Http\Controllers\Controller;
use App\Support\HntTheme;
use App\Models\Team;
use App\Models\TeamLfgPost;
use App\Services\GamificationService;
use App\Services\MentionService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamLfgController extends Controller
{
    public function index(Request $request): View
    {
        $query = TeamLfgPost::query()
            ->with(['user.profile', 'team.activeMembers'])
            ->withCount([
                'applications as applications_count',
                'pendingApplications as pending_count',
                'acceptedApplications as accepted_count',
            ])
            ->where('status', '!=', 'archived')
            ->where(function ($inner) use ($request): void {
                $inner->where('visibility', 'public')
                    ->orWhere('user_id', $request->user()->id);
            });

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($inner) use ($search): void {
                $inner->where('title', 'like', "%{$search}%")
                    ->orWhere('body', 'like', "%{$search}%")
                    ->orWhereHas('team', fn ($teamQuery) => $teamQuery->where('name', 'like', "%{$search}%"));
            });
        }

        foreach (['type', 'platform', 'playstyle', 'region', 'language', 'preferred_time', 'experience_level', 'status'] as $field) {
            if ($value = $request->query($field)) {
                $query->where($field, $value);
            }
        }

        if ($request->boolean('voice_required')) {
            $query->where('voice_required', true);
        }

        if ($request->boolean('mine')) {
            $query->where('user_id', $request->user()->id);
        }

        $posts = $query->latest()->paginate(12)->withQueryString();

        $view = HntTheme::teamLfgEnabled() && ! $request->boolean('classic_team_lfg')
            ? 'themes.socialite.team-lfg.index'
            : 'team-lfg.index';

        return view($view, [
            'posts' => $posts,
            'filters' => $request->only([
                'q',
                'type',
                'platform',
                'playstyle',
                'region',
                'language',
                'preferred_time',
                'experience_level',
                'status',
                'voice_required',
                'mine',
            ]),
        ]);
    }

    public function create(Request $request): View
    {
        $view = HntTheme::teamLfgEnabled() && ! $request->boolean('classic_team_lfg')
            ? 'themes.socialite.team-lfg.create'
            : 'team-lfg.create';

        return view($view, [
            'manageableTeams' => $this->manageableTeams($request),
        ]);
    }

    public function store(Request $request, GamificationService $gamification, MentionService $mentions, NotificationService $notifications): RedirectResponse
    {
        $validated = $this->validatedTeamLfgData($request, false);

        $team = null;
        if ($validated['type'] === 'team_seeks_players') {
            $team = Team::with('members')->findOrFail($validated['team_id']);
            abort_unless($team->canManage($request->user()), 403);
        }

        $post = TeamLfgPost::create([
            ...$validated,
            'team_id' => $team?->id,
            'user_id' => $request->user()->id,
            'voice_required' => $request->boolean('voice_required'),
            'slots_filled' => 0,
            'slots_total' => $validated['type'] === 'team_seeks_players' ? ($validated['slots_total'] ?? 1) : null,
            'status' => 'open',
        ]);

        $gamification->award($request->user(), 'team_lfg_post_created', source: $post);
        $mentions->syncForTeamLfgPost($post, $request->user(), $post->body, $notifications);

        return redirect()->route('team-lfg.show', $post)->with('status', __('ui.team_lfg_created_status'));
    }

    public function show(Request $request, TeamLfgPost $post): View
    {
        $post->loadMissing([
            'user.profile',
            'team.activeMembers.user.profile',
            'applications.user.profile',
            'applications.team',
        ]);
        $post->loadCount([
            'applications as applications_count',
            'pendingApplications as pending_count',
            'acceptedApplications as accepted_count',
        ]);

        if ($post->visibility === 'private' && ! $post->canManage($request->user())) {
            abort(404);
        }

        $manageableTeams = $this->manageableTeams($request);

        $view = HntTheme::teamLfgEnabled() && ! $request->boolean('classic_team_lfg')
            ? 'themes.socialite.team-lfg.show'
            : 'team-lfg.show';

        return view($view, [
            'post' => $post,
            'viewerApplication' => $post->applicationFor($request->user()),
            'manageableTeams' => $manageableTeams,
            'canManage' => $post->canManage($request->user()),
        ]);
    }

    public function edit(Request $request, TeamLfgPost $post): View
    {
        abort_unless($post->canManage($request->user()), 403);

        $view = HntTheme::teamLfgEnabled() && ! $request->boolean('classic_team_lfg')
            ? 'themes.socialite.team-lfg.edit'
            : 'team-lfg.edit';

        return view($view, [
            'post' => $post,
            'manageableTeams' => $this->manageableTeams($request),
        ]);
    }

    public function update(Request $request, TeamLfgPost $post, MentionService $mentions, NotificationService $notifications): RedirectResponse
    {
        abort_unless($post->canManage($request->user()), 403);

        $validated = $this->validatedTeamLfgData($request, true);

        $team = null;
        if ($validated['type'] === 'team_seeks_players') {
            $team = Team::with('members')->findOrFail($validated['team_id']);
            abort_unless($team->canManage($request->user()), 403);
        }

        $post->fill([
            ...$validated,
            'team_id' => $team?->id,
            'voice_required' => $request->boolean('voice_required'),
            'slots_total' => $validated['type'] === 'team_seeks_players' ? ($validated['slots_total'] ?? 1) : null,
            'slots_filled' => $validated['type'] === 'team_seeks_players' ? ($validated['slots_filled'] ?? 0) : 0,
        ]);

        if ($post->isTeamSeekingPlayers() && (int) $post->slots_filled > (int) $post->slots_total) {
            return back()
                ->withErrors(['slots_filled' => __('ui.team_lfg_error_slots_filled_too_high')])
                ->withInput();
        }

        if ($post->isTeamSeekingPlayers() && $post->status === 'open' && $post->slots_filled >= $post->slots_total) {
            $post->status = 'filled';
        }

        $post->save();
        $mentions->syncForTeamLfgPost($post, $request->user(), $post->body, $notifications);

        return redirect()->route('team-lfg.show', $post)->with('status', __('ui.team_lfg_saved_status'));
    }

    public function destroy(Request $request, TeamLfgPost $post): RedirectResponse
    {
        abort_unless($post->canManage($request->user()), 403);

        $post->update(['status' => 'archived']);
        $post->delete();

        return redirect()->route('team-lfg.index')->with('status', __('ui.team_lfg_archived_status'));
    }

    private function validatedTeamLfgData(Request $request, bool $isUpdate): array
    {
        $rules = [
            'type' => ['required', 'string', 'in:team_seeks_players,player_seeks_team'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            'title' => ['required', 'string', 'max:140'],
            'body' => ['nullable', 'string', 'max:3200'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'preferred_time' => ['nullable', 'string', 'max:80'],
            'experience_level' => ['nullable', 'string', 'max:60'],
            'voice_required' => ['nullable', 'boolean'],
            'slots_total' => ['nullable', 'integer', 'min:1', 'max:50'],
            'visibility' => ['required', 'string', 'in:public,private'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];

        if ($request->input('type') === 'team_seeks_players') {
            $rules['team_id'] = ['required', 'integer', 'exists:teams,id'];
            $rules['slots_total'] = ['required', 'integer', 'min:1', 'max:50'];
        }

        if ($isUpdate) {
            $rules['status'] = ['required', 'string', 'in:open,filled,closed'];
            $rules['slots_filled'] = ['nullable', 'integer', 'min:0', 'max:50'];
        }

        $validated = $request->validate($rules);

        if (($validated['type'] ?? '') === 'player_seeks_team') {
            $validated['team_id'] = null;
        }

        if (blank($validated['title'] ?? null)) {
            $validated['title'] = Str::limit((string) ($validated['body'] ?? 'Team-LFG'), 120);
        }

        return $validated;
    }

    private function manageableTeams(Request $request)
    {
        return Team::query()
            ->whereHas('members', function ($query) use ($request): void {
                $query->where('user_id', $request->user()->id)
                    ->where('status', 'active')
                    ->whereIn('role', ['owner', 'officer']);
            })
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }
}
