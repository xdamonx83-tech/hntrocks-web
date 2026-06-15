<?php

namespace App\Services;

use App\Models\CrownInventoryItem;
use App\Models\MediaAsset;
use App\Models\MomentStudioProject;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MomentStudioProjectService
{
    /**
     * @param array<int, UploadedFile> $files
     * @param array{visibility:string,caption?:string|null,description?:string|null} $attributes
     */
    public function createQueuedProject(User $user, array $files, string $encodedPayload, array $attributes, MediaService $mediaService): MomentStudioProject
    {
        $payload = json_decode($encodedPayload, true);
        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'studio_payload' => 'Studio-Daten konnten nicht gelesen werden.',
            ]);
        }

        $files = array_values($files);
        $clips = $this->normalizedStudioClips($payload, count($files));
        $totalDuration = array_sum(array_map(static fn (array $clip): float => (float) $clip['duration'], $clips));
        $textLayers = $this->normalizedStudioTextLayers($payload, $totalDuration);
        $format = $this->normalizeStudioFormat($payload['format'] ?? $payload['aspect_ratio'] ?? null);

        $this->assertStudioProFeaturesAllowed($user, $clips);

        if ($totalDuration <= 0 || $totalDuration > 120.05) {
            throw ValidationException::withMessages([
                'studio_payload' => 'Das fertige Moment muss zwischen 1 und 120 Sekunden lang sein.',
            ]);
        }

        $project = null;
        $sourceAssets = [];

        try {
            DB::transaction(function () use (&$project, &$sourceAssets, $user, $attributes, $files, $clips, $textLayers, $format, $mediaService): void {
                $project = MomentStudioProject::create([
                    'user_id' => $user->id,
                    'status' => 'uploading',
                    'visibility' => $attributes['visibility'],
                    'caption' => $attributes['caption'] ?? null,
                    'description' => $attributes['description'] ?? null,
                    'timeline' => ['format' => $format, 'clips' => $clips, 'text_layers' => $textLayers],
                    'source_media_asset_ids' => [],
                    'total_duration_seconds' => (int) ceil(array_sum(array_map(static fn (array $clip): float => (float) $clip['duration'], $clips))),
                    'expires_at' => now()->addDay(),
                ]);

                foreach ($files as $index => $file) {
                    $mediaService->assertAllowed($file, $user, 'moments');
                    $asset = $mediaService->store($file, $user, 'moment_studio_source', [
                        'visibility' => 'private',
                        'attachable' => $project,
                        'metadata' => [
                            'source' => 'moment_studio_source',
                            'studio_project_id' => $project->id,
                            'clip_index' => $index,
                            'trim_start_seconds' => $clips[$index]['start'],
                            'trim_end_seconds' => $clips[$index]['end'],
                            'duration_seconds' => $clips[$index]['duration'],
                        ],
                    ]);
                    $sourceAssets[] = $asset;
                }

                $project->update([
                    'status' => 'queued',
                    'source_media_asset_ids' => array_map(static fn (MediaAsset $asset): int => (int) $asset->id, $sourceAssets),
                    'queued_at' => now(),
                ]);
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            foreach ($sourceAssets as $asset) {
                $this->deleteStudioSourceAsset($asset);
            }

            throw $exception;
        }

        if (! $project instanceof MomentStudioProject) {
            throw ValidationException::withMessages([
                'studio_payload' => 'Studio-Projekt konnte nicht erstellt werden.',
            ]);
        }

        return $project;
    }

    public function normalizeStudioFormat(mixed $value): string
    {
        $format = trim((string) $value);

        return in_array($format, ['9:16', '16:9', '1:1'], true) ? $format : '9:16';
    }

    /** @return array<int, array<string, mixed>> */
    public function normalizedStudioClips(array $payload, int $fileCount): array
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

    public function normalizeStudioFilter(mixed $value): string
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

    public function normalizeStudioEffect(mixed $value): string
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

    public function normalizeStudioTransition(mixed $value): string
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
    public function normalizeStudioColors(mixed $value): array
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

    /** @return array<int, array<string, bool|float|string>> */
    public function normalizedStudioTextLayers(array $payload, float $totalDuration): array
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
                'color' => $this->normalizeStudioTextColor($layer['color'] ?? null),
                'bold' => filter_var($layer['bold'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'italic' => filter_var($layer['italic'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'underline' => filter_var($layer['underline'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];

            if (count($normalized) >= 5) {
                break;
            }
        }

        return $normalized;
    }

    private function normalizeStudioTextColor(mixed $value): string
    {
        $color = trim((string) $value);
        if (! preg_match('/\A#?([0-9a-f]{6})\z/i', $color, $matches)) {
            return '#ffffff';
        }

        return '#'.strtolower($matches[1]);
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

    private function deleteStudioSourceAsset(?MediaAsset $asset): void
    {
        if (! $asset) {
            return;
        }

        $paths = array_values(array_filter([$asset->path, $asset->thumbnail_path]));
        if ($paths !== []) {
            Storage::disk($asset->disk)->delete($paths);
        }

        $asset->update(['status' => 'deleted']);
        $asset->delete();
    }
}
