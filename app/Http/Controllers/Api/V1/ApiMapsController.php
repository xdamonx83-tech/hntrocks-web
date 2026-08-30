<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ApiMapsController extends Controller
{
    private const MARKER_TYPES = [
        'compound',
        'boss',
        'spawn',
        'supply',
        'extract',
        'cash',
        'tower',
        'bugs',
        'wild',
        'tarot',
    ];

    public function index(): JsonResponse
    {
        $maps = HntMap::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (HntMap $map): array {
                $markers = $this->readDatabaseMarkers($map);
                $markerCounts = array_fill_keys(self::MARKER_TYPES, 0);

                foreach ($markers as $marker) {
                    $type = $marker['type'] ?? null;

                    if (is_string($type) && array_key_exists($type, $markerCounts)) {
                        $markerCounts[$type]++;
                    }
                }

                return [
                    ...$this->mapPayload($map),
                    'marker_count' => count($markers),
                    'marker_counts' => $markerCounts,
                ];
            })
            ->values();

        return response()->json(['data' => $maps]);
    }

    public function show(string $slug): JsonResponse
    {
        $map = HntMap::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json([
            'data' => [
                ...$this->mapPayload($map),
                'markers' => $this->readDatabaseMarkers($map),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function mapPayload(HntMap $map): array
    {
        return [
            'slug' => $map->slug,
            'name' => $map->name,
            'width' => $map->width,
            'height' => $map->height,
            'image_url' => $this->assetUrl($map->image_path),
            'lines_url' => $this->assetUrl($map->lines_path, true),
            'marker_types' => self::MARKER_TYPES,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readDatabaseMarkers(HntMap $map): array
    {
        try {
            if (! Schema::hasTable('hnt_map_markers')) {
                return [];
            }

            $markersQuery = $map->markers()
                ->where('status', 'approved')
                ->orderBy('sort_order')
                ->orderBy('id');

            $votesAvailable = Schema::hasTable('hnt_map_marker_votes');
            $commentsAvailable = Schema::hasTable('hnt_map_marker_comments');

            if ($votesAvailable) {
                $markersQuery->withCount([
                    'votes as up_count' => fn ($query) => $query->where('value', 1),
                    'votes as down_count' => fn ($query) => $query->where('value', -1),
                ]);
            }

            if ($commentsAvailable) {
                $markersQuery->withCount('comments');
            }

            return $markersQuery
                ->get()
                ->map(function ($marker) use ($votesAvailable, $commentsAvailable): ?array {
                    return $this->markerPayload([
                        'id' => $marker->id,
                        'type' => $marker->type,
                        'x' => $marker->x,
                        'y' => $marker->y,
                        'label_de' => $marker->label_de,
                        'label_en' => $marker->label_en,
                        'source_image' => $marker->source_image,
                        'up_count' => $votesAvailable ? $marker->up_count : 0,
                        'down_count' => $votesAvailable ? $marker->down_count : 0,
                        'comment_count' => $commentsAvailable ? $marker->comments_count : 0,
                    ]);
                })
                ->filter()
                ->values()
                ->all();
        } catch (Throwable) {
            return [];
        }
    }

    /**
     * @param  array<string, mixed>  $marker
     * @return array<string, mixed>|null
     */
    private function markerPayload(array $marker): ?array
    {
        if (! in_array($marker['type'] ?? null, self::MARKER_TYPES, true)
            || ! is_numeric($marker['x'] ?? null)
            || ! is_numeric($marker['y'] ?? null)) {
            return null;
        }

        $labelDe = $this->nullableLabel($marker['label_de'] ?? null);
        $labelEn = $this->nullableLabel($marker['label_en'] ?? null);
        $label = app()->getLocale() === 'de'
            ? ($labelDe ?? $labelEn)
            : ($labelEn ?? $labelDe);

        return [
            'id' => is_numeric($marker['id'] ?? null) ? (int) $marker['id'] : null,
            'type' => $marker['type'],
            'x' => (float) $marker['x'],
            'y' => (float) $marker['y'],
            'label' => $label ?? __('ui.maps_type_'.$marker['type']),
            'label_de' => $labelDe,
            'label_en' => $labelEn,
            'image_url' => $marker['type'] === 'cash'
                ? $this->cashSpotImageUrl($marker['source_image'] ?? null)
                : null,
            'up_count' => (int) ($marker['up_count'] ?? 0),
            'down_count' => (int) ($marker['down_count'] ?? 0),
            'viewer_vote' => null,
            'comment_count' => (int) ($marker['comment_count'] ?? 0),
        ];
    }

    private function assetUrl(?string $relativePath, bool $requireFile = false): ?string
    {
        $relativePath = is_string($relativePath) ? trim($relativePath) : null;

        if (! $this->isSafeRelativePath($relativePath)) {
            return null;
        }

        if ($requireFile) {
            $exists = str_starts_with($relativePath, 'storage/')
                ? Storage::disk('public')->exists(substr($relativePath, strlen('storage/')))
                : File::isFile(public_path($relativePath));

            if (! $exists) {
                return null;
            }
        }

        return asset($relativePath);
    }

    private function nullableLabel(mixed $label): ?string
    {
        if (! is_string($label)) {
            return null;
        }

        $label = trim($label);

        return $label !== '' ? $label : null;
    }

    private function cashSpotImageUrl(mixed $sourceImage): ?string
    {
        if (! is_string($sourceImage)) {
            return null;
        }

        $sourceImage = trim($sourceImage);

        if (! $this->isSafeRelativePath($sourceImage)) {
            return null;
        }

        $segments = explode('/', $sourceImage);

        if (! File::isFile(storage_path('app/public/maps/cash-spots/'.$sourceImage))) {
            return null;
        }

        return asset('storage/maps/cash-spots/'.implode('/', array_map('rawurlencode', $segments)));
    }

    private function isSafeRelativePath(?string $path): bool
    {
        return is_string($path)
            && $path !== ''
            && ! str_starts_with($path, '/')
            && ! str_contains($path, '..')
            && ! str_contains($path, '\\')
            && ! str_contains($path, "\0")
            && preg_match('/^[a-z][a-z0-9+.-]*:/i', $path) !== 1
            && ! in_array('', explode('/', $path), true);
    }
}
