<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use App\Support\MapVoteVisitorIdentity;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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

    public function index(Request $request): View|Response
    {
        $maps = HntMap::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->map(function (HntMap $map): array {
                $markers = $this->readDatabaseMarkers($map);

                return [
                    'slug' => $map->slug,
                    'name' => $map->name,
                    'width' => $map->width,
                    'height' => $map->height,
                    'image' => $map->image_path,
                    'lines' => $map->lines_path,
                    'image_url' => $this->assetUrl($map->image_path),
                    'lines_url' => $this->assetUrl($map->lines_path, true),
                    'image_available' => $this->assetExists($map->image_path),
                    'marker_count' => count($markers),
                    'data_available' => true,
                ];
            })
            ->values();

        $reactIndex = public_path('app/index.html');

        abort_unless(
            File::isFile($reactIndex),
            503,
            'The React application bundle is unavailable.',
        );

        return $this->reactMapsResponse($request, $maps, $reactIndex);
    }

    /**
     * Serve the current React application for the public Maps overview while
     * keeping canonical metadata, structured data and indexable fallback HTML
     * in the first server response.
     */
    private function reactMapsResponse(
        Request $request,
        \Illuminate\Support\Collection $maps,
        string $reactIndex,
    ): Response {
        $requestedLocale = strtolower((string) $request->header('X-HNT-Locale', ''));

        if (in_array($requestedLocale, ['de', 'en'], true)) {
            app()->setLocale($requestedLocale);
        }

        $html = File::get($reactIndex);
        $locale = app()->getLocale() === 'de' ? 'de' : 'en';
        $title = __('ui.maps_meta_title');

        $head = view('react.maps-seo', [
            'mode' => 'head',
            'maps' => $maps,
        ])->render();

        $fallback = view('react.maps-seo', [
            'mode' => 'fallback',
            'maps' => $maps,
        ])->render();

        $html = preg_replace(
            '~<html\s+lang="[^"]*"~i',
            '<html lang="'.$locale.'"',
            $html,
            1,
        ) ?? $html;

        $html = preg_replace(
            '~<title>.*?</title>~is',
            '<title>'.e($title).'</title>',
            $html,
            1,
        ) ?? $html;

        $html = preg_replace(
            '~<meta\s+name="description"[^>]*>~i',
            '',
            $html,
            1,
        ) ?? $html;

        abort_unless(
            str_contains($html, '</head>'),
            503,
            'The React application head could not be prepared.',
        );

        $html = str_replace('</head>', $head."\n</head>", $html);
        $rootPattern = '~<div\s+id="root"\s*></div>~i';

        abort_unless(
            preg_match($rootPattern, $html) === 1,
            503,
            'The React application root could not be prepared.',
        );

        $html = preg_replace(
            $rootPattern,
            '<div id="root">'.$fallback.'</div>',
            $html,
            1,
        ) ?? $html;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Language' => $locale,
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function show(Request $request, MapVoteVisitorIdentity $visitorIdentity, string $slug): View
    {
        $map = HntMap::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $viewerVisitorHash = $request->user() === null
            ? $visitorIdentity->hashFromRequest($request)
            : null;
        $markers = $this->readDatabaseMarkers($map, $viewerVisitorHash);

        return view('themes.hnt_preview.maps.show', [
            'map' => [
                'slug' => $map->slug,
                'name' => $map->name,
                'width' => $map->width,
                'height' => $map->height,
                'image' => $map->image_path,
                'lines' => $map->lines_path,
                'image_url' => $this->assetUrl($map->image_path),
                'lines_url' => $this->assetUrl($map->lines_path, true),
                'cash_spot_submission_url' => route('maps.cash-spots.store', ['map' => $map->slug]),
            ],
            'markers' => $markers,
            'markerTypes' => self::MARKER_TYPES,
            'availableMaps' => HntMap::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['slug', 'name'])
                ->map(fn (HntMap $availableMap): array => [
                    'slug' => $availableMap->slug,
                    'name' => $availableMap->name,
                    'url' => route('maps.show', $availableMap->slug),
                ])
                ->values(),
            'imageAvailable' => $this->assetExists($map->image_path),
            'dataError' => null,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readDatabaseMarkers(HntMap $map, ?string $viewerVisitorHash = null): array
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

                if (auth()->check()) {
                    $markersQuery->with(['votes' => fn ($query) => $query
                        ->where('user_id', auth()->id())
                        ->select(['id', 'hnt_map_marker_id', 'value'])]);
                } elseif ($viewerVisitorHash !== null) {
                    $markersQuery->with(['votes' => fn ($query) => $query
                        ->where('visitor_hash', $viewerVisitorHash)
                        ->select(['id', 'hnt_map_marker_id', 'value'])]);
                }
            }

            if ($commentsAvailable) {
                $markersQuery->withCount('comments');
            }

            return $markersQuery->get()->map(function ($marker) use ($votesAvailable, $commentsAvailable): ?array {
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
                $safeMarker['comments_url'] = route('api.v1.maps.markers.comments.index', $marker);
                $safeMarker['comment_store_url'] = auth()->check()
                    ? route('maps.markers.comments.store', $marker)
                    : null;
                $safeMarker['comment_count'] = $commentsAvailable ? (int) $marker->comments_count : 0;
                $safeMarker['viewer_can_comment'] = auth()->check();

                if (! $votesAvailable) {
                    return $safeMarker;
                }

                return [
                    ...$safeMarker,
                    'vote_url' => route('maps.markers.vote', $marker),
                    'up_count' => (int) $marker->up_count,
                    'down_count' => (int) $marker->down_count,
                    'viewer_vote' => $marker->relationLoaded('votes') ? $marker->votes->first()?->value : null,
                ];
            })->filter()->values()->all();
        } catch (Throwable) {
            return [];
        }
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

    private function assetUrl(?string $relativePath, bool $requireFile = false): ?string
    {
        $relativePath = is_string($relativePath) ? trim($relativePath) : null;

        if (! $this->isSafeRelativePath($relativePath)) {
            return null;
        }

        if ($requireFile && ! $this->assetExists($relativePath)) {
            return null;
        }

        return asset($relativePath);
    }

    private function assetExists(?string $relativePath): bool
    {
        $relativePath = is_string($relativePath) ? trim($relativePath) : null;

        if (! $this->isSafeRelativePath($relativePath)) {
            return false;
        }

        if (str_starts_with($relativePath, 'storage/')) {
            return Storage::disk('public')->exists(substr($relativePath, strlen('storage/')));
        }

        return File::isFile(public_path($relativePath));
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
        $path = storage_path('app/public/maps/cash-spots/'.$sourceImage);

        if (! File::isFile($path)) {
            return null;
        }

        return '/storage/maps/cash-spots/'.implode('/', array_map('rawurlencode', $segments));
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
