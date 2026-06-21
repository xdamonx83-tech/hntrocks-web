<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class MapController extends Controller
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

    public function index(): View
    {
        $maps = collect(self::MAPS)->map(function (array $map, string $slug): array {
            $data = $this->readMapData($slug, $map['data']);

            return [
                ...$map,
                'slug' => $slug,
                'image_url' => asset($map['image']),
                'image_available' => File::isFile(public_path($map['image'])),
                'marker_count' => count($data['markers']),
                'data_available' => $data['error'] === null,
            ];
        })->values();

        return view('themes.hnt_preview.maps.index', compact('maps'));
    }

    public function show(string $slug): View
    {
        abort_unless(isset(self::MAPS[$slug]), 404);

        $map = self::MAPS[$slug];
        $data = $this->readMapData($slug, $map['data']);
        $imageAvailable = File::isFile(public_path($map['image']));
        $linesAvailable = File::isFile(public_path($map['lines']));

        return view('themes.hnt_preview.maps.show', [
            'map' => [
                ...$map,
                'slug' => $slug,
                'image_url' => asset($map['image']),
                'lines_url' => $linesAvailable ? asset($map['lines']) : null,
                'cash_spot_submission_url' => route('maps.cash-spots.store', ['map' => $slug]),
            ],
            'markers' => $data['markers'],
            'markerTypes' => self::MARKER_TYPES,
            'availableMaps' => collect(self::MAPS)->map(fn (array $availableMap, string $availableSlug): array => [
                'slug' => $availableSlug,
                'name' => $availableMap['name'],
                'url' => route('maps.show', $availableSlug),
            ])->values(),
            'imageAvailable' => $imageAvailable,
            'dataError' => $data['error'],
        ]);
    }

    /**
     * @return array{markers: array<int, array<string, mixed>>, error: string|null}
     */
    private function readMapData(string $slug, string $relativePath): array
    {
        $databaseMarkers = $this->readDatabaseMarkers($slug);

        if ($databaseMarkers !== null) {
            return ['markers' => $databaseMarkers, 'error' => null];
        }

        return $this->readJsonMarkers($relativePath);
    }

    /**
     * A null result deliberately selects the JSON fallback. This also protects
     * deploys where application code arrives before its migrations or seeder.
     *
     * @return array<int, array<string, mixed>>|null
     */
    private function readDatabaseMarkers(string $slug): ?array
    {
        try {
            if (! Schema::hasTable('hnt_maps') || ! Schema::hasTable('hnt_map_markers')) {
                return null;
            }

            $map = HntMap::query()->where('slug', $slug)->where('is_active', true)->first();

            if ($map === null) {
                return null;
            }

            $markersQuery = $map->markers()
                ->where('status', 'approved')
                ->orderBy('sort_order')
                ->orderBy('id');

            $votesAvailable = Schema::hasTable('hnt_map_marker_votes');

            if ($votesAvailable) {
                $markersQuery->withCount([
                    'votes as up_count' => fn ($query) => $query->where('value', 1),
                    'votes as down_count' => fn ($query) => $query->where('value', -1),
                ]);

                if (auth()->check()) {
                    $markersQuery->with(['votes' => fn ($query) => $query
                        ->where('user_id', auth()->id())
                        ->select(['id', 'hnt_map_marker_id', 'value'])]);
                }
            }

            $markers = $markersQuery->get();

            if ($markers->isEmpty()) {
                return null;
            }

            return $markers->map(function ($marker) use ($votesAvailable): ?array {
                $safeMarker = $this->safeMarker([
                    'type' => $marker->type,
                    'x' => $marker->x,
                    'y' => $marker->y,
                    'label' => [
                        'de' => $marker->label_de,
                        'en' => $marker->label_en,
                    ],
                    'source_image' => $marker->source_image,
                ]);

                if ($safeMarker === null || $marker->type !== 'cash') {
                    return $safeMarker;
                }

                $safeMarker['id'] = $marker->id;

                if (! $votesAvailable) {
                    return $safeMarker;
                }

                return [
                    ...$safeMarker,
                    'vote_url' => route('maps.markers.vote', $marker),
                    'up_count' => (int) $marker->up_count,
                    'down_count' => (int) $marker->down_count,
                    'viewer_vote' => auth()->check() ? $marker->votes->first()?->value : null,
                    'comment_count' => 0,
                ];
            })->filter()->values()->all();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{markers: array<int, array<string, mixed>>, error: string|null}
     */
    private function readJsonMarkers(string $relativePath): array
    {
        $path = resource_path('data/'.$relativePath);

        if (! File::isFile($path)) {
            return ['markers' => [], 'error' => 'missing'];
        }

        try {
            $decoded = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return ['markers' => [], 'error' => 'invalid'];
        }

        if (! is_array($decoded) || ! is_array($decoded['markers'] ?? null)) {
            return ['markers' => [], 'error' => 'invalid'];
        }

        $markers = [];

        foreach ($decoded['markers'] as $marker) {
            $safeMarker = is_array($marker) ? $this->safeMarker($marker) : null;

            if ($safeMarker !== null) {
                $markers[] = $safeMarker;
            }
        }

        return ['markers' => $markers, 'error' => null];
    }

    /**
     * @param  array<string, mixed>  $marker
     * @return array<string, mixed>|null
     */
    private function safeMarker(array $marker): ?array
    {
        if (! in_array($marker['type'] ?? null, self::MARKER_TYPES, true)
            || ! is_numeric($marker['x'] ?? null)
            || ! is_numeric($marker['y'] ?? null)) {
            return null;
        }

        $locale = app()->getLocale() === 'de' ? 'de' : 'en';
        $labels = is_array($marker['label'] ?? null) ? $marker['label'] : [];
        $label = trim((string) ($labels[$locale] ?? $labels['en'] ?? ''));
        $safeMarker = [
            'type' => $marker['type'],
            'x' => (float) $marker['x'],
            'y' => (float) $marker['y'],
            'label' => $label !== '' ? $label : __('ui.maps_type_'.$marker['type']),
        ];

        if ($marker['type'] === 'cash') {
            $imageUrl = $this->cashSpotImageUrl($marker['source_image'] ?? null);

            if ($imageUrl !== null) {
                $safeMarker['image_url'] = $imageUrl;
            }
        }

        return $safeMarker;
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

        $path = storage_path('app/public/maps/cash-spots/'.$sourceImage);

        if (! File::isFile($path)) {
            return null;
        }

        return '/storage/maps/cash-spots/'.implode('/', array_map('rawurlencode', $segments));
    }
}
