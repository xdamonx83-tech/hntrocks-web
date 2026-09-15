<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CupResource;
use App\Models\Cup;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Support\HntTheme;
use App\Support\ReworkFeedSidebar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CupController extends Controller
{
    public function index(Request $request): View
    {
        $viewer = $request->user();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'platform' => (string) $request->query('platform', ''),
            'mine' => $viewer !== null && $request->boolean('mine'),
        ];

        $cups = Cup::query()
            ->withCount(['activeTeams', 'pendingSubmissions'])
            ->with('owner:id,name,username,avatar_path')
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $query->where(function ($subQuery) use ($filters): void {
                    $subQuery->where('title', 'like', '%'.$filters['q'].'%')
                        ->orWhere('summary', 'like', '%'.$filters['q'].'%')
                        ->orWhere('rules', 'like', '%'.$filters['q'].'%');
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['platform'] !== '', function ($query) use ($filters): void {
                $platform = strtolower(str_replace([' ', '-', '_', '/'], '', $filters['platform']));
                $aliases = match ($platform) {
                    'pc', 'steam', 'windows' => ['PC', 'pc', 'Steam', 'Windows'],
                    'ps', 'ps4', 'ps5', 'playstation', 'playstation4', 'playstation5' => ['PlayStation', 'PS5', 'PS4', 'ps5', 'ps4', 'PlayStation / Xbox', 'PS5/Xbox', 'PS5 / Xbox', 'Konsole', 'Console'],
                    'xbox', 'xboxseries', 'xboxseriesx', 'xboxseriess', 'xboxseriesxs' => ['Xbox', 'xbox', 'PlayStation / Xbox', 'PS5/Xbox', 'PS5 / Xbox', 'Konsole', 'Console'],
                    'konsole', 'console' => ['Konsole', 'Console', 'PlayStation', 'PS5', 'PS4', 'Xbox', 'PlayStation / Xbox', 'PS5/Xbox', 'PS5 / Xbox'],
                    default => [$filters['platform']],
                };

                $jsonPlatforms = match ($platform) {
                    'pc', 'steam', 'windows' => ['PC'],
                    'ps', 'ps4', 'ps5', 'playstation', 'playstation4', 'playstation5' => ['PlayStation'],
                    'xbox', 'xboxseries', 'xboxseriesx', 'xboxseriess', 'xboxseriesxs' => ['Xbox'],
                    'konsole', 'console' => ['PlayStation', 'Xbox'],
                    default => [$filters['platform']],
                };

                $query->where(function ($platformQuery) use ($aliases, $jsonPlatforms): void {
                    foreach (array_values(array_unique($aliases)) as $alias) {
                        $platformQuery->orWhere('platform', 'like', '%'.$alias.'%');
                    }

                    foreach (array_values(array_unique($jsonPlatforms)) as $jsonPlatform) {
                        $platformQuery->orWhereJsonContains('settings->platform_gate->allowed_platforms', $jsonPlatform);
                    }
                });
            })
            ->when($filters['mine'] && $viewer !== null, fn ($query) => $query->where('owner_id', $viewer->id))
            ->when(! $viewer?->isAdmin(), function ($query) use ($viewer): void {
                $query->where(function ($visibilityQuery) use ($viewer): void {
                    $visibilityQuery->where('visibility', 'public');

                    if ($viewer !== null) {
                        $visibilityQuery->orWhere('owner_id', $viewer->id);
                    }
                });
            })
            ->latest('starts_at')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $sidebarData = ReworkFeedSidebar::forViewer($viewer);

        return view(HntTheme::resolve('cups.index'), [
            'cups' => $cups,
            'filters' => $filters,
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
        ]);
    }


    public function hallOfFame(): View
    {
        $cups = Cup::query()
            ->visible()
            ->where('status', 'finished')
            ->with([
                'owner:id,name,username,avatar_path,level',
                'teams' => function ($query): void {
                    $query->where('status', 'active')
                        ->with('owner:id,name,username,avatar_path,level')
                        ->orderByDesc('points_total')
                        ->orderByDesc('bounty_tokens_total')
                        ->orderByDesc('kills_total')
                        ->orderByDesc('submissions_approved_count')
                        ->orderBy('id');
                },
            ])
            ->withCount(['activeTeams', 'submissions'])
            ->orderByRaw('COALESCE(ends_at, starts_at, created_at) desc')
            ->latest()
            ->get();

        $hallCups = $cups->map(function (Cup $cup): array {
            $leaderboard = $cup->teams
                ->sortBy([
                    ['points_total', 'desc'],
                    ['bounty_tokens_total', 'desc'],
                    ['kills_total', 'desc'],
                    ['submissions_approved_count', 'desc'],
                    ['id', 'asc'],
                ])
                ->values();

            return [
                'cup' => $cup,
                'topThree' => $leaderboard->take(3)->values(),
                'topFive' => $leaderboard->take(5)->values(),
            ];
        });

        $winnerCount = $hallCups->sum(fn (array $entry): int => $entry['topThree']->count());
        $finalistCount = $hallCups->sum(fn (array $entry): int => $entry['topFive']->count());

        return view(HntTheme::resolve('cups.hall-of-fame'), compact('hallCups', 'winnerCount', 'finalistCount'));
    }

    public function create(Request $request): View
    {
        $this->guardAdmin($request);

        return view(HntTheme::resolve('cups.create'), ['cup' => new Cup()]);
    }

    public function store(Request $request, MediaService $mediaService, GamificationService $gamification): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $this->validatedCupData($request);
        $contentSettings = $this->extractCupContentSettings($validated);
        $rulesSettings = $this->extractCupRulesSettings($validated);

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $request->user(), 'cups/covers');
        }

        unset($validated['cover']);
        $validated['owner_id'] = $request->user()->id;
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['settings'] = array_merge($rulesSettings, [
            'scoring' => 'bounty_first_extract_required',
            'submission_cooldown_minutes' => (int) config('hunthub.cups.submission_cooldown_minutes', 30),
            'mode' => ((int) ($validated['team_size'] ?? 1) <= 1 || strcasecmp((string) ($validated['title'] ?? ''), 'Bayou Blood Cup') === 0) ? 'solo_leaderboard' : 'team_leaderboard',
            'event_key' => strcasecmp((string) ($validated['title'] ?? ''), 'Bayou Blood Cup') === 0 ? 'bayou_blood_cup' : null,
            'content' => $contentSettings,
        ]);

        $cup = Cup::create($validated);

        if ($request->hasFile('cover')) {
            $asset = $mediaService->store($request->file('cover'), $request->user(), 'cups/covers', [
                'visibility' => $cup->visibility,
                'attachable' => $cup,
            ]);
            $cup->update(['cover_path' => $asset->path]);
        }

        $gamification->award($request->user(), 'cup_created', source: $cup, description: __('ui.cup_gamification_created'));

        return redirect()->route('cups.show', $cup)->with('status', __('ui.cup_created_status'));
    }

    public function show(Cup $cup): View
    {
        return $this->renderShow($cup, 'overview');
    }

    public function showSection(Cup $cup, string $section): View
    {
        return $this->renderShow($cup, $section);
    }

    private function renderShow(Cup $cup, string $activeSection = 'overview'): View
    {
        $allowedSections = ['overview', 'rules', 'prizes', 'leaderboard', 'participants', 'submit', 'submissions'];
        $activeSection = in_array($activeSection, $allowedSections, true) ? $activeSection : 'overview';

        $cup->load([
            'owner:id,name,username,avatar_path',
            'teams.owner:id,name,username,avatar_path',
            'teams.members.user:id,name,username,avatar_path',
            'submissions.team',
            'submissions.submitter:id,name,username,avatar_path',
            'submissions.screenshot',
        ]);

        $cupChatMessages = collect();
        $cupChatMessagesCount = 0;
        if (Schema::hasTable('cup_chat_messages')) {
            $cupChatMessages = $cup->chatMessages()
                ->with('user:id,name,username,avatar_path,level')
                ->limit(8)
                ->get()
                ->reverse()
                ->values();
            $cupChatMessagesCount = $cup->chatMessages()->count();
        }

        abort_unless($cup->visibility === 'public' || $cup->isOwner(auth()->user()) || $cup->canManage(auth()->user()), 404);

        $viewerTeam = $cup->teamFor(auth()->user());
        $canManage = $cup->canManage(auth()->user());
        $cupRandomizerDraws = collect();
        $cupRandomizerEligibleTeams = collect();
        if ($canManage && Schema::hasTable('cup_randomizer_draws')) {
            $cupRandomizerDraws = $cup->randomizerDraws()
                ->with(['team.owner:id,name,username,avatar_path', 'drawer:id,name,username,avatar_path'])
                ->limit(8)
                ->get();
            $cupRandomizerEligibleTeams = $cup->randomizerEligibleTeams();
        }
        $viewerTeamChatMessages = collect();
        $viewerTeamChatMessagesCount = 0;
        if ($viewerTeam && ! $cup->isSoloLeaderboard() && Schema::hasTable('cup_team_chat_messages')) {
            $viewerTeamChatMessages = $viewerTeam->chatMessages()
                ->with('user:id,name,username,avatar_path,level')
                ->limit(20)
                ->get()
                ->reverse()
                ->values();
            $viewerTeamChatMessagesCount = $viewerTeam->chatMessages()->count();
        }

        $leaderboard = $cup->teams->where('status', 'active')->sortBy([
            ['points_total', 'desc'],
            ['bounty_tokens_total', 'desc'],
            ['kills_total', 'desc'],
            ['submissions_approved_count', 'desc'],
            ['id', 'asc'],
        ])->values();

        $sidebarData = ReworkFeedSidebar::forViewer(auth()->user());

        return view(HntTheme::resolve('cups.show'), [
            'cup' => $cup,
            'viewerTeam' => $viewerTeam,
            'canManage' => $canManage,
            'leaderboard' => $leaderboard,
            'activeSection' => $activeSection,
            'cupChatMessages' => $cupChatMessages,
            'cupChatMessagesCount' => $cupChatMessagesCount,
            'viewerTeamChatMessages' => $viewerTeamChatMessages,
            'viewerTeamChatMessagesCount' => $viewerTeamChatMessagesCount,
            'cupRandomizerDraws' => $cupRandomizerDraws,
            'cupRandomizerEligibleTeams' => $cupRandomizerEligibleTeams,
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
        ]);
    }

    public function edit(Cup $cup): View
    {
        abort_unless($cup->canManage(auth()->user()), 403);

        return view(HntTheme::resolve('cups.edit'), compact('cup'));
    }

    public function update(Request $request, Cup $cup, MediaService $mediaService): RedirectResponse
    {
        abort_unless($cup->canManage($request->user()), 403);

        $validated = $this->validatedCupData($request, $cup);
        $contentSettings = $this->extractCupContentSettings($validated);
        $rulesSettings = $this->extractCupRulesSettings($validated, $cup);

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $request->user(), 'cups/covers');
        }

        unset($validated['cover']);
        $settings = is_array($cup->settings) ? $cup->settings : [];
        $settings['mode'] = ((int) ($validated['team_size'] ?? $cup->team_size ?? 1) <= 1 || strcasecmp((string) ($validated['title'] ?? $cup->title ?? ''), 'Bayou Blood Cup') === 0) ? 'solo_leaderboard' : 'team_leaderboard';
        $settings['event_key'] = strcasecmp((string) ($validated['title'] ?? $cup->title ?? ''), 'Bayou Blood Cup') === 0 ? 'bayou_blood_cup' : ($settings['event_key'] ?? null);
        $settings['scoring'] = 'bounty_first_extract_required';
        $settings['content'] = $contentSettings;
        $validated['settings'] = array_merge($settings, $rulesSettings);

        $cup->update($validated);

        if ($request->hasFile('cover')) {
            $asset = $mediaService->store($request->file('cover'), $request->user(), 'cups/covers', [
                'visibility' => $cup->visibility,
                'attachable' => $cup,
            ]);
            $cup->update(['cover_path' => $asset->path]);
        }

        return redirect()->route('cups.show', $cup)->with('status', __('ui.cup_saved_status'));
    }

    public function destroy(Request $request, Cup $cup): RedirectResponse
    {
        abort_unless($cup->canManage($request->user()), 403);

        $cup->update(['status' => 'archived']);
        $cup->delete();

        return redirect()->route('cups.index')->with('status', __('ui.cup_archived_status'));
    }


    public function apiOptions(Request $request): JsonResponse
    {
        $this->guardAdmin($request);

        return response()->json([
            'can_create' => true,
            'rules_presets' => Cup::rulesPresetOptions(),
            'ai_prompt_presets' => Cup::aiPromptPresetOptions(),
            'platforms' => [
                'PC' => 'PC',
                'PlayStation' => 'PlayStation 5',
                'Xbox' => 'Xbox Series X|S',
            ],
            'statuses' => [
                'planned' => __('ui.cup_status_planned'),
                'active' => __('ui.cup_status_active'),
                'finished' => __('ui.cup_status_finished'),
                'archived' => __('ui.cup_status_archived'),
            ],
        ]);
    }

    public function apiStore(
        Request $request,
        MediaService $mediaService,
        GamificationService $gamification
    ): JsonResponse {
        $this->guardAdmin($request);

        $validated = $this->validatedCupData($request);
        $contentSettings = $this->extractCupContentSettings($validated);
        $rulesSettings = $this->extractCupRulesSettings($validated);

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $request->user(), 'cups/covers');
        }

        unset($validated['cover']);
        $validated['owner_id'] = $request->user()->id;
        $validated['slug'] = $this->uniqueSlug($validated['title']);
        $validated['settings'] = array_merge($rulesSettings, [
            'scoring' => 'bounty_first_extract_required',
            'submission_cooldown_minutes' => (int) config('hunthub.cups.submission_cooldown_minutes', 30),
            'mode' => ((int) ($validated['team_size'] ?? 1) <= 1 || strcasecmp((string) ($validated['title'] ?? ''), 'Bayou Blood Cup') === 0)
                ? 'solo_leaderboard'
                : 'team_leaderboard',
            'event_key' => strcasecmp((string) ($validated['title'] ?? ''), 'Bayou Blood Cup') === 0
                ? 'bayou_blood_cup'
                : null,
            'content' => $contentSettings,
        ]);

        $cup = Cup::create($validated);

        if ($request->hasFile('cover')) {
            $asset = $mediaService->store($request->file('cover'), $request->user(), 'cups/covers', [
                'visibility' => $cup->visibility,
                'attachable' => $cup,
            ]);
            $cup->update(['cover_path' => $asset->path]);
        }

        $gamification->award(
            $request->user(),
            'cup_created',
            source: $cup,
            description: __('ui.cup_gamification_created')
        );

        $cup->load('owner.profile')->loadCount('activeTeams');

        return response()->json([
            'message' => __('ui.cup_created_status'),
            'data' => new CupResource($cup),
        ], 201);
    }

    public function apiUpdate(Request $request, Cup $cup, MediaService $mediaService): JsonResponse
    {
        $this->guardAdmin($request);

        $validated = $this->validatedCupData($request, $cup);
        $contentSettings = $this->extractCupContentSettings($validated);
        $rulesSettings = $this->extractCupRulesSettings($validated, $cup);

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $request->user(), 'cups/covers');
        }

        unset($validated['cover']);
        $settings = is_array($cup->settings) ? $cup->settings : [];
        $settings['mode'] = ((int) ($validated['team_size'] ?? $cup->team_size ?? 1) <= 1 || strcasecmp((string) ($validated['title'] ?? $cup->title ?? ''), 'Bayou Blood Cup') === 0)
            ? 'solo_leaderboard'
            : 'team_leaderboard';
        $settings['event_key'] = strcasecmp((string) ($validated['title'] ?? $cup->title ?? ''), 'Bayou Blood Cup') === 0
            ? 'bayou_blood_cup'
            : ($settings['event_key'] ?? null);
        $settings['scoring'] = 'bounty_first_extract_required';
        $settings['content'] = $contentSettings;
        $validated['settings'] = array_merge($settings, $rulesSettings);

        $cup->update($validated);

        if ($request->hasFile('cover')) {
            $asset = $mediaService->store($request->file('cover'), $request->user(), 'cups/covers', [
                'visibility' => $cup->visibility,
                'attachable' => $cup,
            ]);
            $cup->update(['cover_path' => $asset->path]);
        }

        $cup->load('owner.profile')->loadCount('activeTeams');

        return response()->json([
            'message' => __('ui.cup_saved_status'),
            'data' => new CupResource($cup),
        ]);
    }

    public function apiArchive(Request $request, Cup $cup): JsonResponse
    {
        $this->guardAdmin($request);

        $cupId = (int) $cup->id;
        $cupSlug = (string) $cup->slug;
        $cup->update(['status' => 'archived']);
        $cup->delete();

        return response()->json([
            'message' => __('ui.cup_archived_status'),
            'archived' => true,
            'cup_id' => $cupId,
            'cup_slug' => $cupSlug,
        ]);
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function validatedCupData(Request $request, ?Cup $cup = null): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:140'],
            'summary' => ['nullable', 'string', 'max:255'],
            'summary_de' => ['nullable', 'string', 'max:255'],
            'summary_en' => ['nullable', 'string', 'max:255'],
            'rules' => ['nullable', 'string', 'max:6000'],
            'rules_de' => ['nullable', 'string', 'max:6000'],
            'rules_en' => ['nullable', 'string', 'max:6000'],
            'platform' => ['nullable', 'string', 'max:40'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:60'],
            'team_size' => ['required', 'integer', 'min:1', 'max:4'],
            'max_teams' => ['nullable', 'integer', 'min:2', 'max:256'],
            'status' => ['required', 'in:planned,active,finished,archived'],
            'visibility' => ['required', 'in:public,private'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'registration_opens_at' => ['nullable', 'date'],
            'registration_closes_at' => ['nullable', 'date', 'after_or_equal:registration_opens_at'],
            'cup_description' => ['nullable', 'string', 'max:12000'],
            'cup_description_de' => ['nullable', 'string', 'max:12000'],
            'cup_description_en' => ['nullable', 'string', 'max:12000'],
            'scoring_rules' => ['nullable', 'string', 'max:6000'],
            'scoring_rules_de' => ['nullable', 'string', 'max:6000'],
            'scoring_rules_en' => ['nullable', 'string', 'max:6000'],
            'prize_first' => ['nullable', 'string', 'max:1000'],
            'prize_first_de' => ['nullable', 'string', 'max:1000'],
            'prize_first_en' => ['nullable', 'string', 'max:1000'],
            'prize_second' => ['nullable', 'string', 'max:1000'],
            'prize_second_de' => ['nullable', 'string', 'max:1000'],
            'prize_second_en' => ['nullable', 'string', 'max:1000'],
            'prize_third' => ['nullable', 'string', 'max:1000'],
            'prize_third_de' => ['nullable', 'string', 'max:1000'],
            'prize_third_en' => ['nullable', 'string', 'max:1000'],
            'prize_note' => ['nullable', 'string', 'max:3000'],
            'prize_note_de' => ['nullable', 'string', 'max:3000'],
            'prize_note_en' => ['nullable', 'string', 'max:3000'],
            'cashout_note' => ['nullable', 'string', 'max:2000'],
            'cashout_note_de' => ['nullable', 'string', 'max:2000'],
            'cashout_note_en' => ['nullable', 'string', 'max:2000'],
            'hall_of_fame_note' => ['nullable', 'string', 'max:2000'],
            'hall_of_fame_note_de' => ['nullable', 'string', 'max:2000'],
            'hall_of_fame_note_en' => ['nullable', 'string', 'max:2000'],
            'rules_preset' => ['nullable', 'string', 'in:classic_bounty,console_mini_fair,summer_trio_first_trophy,manual_bounty_banish'],
            'ai_prompt_preset' => ['nullable', 'string', 'in:classic_summary,console_platform,awards_first_trophy'],
            'allowed_platforms' => ['nullable', 'array'],
            'allowed_platforms.*' => ['string', 'in:PC,PlayStation,Xbox'],
            'max_submissions_per_participant' => ['nullable', 'integer', 'min:0', 'max:99'],
            'max_scored_submissions_per_participant' => ['nullable', 'integer', 'min:0', 'max:99'],
            'require_profile_complete' => ['nullable', 'boolean'],
            'min_community_actions' => ['nullable', 'integer', 'min:0', 'max:20'],
            'cover' => ['nullable', 'image', 'max:'.config('hunthub.upload_limits.cup_cover_kb', 6144)],
        ]);
    }


    private function extractCupRulesSettings(array &$validated, ?Cup $cup = null): array
    {
        $existing = is_array($cup?->settings) ? $cup->settings : [];
        $rulesPreset = (string) ($validated['rules_preset'] ?? data_get($existing, 'rules_preset', 'classic_bounty'));
        if (! array_key_exists($rulesPreset, Cup::rulesPresetOptions())) {
            $rulesPreset = 'classic_bounty';
        }

        $defaultAiPreset = match ($rulesPreset) {
            'console_mini_fair' => 'console_platform',
            'summer_trio_first_trophy' => 'awards_first_trophy',
            default => 'classic_summary',
        };

        $aiPreset = (string) ($validated['ai_prompt_preset'] ?? data_get($existing, 'ai_prompt_preset', $defaultAiPreset));
        if (! array_key_exists($aiPreset, Cup::aiPromptPresetOptions())) {
            $aiPreset = $defaultAiPreset;
        }

        $allowedPlatforms = array_key_exists('allowed_platforms', $validated) ? $validated['allowed_platforms'] : [];
        if (! is_array($allowedPlatforms)) {
            $allowedPlatforms = [];
        }
        $allowedPlatforms = collect($allowedPlatforms)
            ->filter(fn ($platform): bool => in_array($platform, ['PC', 'PlayStation', 'Xbox'], true))
            ->unique()
            ->values()
            ->all();

        $isNewCup = ! $cup?->exists;
        if ($rulesPreset === 'console_mini_fair' && $isNewCup && $allowedPlatforms === []) {
            $allowedPlatforms = ['PlayStation', 'Xbox'];
        }

        if ($rulesPreset === 'manual_bounty_banish' && $isNewCup && $allowedPlatforms === []) {
            $allowedPlatforms = ['PC', 'PlayStation', 'Xbox'];
        }

        $validated['platform'] = $this->platformLabelFromAllowedPlatforms($allowedPlatforms);

        $maxUploads = $this->nullablePositiveInt(
            $validated['max_submissions_per_participant'] ?? data_get($existing, 'submission_limit.max_uploads_per_participant')
        );
        $maxScored = $this->nullablePositiveInt(
            $validated['max_scored_submissions_per_participant'] ?? data_get($existing, 'submission_limit.max_scored_runs_per_participant')
        );
        $minCommunityActions = max(0, (int) ($validated['min_community_actions'] ?? data_get($existing, 'participation_requirements.min_community_actions', 0)));
        $profileComplete = (bool) ($validated['require_profile_complete'] ?? data_get($existing, 'participation_requirements.profile_complete', false));

        if ($rulesPreset === 'console_mini_fair' && $isNewCup) {
            $maxUploads ??= 7;
            $maxScored ??= 5;
            $minCommunityActions = max(1, $minCommunityActions);
            $profileComplete = true;
        }

        if ($rulesPreset === 'summer_trio_first_trophy' && $isNewCup) {
            $maxUploads ??= 12;
            $maxScored ??= 5;
            $minCommunityActions = max(1, $minCommunityActions);
            $profileComplete = true;
        }

        if ($rulesPreset === 'manual_bounty_banish' && $isNewCup) {
            $validated['team_size'] = 1;
            $maxUploads ??= 10;
            $maxScored ??= 5;
            $minCommunityActions = max(1, $minCommunityActions);
        }

        foreach ([
            'rules_preset',
            'ai_prompt_preset',
            'allowed_platforms',
            'max_submissions_per_participant',
            'max_scored_submissions_per_participant',
            'require_profile_complete',
            'min_community_actions',
        ] as $key) {
            unset($validated[$key]);
        }

        return [
            'rules_preset' => $rulesPreset,
            'ai_prompt_preset' => $aiPreset,
            'platform_gate' => [
                'allowed_platforms' => $allowedPlatforms,
            ],
            'submission_limit' => [
                'max_uploads_per_participant' => $maxUploads,
                'max_scored_runs_per_participant' => $maxScored,
            ],
            'participation_requirements' => [
                'profile_complete' => $profileComplete,
                'min_community_actions' => $minCommunityActions,
            ],
        ];
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if (! is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }

    private function platformLabelFromAllowedPlatforms(array $allowedPlatforms): string
    {
        $allowedPlatforms = collect($allowedPlatforms)
            ->filter(fn ($platform): bool => in_array($platform, ['PC', 'PlayStation', 'Xbox'], true))
            ->unique()
            ->values()
            ->all();

        if ($allowedPlatforms === []) {
            return '';
        }

        return implode(' / ', $allowedPlatforms);
    }


    private function extractCupContentSettings(array &$validated): array
    {
        $legacy = [
            'summary' => trim((string) ($validated['summary'] ?? '')),
            'description' => trim((string) ($validated['cup_description'] ?? '')),
            'rules' => trim((string) ($validated['rules'] ?? '')),
            'scoring_rules' => trim((string) ($validated['scoring_rules'] ?? '')),
            'prizes' => [
                'first' => trim((string) ($validated['prize_first'] ?? '')),
                'second' => trim((string) ($validated['prize_second'] ?? '')),
                'third' => trim((string) ($validated['prize_third'] ?? '')),
            ],
            'prize_note' => trim((string) ($validated['prize_note'] ?? '')),
            'cashout_note' => trim((string) ($validated['cashout_note'] ?? '')),
            'hall_of_fame_note' => trim((string) ($validated['hall_of_fame_note'] ?? '')),
        ];

        $content = ['locales' => []];

        foreach (['de', 'en'] as $locale) {
            $localeContent = [
                'summary' => trim((string) ($validated['summary_'.$locale] ?? ($locale === 'de' ? $legacy['summary'] : ''))),
                'description' => trim((string) ($validated['cup_description_'.$locale] ?? ($locale === 'de' ? $legacy['description'] : ''))),
                'rules' => trim((string) ($validated['rules_'.$locale] ?? ($locale === 'de' ? $legacy['rules'] : ''))),
                'scoring_rules' => trim((string) ($validated['scoring_rules_'.$locale] ?? ($locale === 'de' ? $legacy['scoring_rules'] : ''))),
                'prizes' => [
                    'first' => trim((string) ($validated['prize_first_'.$locale] ?? ($locale === 'de' ? $legacy['prizes']['first'] : ''))),
                    'second' => trim((string) ($validated['prize_second_'.$locale] ?? ($locale === 'de' ? $legacy['prizes']['second'] : ''))),
                    'third' => trim((string) ($validated['prize_third_'.$locale] ?? ($locale === 'de' ? $legacy['prizes']['third'] : ''))),
                ],
                'prize_note' => trim((string) ($validated['prize_note_'.$locale] ?? ($locale === 'de' ? $legacy['prize_note'] : ''))),
                'cashout_note' => trim((string) ($validated['cashout_note_'.$locale] ?? ($locale === 'de' ? $legacy['cashout_note'] : ''))),
                'hall_of_fame_note' => trim((string) ($validated['hall_of_fame_note_'.$locale] ?? ($locale === 'de' ? $legacy['hall_of_fame_note'] : ''))),
            ];

            $localeContent = $this->cleanContentSettings($localeContent);
            if ($localeContent !== []) {
                $content['locales'][$locale] = $localeContent;
            }
        }

        $content = $this->cleanContentSettings($content);

        $validated['summary'] = $this->firstLocalizedContentValue($content, 'summary');
        $validated['rules'] = $this->firstLocalizedContentValue($content, 'rules');

        foreach ([
            'summary_de',
            'summary_en',
            'rules_de',
            'rules_en',
            'cup_description',
            'cup_description_de',
            'cup_description_en',
            'scoring_rules',
            'scoring_rules_de',
            'scoring_rules_en',
            'prize_first',
            'prize_first_de',
            'prize_first_en',
            'prize_second',
            'prize_second_de',
            'prize_second_en',
            'prize_third',
            'prize_third_de',
            'prize_third_en',
            'prize_note',
            'prize_note_de',
            'prize_note_en',
            'cashout_note',
            'cashout_note_de',
            'cashout_note_en',
            'hall_of_fame_note',
            'hall_of_fame_note_de',
            'hall_of_fame_note_en',
        ] as $key) {
            unset($validated[$key]);
        }

        return $content;
    }

    private function firstLocalizedContentValue(array $content, string $key): ?string
    {
        foreach (['de', 'en'] as $locale) {
            $value = data_get($content, 'locales.'.$locale.'.'.$key);
            if (trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function cleanContentSettings(array $content): array
    {
        if (isset($content['prizes']) && is_array($content['prizes'])) {
            $content['prizes'] = array_filter($content['prizes'], fn ($value): bool => trim((string) $value) !== '');
        }

        if (isset($content['locales']) && is_array($content['locales'])) {
            $content['locales'] = array_filter($content['locales'], fn ($value): bool => is_array($value) && $value !== []);
        }

        return array_filter($content, function ($value): bool {
            if (is_array($value)) {
                return $value !== [];
            }

            return trim((string) $value) !== '';
        });
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'cup';
        $slug = $base;
        $counter = 2;

        while (Cup::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
