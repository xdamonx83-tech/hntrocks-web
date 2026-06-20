<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use App\Models\HntMapMarker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class AdminMapController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $maps = HntMap::query()
            ->withCount('markers')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.maps.index', compact('maps'));
    }

    public function markers(Request $request, HntMap $map): View
    {
        $this->guardAdmin($request);

        $markers = $map->markers()
            ->orderByRaw("case when status = 'approved' then 0 else 1 end")
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HntMapMarker $marker): array => [
                'id' => $marker->id,
                'type' => $marker->type,
                'x' => $marker->x,
                'y' => $marker->y,
                'label' => $this->markerLabel($marker),
                'image_url' => $marker->type === 'cash'
                    ? $this->cashSpotImageUrl($marker->source_image)
                    : null,
                'status' => $marker->status,
                'position_url' => route('admin.maps.markers.position', $marker),
            ])
            ->values();

        return view('admin.maps.markers', [
            'map' => [
                'slug' => $map->slug,
                'name' => $map->name,
                'width' => $map->width,
                'height' => $map->height,
                'image_url' => $this->publicAssetUrl($map->image_path),
                'lines_url' => $this->publicAssetUrl($map->lines_path),
            ],
            'markers' => $markers,
        ]);
    }

    public function updateMarkerPosition(Request $request, HntMapMarker $marker): JsonResponse
    {
        $this->guardAdmin($request);
        abort_unless($request->isJson(), 415);

        $map = $marker->map()->firstOrFail();
        $validated = $request->validate([
            'x' => ['required', 'numeric', 'min:0', 'max:'.$map->width],
            'y' => ['required', 'numeric', 'min:0', 'max:'.$map->height],
        ]);

        $marker->forceFill([
            'x' => (float) $validated['x'],
            'y' => (float) $validated['y'],
        ])->save();

        return response()->json([
            'ok' => true,
            'marker_id' => $marker->id,
            'x' => $marker->x,
            'y' => $marker->y,
        ]);
    }

    private function markerLabel(HntMapMarker $marker): string
    {
        $label = app()->getLocale() === 'de' ? $marker->label_de : $marker->label_en;
        $fallback = app()->getLocale() === 'de' ? $marker->label_en : $marker->label_de;

        return trim((string) ($label ?: $fallback ?: ucfirst($marker->type)));
    }

    private function publicAssetUrl(?string $relativePath): ?string
    {
        $relativePath = is_string($relativePath) ? trim($relativePath) : null;

        if (! $this->isSafeRelativePath($relativePath)) {
            return null;
        }

        return File::isFile(public_path($relativePath)) ? asset($relativePath) : null;
    }

    private function cashSpotImageUrl(?string $sourceImage): ?string
    {
        $sourceImage = is_string($sourceImage) ? trim($sourceImage) : null;

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

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
