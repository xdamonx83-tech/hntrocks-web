<?php

namespace App\Http\Controllers\Moments;

use App\Http\Controllers\Controller;
use App\Jobs\RenderMomentStudioProject;
use App\Models\CrownInventoryItem;
use App\Models\CrownShopItem;
use App\Models\Friendship;
use App\Models\Moment;
use App\Models\MomentStudioProject;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\MomentStudioProjectService;
use App\Services\Economy\CrownsService;
use App\Support\HntTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MomentController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        if (HntTheme::enabled() && HntTheme::hasResolvedOverride('moments.show') && ! $request->boolean('mine') && ! $request->boolean('saved')) {
            $studioProject = $request->boolean('upload_shell') ? null : $this->resolveStudioFeedProject($request);
            if ($studioProject) {
                if ($studioProject->status === 'published' && $studioProject->moment_id) {
                    return redirect()->route('moments.show', $studioProject->moment_id);
                }

                return HntTheme::view('moments.processing', [
                    'project' => $studioProject,
                    'statusEndpoint' => route('moments.studio.status', $studioProject),
                    'feedUrl' => route('moments.index'),
                    'studioUrl' => route('moments.create'),
                ]);
            }

            $moment = Moment::query()
                ->published()
                ->latest('published_at')
                ->latest('id')
                ->first();

            if ($moment) {
                return redirect()->route('moments.show', $moment);
            }

            return HntTheme::view('moments.empty');
        }

        $feed = (string) $request->query('feed', 'for-you');
        $mine = $request->boolean('mine');
        $saved = $request->boolean('saved');

        $moments = Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->withCount(['comments', 'reactions as likes_count' => fn ($query) => $query->where('type', 'like'), 'bookmarks'])
            ->latest('published_at')
            ->latest('id');

        if ($mine) {
            $moments->where('user_id', $request->user()->id);
        } else {
            $moments->published();
        }

        if ($saved) {
            $moments->whereHas('bookmarks', fn ($query) => $query->where('user_id', $request->user()->id));
        }

        return view('moments.index', [
            'moments' => $moments->paginate(12)->withQueryString(),
            'feed' => $feed,
            'mine' => $mine,
            'saved' => $saved,
            'stats' => [
                'total' => Moment::query()->published()->count(),
                'mine' => Moment::query()->where('user_id', $request->user()->id)->count(),
                'saved' => Moment::query()->whereHas('bookmarks', fn ($query) => $query->where('user_id', $request->user()->id))->count(),
            ],
        ]);
    }

    public function create(Request $request, CrownsService $crowns): View
    {
        return HntTheme::view('moments.create', [
            'limits' => [
                'videoMb' => round(config('hunthub.upload_limits.moment_video_kb', 204800) / 1024),
                'coverMb' => round(config('hunthub.upload_limits.moment_cover_kb', 8192) / 1024),
            ],
            'studioProFeatures' => $this->studioProFeaturePayload($request->user(), $crowns),
        ]);
    }

    public function unlockStudioFeature(Request $request, string $feature, CrownsService $crowns): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $definitions = $this->studioProFeatureDefinitions();

        if (! isset($definitions[$feature])) {
            abort(404);
        }

        $definition = $definitions[$feature];

        if (! $crowns->enabled()) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.moment_studio_crowns_unavailable'),
            ], 422);
        }

        $item = CrownShopItem::query()
            ->available()
            ->where('key', $definition['shop_key'])
            ->first();

        if (! $item) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.moment_studio_crowns_item_missing'),
            ], 404);
        }

        $alreadyOwned = $this->userOwnsCrownShopItem($user, $item->key);
        if ($alreadyOwned) {
            return response()->json([
                'ok' => true,
                'owned' => true,
                'message' => __('ui.moment_studio_crowns_already_owned'),
                'balance' => $crowns->balance($user),
            ]);
        }

        $inventoryItem = DB::transaction(function () use ($user, $item, $crowns): ?CrownInventoryItem {
            $alreadyOwned = CrownInventoryItem::query()
                ->where('user_id', $user->id)
                ->where('shop_item_id', $item->id)
                ->lockForUpdate()
                ->exists();

            if ($alreadyOwned) {
                return CrownInventoryItem::query()
                    ->where('user_id', $user->id)
                    ->where('shop_item_id', $item->id)
                    ->first();
            }

            $transaction = $crowns->spend(
                $user,
                (int) $item->price,
                'moment_studio_feature_unlock',
                $item,
                __('ui.moment_studio_crowns_transaction', ['item' => $item->displayName()]),
                ['shop_item_key' => $item->key, 'type' => $item->type, 'slot' => $item->slot]
            );

            if (! $transaction) {
                return null;
            }

            return CrownInventoryItem::create([
                'user_id' => $user->id,
                'shop_item_id' => $item->id,
                'purchased_at' => now(),
                'metadata' => ['transaction_id' => $transaction->id, 'source' => 'moment_studio'],
            ]);
        });

        if (! $inventoryItem) {
            return response()->json([
                'ok' => false,
                'message' => __('ui.moment_studio_crowns_not_enough'),
                'balance' => $crowns->balance($user),
                'price' => (int) $item->price,
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'owned' => true,
            'feature' => $feature,
            'message' => __('ui.moment_studio_crowns_unlock_success', ['item' => $item->displayName()]),
            'balance' => $crowns->balance($user),
            'price' => (int) $item->price,
        ]);
    }

    private function resolveStudioFeedProject(Request $request): ?MomentStudioProject
    {
        /** @var User|null $user */
        $user = $request->user();
        if (! $user) {
            return null;
        }

        $projectId = $request->integer('studio_project') ?: (int) session('moment_studio_project_id', 0);
        if ($projectId > 0) {
            $project = MomentStudioProject::query()
                ->where('user_id', $user->id)
                ->whereKey($projectId)
                ->first();

            if ($project) {
                return $project;
            }
        }

        return MomentStudioProject::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['uploading', 'queued', 'rendering'])
            ->latest('updated_at')
            ->latest('id')
            ->first();
    }

    public function store(Request $request, MediaService $mediaService, GamificationService $gamification): RedirectResponse|JsonResponse
    {
        if ($this->isStudioMultiClipRequest($request)) {
            return $this->storeStudioProject($request, $mediaService);
        }

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:'.config('hunthub.upload_limits.moment_video_kb', 204800)],
            'cover' => ['nullable', 'image', 'max:'.config('hunthub.upload_limits.moment_cover_kb', 8192)],
            'caption' => ['nullable', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:public,registered,private'],
            'trim_start_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
            'trim_end_seconds' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ]);

        $mediaService->assertAllowed($request->file('video'), $user, 'moments');

        if ($request->hasFile('cover')) {
            $mediaService->assertAllowed($request->file('cover'), $user, 'moments_cover');
        }

        $video = $mediaService->store($request->file('video'), $user, 'moments', [
            'visibility' => $validated['visibility'],
            'metadata' => [
                'source' => 'moment_upload',
                'trim_start_seconds' => $validated['trim_start_seconds'] ?? null,
                'trim_end_seconds' => $validated['trim_end_seconds'] ?? null,
            ],
        ]);

        $cover = null;
        if ($request->hasFile('cover')) {
            $cover = $mediaService->store($request->file('cover'), $user, 'moments_cover', [
                'visibility' => $validated['visibility'],
                'metadata' => ['source' => 'moment_cover_upload'],
            ]);
        }

        $moment = Moment::create([
            'user_id' => $user->id,
            'media_asset_id' => $video->id,
            'cover_media_asset_id' => $cover?->id,
            'caption' => $validated['caption'] ?? null,
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
            'status' => 'published',
            'processing_status' => $video->status === 'processing' ? 'processing' : 'ready',
            'trim_start_seconds' => $validated['trim_start_seconds'] ?? null,
            'trim_end_seconds' => $validated['trim_end_seconds'] ?? null,
            'published_at' => now(),
        ]);

        $mediaService->attach($video, $moment);
        if ($cover) {
            $mediaService->attach($cover, $moment);
        }

        $gamification->award($user, 'moment_created', source: $moment, description: 'Moment veröffentlicht');

        $message = $moment->processing_status === 'processing' ? 'Moment wurde gespeichert und wird jetzt komprimiert.' : 'Moment wurde gespeichert.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => $moment->processing_status === 'processing' ? 'processing' : 'published',
                'message' => $message,
                'redirect_url' => route('moments.show', $moment),
                'moment_id' => $moment->id,
            ]);
        }

        return redirect()->route('moments.show', $moment)->with('status', $message);
    }

    private function isStudioMultiClipRequest(Request $request): bool
    {
        $payload = trim((string) $request->input('studio_payload', ''));

        return $payload !== '' && $request->hasFile('studio_videos') && count(Arr::wrap($request->file('studio_videos'))) >= 1;
    }

    private function storeStudioProject(Request $request, MediaService $mediaService, ?MomentStudioProjectService $studioProjects = null): RedirectResponse|JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $studioProjects ??= app(MomentStudioProjectService::class);

        $validated = $request->validate([
            'studio_videos' => ['required', 'array', 'min:1', 'max:5'],
            'studio_videos.*' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:'.config('hunthub.upload_limits.moment_video_kb', 204800)],
            'studio_payload' => ['required', 'string', 'max:20000'],
            'caption' => ['nullable', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:2000'],
            'visibility' => ['required', 'in:public,registered,private'],
        ]);

        $files = array_values(Arr::wrap($request->file('studio_videos')));
        $project = $studioProjects->createQueuedProject($user, $files, (string) $validated['studio_payload'], [
            'visibility' => $validated['visibility'],
            'caption' => $validated['caption'] ?? null,
            'description' => $validated['description'] ?? null,
        ], $mediaService);

        if ($project) {
            RenderMomentStudioProject::dispatchAfterResponse($project->id);
        }

        if ($project) {
            $redirectUrl = route('moments.index', ['studio_project' => $project->id]);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'status' => $project->status,
                    'message' => __('ui.moment_studio_processing_flash'),
                    'project_id' => $project->id,
                    'redirect_url' => $redirectUrl,
                    'processing_url' => route('moments.studio.processing', $project),
                    'status_endpoint' => route('moments.studio.status', $project),
                    'feed_url' => route('moments.index'),
                ]);
            }

            return redirect()
                ->to($redirectUrl)
                ->with('moment_studio_project_id', $project->id)
                ->with('status', __('ui.moment_studio_processing_flash'));
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'queued',
                'message' => __('ui.moment_studio_processing_flash'),
                'redirect_url' => route('moments.index'),
                'feed_url' => route('moments.index'),
            ]);
        }

        return redirect()->route('moments.index')->with('status', __('ui.moment_studio_processing_flash'));
    }

    public function processing(Request $request, MomentStudioProject $project): View|RedirectResponse
    {
        abort_unless((int) $project->user_id === (int) $request->user()->id, 404);

        $project->refresh();

        if ($project->status === 'published' && $project->moment_id) {
            return redirect()->route('moments.show', $project->moment_id);
        }

        return HntTheme::view('moments.processing', [
            'project' => $project,
            'statusEndpoint' => route('moments.studio.status', $project),
            'feedUrl' => route('moments.index'),
            'studioUrl' => route('moments.create'),
        ]);
    }

    public function studioStatus(Request $request, MomentStudioProject $project): JsonResponse
    {
        abort_unless((int) $project->user_id === (int) $request->user()->id, 404);

        $project->refresh();

        $redirectUrl = null;
        if ($project->status === 'published' && $project->moment_id) {
            $redirectUrl = route('moments.show', $project->moment_id);
        }

        return response()->json([
            'status' => $project->status,
            'label' => match ($project->status) {
                'queued' => __('ui.moment_studio_processing_state_queued'),
                'rendering' => __('ui.moment_studio_processing_state_rendering'),
                'published' => __('ui.moment_studio_processing_state_published'),
                'failed' => __('ui.moment_studio_processing_state_failed'),
                default => __('ui.moment_studio_processing_state_preparing'),
            },
            'message' => match ($project->status) {
                'queued' => __('ui.moment_studio_processing_message_queued'),
                'rendering' => __('ui.moment_studio_processing_message_rendering'),
                'published' => __('ui.moment_studio_processing_message_published'),
                'failed' => $project->error_message ?: __('ui.moment_studio_processing_message_failed'),
                default => __('ui.moment_studio_processing_message_preparing'),
            },
            'redirect_url' => $redirectUrl,
            'feed_url' => route('moments.index'),
            'studio_url' => route('moments.create'),
        ]);
    }

    /** @return array<int, array<string, float|int>> */
    private function normalizedStudioClips(array $payload, int $fileCount): array
    {
        $clips = $payload['clips'] ?? [];
        if (! is_array($clips) || count($clips) !== $fileCount) {
            throw ValidationException::withMessages([
                'studio_payload' => 'Die Anzahl der Studio-Clips passt nicht zu den hochgeladenen Dateien.',
            ]);
        }

        $normalized = [];
        foreach (array_values($clips) as $index => $clip) {
            if (! is_array($clip)) {
                throw ValidationException::withMessages(['studio_payload' => 'Ungültige Clip-Daten.']);
            }

            $fileIndex = (int) ($clip['file_index'] ?? $index);
            if ($fileIndex !== $index) {
                throw ValidationException::withMessages(['studio_payload' => 'Clip-Reihenfolge und Upload-Reihenfolge stimmen nicht überein.']);
            }

            $start = max(0.0, (float) ($clip['start'] ?? 0));
            $end = max(0.0, (float) ($clip['end'] ?? 0));
            if ($end <= $start) {
                throw ValidationException::withMessages(['studio_payload' => 'Ein Clip hat ungültige Start-/Endzeiten.']);
            }

            $duration = min(120.0, max(0.1, $end - $start));
            $normalized[] = [
                'file_index' => $index,
                'start' => round($start, 3),
                'end' => round($end, 3),
                'duration' => round($duration, 3),
                'fade_in' => filter_var($clip['fade_in'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'fade_out' => filter_var($clip['fade_out'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'filter' => $this->normalizeStudioFilter($clip['filter'] ?? 'none'),
                'effect' => $this->normalizeStudioEffect($clip['effect'] ?? 'none'),
                'colors' => $this->normalizeStudioColors($clip['colors'] ?? []),
                'transition_out' => $this->normalizeStudioTransition($clip['transition_out'] ?? 'none'),
            ];
        }

        return $normalized;
    }

    private function normalizeStudioFilter(mixed $value): string
    {
        $filter = strtolower(trim((string) $value));

        return in_array($filter, $this->allowedStudioFilters(), true) ? $filter : 'none';
    }

    /** @return array<int, string> */
    private function allowedStudioFilters(): array
    {
        return [
            'none',
            'orange_teal',
            'bold_blue',
            'golden_hour',
            'vivid_vlogger',
            'purple_undertone',
            'winter_sunset_35',
            'contrast',
            'autumn',
            'winter',
            'old_western',
            'warm_coast',
            'cool_coast',
            'warm_landscape',
            'cool_landscape',
            'golden',
            'dreamscape',
        ];
    }

    private function normalizeStudioEffect(mixed $value): string
    {
        $effect = strtolower(trim((string) $value));

        return in_array($effect, $this->allowedStudioEffects(), true) ? $effect : 'none';
    }

    /** @return array<int, string> */
    private function allowedStudioEffects(): array
    {
        return [
            'none',
            'flash',
            'impulse',
            'rotate',
            'vhs',
            'vaporwave',
            'chromatic',
            'fast_zoom',
            'slow_zoom',
            'random_zoom',
            'blur',
            'filmic',
            'glitch',
            'disco',
            'comic',
            'retro',
            'smoke',
            'shine',
            'spread',
        ];
    }

    private function normalizeStudioTransition(mixed $value): string
    {
        $transition = strtolower(trim((string) $value));

        return in_array($transition, $this->allowedStudioTransitions(), true) ? $transition : 'none';
    }

    /** @return array<int, string> */
    private function allowedStudioTransitions(): array
    {
        return [
            'none',
            'crossfade',
            'fadeblack',
            'fadewhite',
            'slideleft',
            'slideright',
            'smoothleft',
        ];
    }

    /** @return array<string, int> */
    private function normalizeStudioColors(mixed $value): array
    {
        $colors = is_array($value) ? $value : [];

        return [
            'exposure' => max(-50, min(50, (int) round((float) ($colors['exposure'] ?? 0)))),
            'contrast' => max(-50, min(50, (int) round((float) ($colors['contrast'] ?? 0)))),
            'saturation' => max(-50, min(50, (int) round((float) ($colors['saturation'] ?? 0)))),
            'temperature' => max(-50, min(50, (int) round((float) ($colors['temperature'] ?? 0)))),
            'transparency' => max(0, min(70, (int) round((float) ($colors['transparency'] ?? 0)))),
        ];
    }

    /** @return array<int, array<string, float|string>> */
    private function normalizedStudioTextLayers(array $payload, float $totalDuration): array
    {
        $layers = $payload['text_layers'] ?? [];
        if (! is_array($layers)) {
            return [];
        }

        $normalized = [];
        foreach (array_values($layers) as $layer) {
            if (! is_array($layer)) {
                continue;
            }

            $text = trim((string) ($layer['text'] ?? ''));
            $text = preg_replace('/\s+/u', ' ', $text) ?: '';
            if ($text === '') {
                continue;
            }

            $start = max(0.0, (float) ($layer['start'] ?? 0));
            $end = max($start + 0.1, (float) ($layer['end'] ?? min($totalDuration, $start + 4.0)));
            $end = min(max(0.1, $totalDuration), $end);
            if ($end <= $start) {
                continue;
            }

            $normalized[] = [
                'text' => function_exists('mb_substr') ? mb_substr($text, 0, 90) : substr($text, 0, 90),
                'start' => round($start, 3),
                'end' => round($end, 3),
                'x' => round(min(95, max(5, (float) ($layer['x'] ?? 50))), 2),
                'y' => round(min(92, max(7, (float) ($layer['y'] ?? 78))), 2),
            ];

            if (count($normalized) >= 5) {
                break;
            }
        }

        return $normalized;
    }

    /** @return array<string, array<string, string>> */
    private function studioProFeatureDefinitions(): array
    {
        return [
            'fade' => ['shop_key' => 'moment_studio_fade', 'payload_key' => 'fade'],
            'filter' => ['shop_key' => 'moment_studio_filters', 'payload_key' => 'filter'],
            'effects' => ['shop_key' => 'moment_studio_effects', 'payload_key' => 'effects'],
            'colors' => ['shop_key' => 'moment_studio_color_adjust', 'payload_key' => 'colors'],
            'transitions' => ['shop_key' => 'moment_studio_transitions', 'payload_key' => 'transitions'],
        ];
    }

    /** @return array<string, array<string, mixed>> */
    private function studioProFeaturePayload(?User $user, CrownsService $crowns): array
    {
        $definitions = $this->studioProFeatureDefinitions();
        $shopKeys = array_map(static fn (array $definition): string => $definition['shop_key'], $definitions);
        $items = CrownShopItem::query()
            ->whereIn('key', $shopKeys)
            ->get()
            ->keyBy('key');

        $ownedKeys = [];
        if ($user) {
            $ownedKeys = CrownInventoryItem::query()
                ->where('user_id', $user->id)
                ->whereHas('shopItem', fn ($query) => $query->whereIn('key', $shopKeys))
                ->with('shopItem')
                ->get()
                ->map(fn (CrownInventoryItem $inventoryItem): ?string => $inventoryItem->shopItem?->key)
                ->filter()
                ->values()
                ->all();
        }

        $payload = [];
        foreach ($definitions as $feature => $definition) {
            $item = $items->get($definition['shop_key']);
            $payload[$feature] = [
                'feature' => $feature,
                'shop_key' => $definition['shop_key'],
                'owned' => in_array($definition['shop_key'], $ownedKeys, true),
                'available' => (bool) $item,
                'price' => $item ? (int) $item->price : 0,
                'name' => $item ? $item->displayName() : '',
                'description' => $item ? $item->displayDescription() : '',
                'unlock_url' => route('moments.studio.unlock', ['feature' => $feature]),
            ];
        }

        return [
            'enabled' => $crowns->enabled(),
            'balance' => $user ? $crowns->balance($user) : 0,
            'features' => $payload,
        ];
    }

    private function userOwnsCrownShopItem(User $user, string $shopKey): bool
    {
        return CrownInventoryItem::query()
            ->where('user_id', $user->id)
            ->whereHas('shopItem', fn ($query) => $query->where('key', $shopKey))
            ->exists();
    }

    /** @param array<int, array<string, mixed>> $clips */
    private function assertStudioProFeaturesAllowed(User $user, array $clips): void
    {
        $required = $this->requiredStudioProFeatures($clips);
        if ($required === []) {
            return;
        }

        $definitions = $this->studioProFeatureDefinitions();
        $shopKeys = [];
        foreach ($required as $feature) {
            if (isset($definitions[$feature])) {
                $shopKeys[$feature] = $definitions[$feature]['shop_key'];
            }
        }

        if ($shopKeys === []) {
            return;
        }

        $owned = CrownInventoryItem::query()
            ->where('user_id', $user->id)
            ->whereHas('shopItem', fn ($query) => $query->whereIn('key', array_values($shopKeys)))
            ->with('shopItem')
            ->get()
            ->map(fn (CrownInventoryItem $inventoryItem): ?string => $inventoryItem->shopItem?->key)
            ->filter()
            ->values()
            ->all();

        $missing = [];
        foreach ($shopKeys as $feature => $shopKey) {
            if (! in_array($shopKey, $owned, true)) {
                $missing[] = $feature;
            }
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'studio_payload' => __('ui.moment_studio_pro_backend_locked'),
            ]);
        }
    }

    /** @param array<int, array<string, mixed>> $clips */
    private function requiredStudioProFeatures(array $clips): array
    {
        $required = [];
        $count = count($clips);

        foreach ($clips as $index => $clip) {
            if (! empty($clip['fade_in']) || ! empty($clip['fade_out'])) {
                $required['fade'] = 'fade';
            }

            if (($clip['filter'] ?? 'none') !== 'none') {
                $required['filter'] = 'filter';
            }

            if (($clip['effect'] ?? 'none') !== 'none') {
                $required['effects'] = 'effects';
            }

            $colors = is_array($clip['colors'] ?? null) ? $clip['colors'] : [];
            $hasColors = ((int) ($colors['exposure'] ?? 0) !== 0)
                || ((int) ($colors['contrast'] ?? 0) !== 0)
                || ((int) ($colors['saturation'] ?? 0) !== 0)
                || ((int) ($colors['temperature'] ?? 0) !== 0)
                || ((int) ($colors['transparency'] ?? 0) !== 0);
            if ($hasColors) {
                $required['colors'] = 'colors';
            }

            if ($index < $count - 1 && ($clip['transition_out'] ?? 'none') !== 'none') {
                $required['transitions'] = 'transitions';
            }
        }

        return array_values($required);
    }

    private function deleteStudioSourceAsset($asset): void
    {
        if (! $asset) {
            return;
        }

        $paths = array_values(array_filter([$asset->path ?? null, $asset->thumbnail_path ?? null]));
        if ($paths !== []) {
            Storage::disk($asset->disk ?? 'public')->delete($paths);
        }
        $asset->update(['status' => 'deleted']);
        $asset->delete();
    }

    public function show(Moment $moment): View
    {
        abort_unless(($moment->status === 'published' && $moment->visibility !== 'private') || $moment->canBeManagedBy(auth()->user()), 404);

        $viewer = auth()->user();
        $viewerId = $viewer?->id;

        $viewerLikeFilter = static function ($query) use ($viewerId): void {
            if ($viewerId) {
                $query->where('user_id', $viewerId)->where('type', 'like');
                return;
            }

            $query->whereRaw('1 = 0');
        };

        $moment->load([
            'user.profile',
            'media',
            'cover',
            'comments' => function ($query) use ($viewerLikeFilter): void {
                $query
                    ->whereNull('parent_id')
                    ->with([
                        'user.profile',
                        'reactions' => $viewerLikeFilter,
                        'replies' => function ($replyQuery) use ($viewerLikeFilter): void {
                            $replyQuery
                                ->with([
                                    'user.profile',
                                    'reactions' => $viewerLikeFilter,
                                ])
                                ->oldest();
                        },
                    ])
                    ->latest();
            },
        ]);
        $moment->increment('views_count');

        $friendship = null;

        if ($viewer && (int) $viewer->id !== (int) $moment->user_id) {
            $friendship = Friendship::query()->between($viewer, $moment->user)->first();
        }

        $nextMoment = Moment::query()
            ->published()
            ->whereKeyNot($moment->id)
            ->where('id', '<', $moment->id)
            ->latest('id')
            ->first();

        $previousMoment = Moment::query()
            ->published()
            ->whereKeyNot($moment->id)
            ->where('id', '>', $moment->id)
            ->oldest('id')
            ->first();

        $moreMoments = Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->published()
            ->whereKeyNot($moment->id)
            ->latest('published_at')
            ->latest('id')
            ->limit(8)
            ->get();

        return HntTheme::view('moments.show', [
            'moment' => $moment,
            'moreMoments' => $moreMoments,
            'friendship' => $friendship,
            'nextMoment' => $nextMoment,
            'previousMoment' => $previousMoment,
        ]);
    }

    public function update(Request $request, Moment $moment): RedirectResponse|JsonResponse
    {
        abort_unless($moment->canBeManagedBy($request->user()), 403);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $moment->update([
            'description' => $validated['description'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'description' => $moment->description,
                'description_html' => e($moment->description ?: ''),
            ]);
        }

        return redirect()->route('moments.show', $moment)->with('status', 'Moment-Beschreibung wurde gespeichert.');
    }

    public function destroy(Moment $moment): RedirectResponse
    {
        abort_unless($moment->canBeManagedBy(auth()->user()), 403);

        $moment->update(['status' => 'archived']);
        $moment->delete();

        return redirect()->route('moments.index')->with('status', 'Moment wurde archiviert.');
    }
}
