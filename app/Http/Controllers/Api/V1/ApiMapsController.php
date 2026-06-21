<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
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

    private const MAPS = [
        'stillwater-bayou' => [
            'name' => 'Stillwater Bayou',
            'width' => 2048,
            'height' => 2048,
            'image' => 'assets/hnt/maps/stillwater-bayou/map.webp',
            'lines' => 'assets/hnt/maps/stillwater-bayou/lines.png',
            'data' => 'maps/stillwater-bayou.json',
        ],
        'lawson-delta' => [
            'name' => 'Lawson Delta',
            'width' => 2048,
            'height' => 2048,
            'image' => 'assets/hnt/maps/lawson-delta/map.webp',
            'lines' => 'assets/hnt/maps/lawson-delta/lines.png',
            'data' => 'maps/lawson-delta.json',
        ],
        'desalle' => [
            'name' => 'DeSalle',
            'width' => 2048,
            'height' => 2048,
            'image' => 'assets/hnt/maps/desalle/map.webp',
            'lines' => 'assets/hnt/maps/desalle/lines.png',
            'data' => 'maps/desalle.json',
        ],
        'mammons-gulch' => [
            'name' => "Mammon's Gulch",
            'width' => 2048,
            'height' => 2048,
            'image' => 'assets/hnt/maps/mammons-gulch/map.webp',
            'lines' => 'assets/hnt/maps/mammons-gulch/lines.png',
            'data' => 'maps/mammons-gulch.json',
        ],
    ];

    public function index(): JsonResponse
    {
        $maps = collect(self::MAPS)->map(function (array $map, string $slug): array {
            return [
                ...$this->mapPayload($slug, $map),
                'marker_count' => count($this->readMarkers($slug, $map['data'])),
            ];
        })->values();

        return response()->json(['data' => $maps]);
    }

    public function show(string $slug): JsonResponse
    {
        abort_unless(isset(self::MAPS[$slug]), 404);

        $map = self::MAPS[$slug];

        return response()->json([
            'data' => [
                ...$this->mapPayload($slug, $map),
                'markers' => $this->readMarkers($slug, $map['data']),
            ],
        ]);
    }

    /**
     * @param  array{name: string, width: int, height: int, image: string, lines: string, data: string}  $map
     * @return array<string, mixed>
     */
    private function mapPayload(string $slug, array $map): array
    {
        return [
            'slug' => $slug,
            'name' => $map['name'],
            'width' => $map['width'],
            'height' => $map['height'],
            'image_url' => asset($map['image']),
            'lines_url' => asset($map['lines']),
            'marker_types' => self::MARKER_TYPES,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readMarkers(string $slug, string $relativePath): array
    {
        $databaseMarkers = $this->readDatabaseMarkers($slug);

        return $databaseMarkers ?? $this->readJsonMarkers($relativePath);
    }

    /**
     * A null result deliberately selects the bundled JSON fallback when the
     * maps schema, active map, or approved marker data is not available.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function readDatabaseMarkers(string $slug): ?array
    {
        try {
            if (! Schema::hasTable('hnt_maps') || ! Schema::hasTable('hnt_map_markers')) {
                return null;
            }

            $map = HntMap::query()
                ->where('slug', $slug)
                ->where('is_active', true)
                ->first();

            if ($map === null) {
                return null;
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

            $markers = $markersQuery->get();

            if ($markers->isEmpty()) {
                return null;
            }

            return $markers->map(function ($marker) use ($votesAvailable, $commentsAvailable): ?array {
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
            })->filter()->values()->all();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readJsonMarkers(string $relativePath): array
    {
        $path = resource_path('data/'.$relativePath);

        if (! File::isFile($path)) {
            return [];
        }

        try {
            $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }

        if (! is_array($decoded) || ! is_array($decoded['markers'] ?? null)) {
            return [];
        }

        return collect($decoded['markers'])
            ->map(function (mixed $marker): ?array {
                if (! is_array($marker)) {
                    return null;
                }

                $labels = is_array($marker['label'] ?? null) ? $marker['label'] : [];

                return $this->markerPayload([
                    'id' => $marker['source_id'] ?? null,
                    'type' => $marker['type'] ?? null,
                    'x' => $marker['x'] ?? null,
                    'y' => $marker['y'] ?? null,
                    'label_de' => $labels['de'] ?? null,
                    'label_en' => $labels['en'] ?? null,
                    'source_image' => $marker['source_image'] ?? null,
                    'up_count' => 0,
                    'down_count' => 0,
                    'comment_count' => 0,
                ]);
            })
            ->filter()
            ->values()
            ->all();
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

        if ($sourceImage === ''
            || str_starts_with($sourceImage, '/')
            || str_contains($sourceImage, '..')
            || str_contains($sourceImage, '\\')
            || str_contains($sourceImage, "\0")
            || preg_match('/^[a-z][a-z0-9+.-]*:/i', $sourceImage) === 1) {
            return null;
        }

        $segments = explode('/', $sourceImage);

        if (in_array('', $segments, true)) {
            return null;
        }

        if (! File::isFile(storage_path('app/public/maps/cash-spots/'.$sourceImage))) {
            return null;
        }

        return asset('storage/maps/cash-spots/'.implode('/', array_map('rawurlencode', $segments)));
    }
}
