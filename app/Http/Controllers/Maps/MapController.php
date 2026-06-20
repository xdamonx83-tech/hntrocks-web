<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;
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
            $data = $this->readMapData($map['data']);

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
        $data = $this->readMapData($map['data']);
        $imageAvailable = File::isFile(public_path($map['image']));
        $linesAvailable = File::isFile(public_path($map['lines']));

        return view('themes.hnt_preview.maps.show', [
            'map' => [
                ...$map,
                'slug' => $slug,
                'image_url' => asset($map['image']),
                'lines_url' => $linesAvailable ? asset($map['lines']) : null,
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
    private function readMapData(string $relativePath): array
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

        $locale = app()->getLocale() === 'de' ? 'de' : 'en';
        $markers = [];

        foreach ($decoded['markers'] as $marker) {
            if (! is_array($marker)
                || ! in_array($marker['type'] ?? null, self::MARKER_TYPES, true)
                || ! is_numeric($marker['x'] ?? null)
                || ! is_numeric($marker['y'] ?? null)) {
                continue;
            }

            $labels = is_array($marker['label'] ?? null) ? $marker['label'] : [];
            $label = trim((string) ($labels[$locale] ?? $labels['en'] ?? ''));

            $markers[] = [
                'type' => $marker['type'],
                'x' => (float) $marker['x'],
                'y' => (float) $marker['y'],
                'label' => $label !== '' ? $label : __('ui.maps_type_'.$marker['type']),
            ];
        }

        return ['markers' => $markers, 'error' => null];
    }
}
