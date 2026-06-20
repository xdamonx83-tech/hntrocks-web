<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->with(['uploads' => fn ($query) => $query
                ->with(['uploader:id,username', 'reviewer:id,username'])
                ->latest('id')])
            ->orderByRaw("case when status = 'approved' then 0 else 1 end")
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (HntMapMarker $marker): array => $this->markerPayload($marker))
            ->values();

        return view('admin.maps.markers', [
            'map' => [
                'slug' => $map->slug,
                'name' => $map->name,
                'width' => $map->width,
                'height' => $map->height,
                'image_url' => $this->publicAssetUrl($map->image_path),
                'lines_url' => $this->publicAssetUrl($map->lines_path),
                'store_url' => route('admin.maps.markers.store', $map),
            ],
            'markers' => $markers,
        ]);
    }

    public function storeMarker(Request $request, HntMap $map): JsonResponse|RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $this->validateMarker($request, $map);
        $marker = $map->markers()->create([
            ...$validated,
            'legacy_key' => 'admin:'.Str::uuid(),
            'source_id' => null,
            'meta' => null,
        ]);

        if ($request->isJson()) {
            return response()->json([
                'ok' => true,
                'marker' => $this->markerPayload($marker),
            ], 201);
        }

        return back()->with('status', 'Marker wurde erstellt.');
    }

    public function updateMarker(Request $request, HntMapMarker $marker): JsonResponse
    {
        $this->guardAdmin($request);
        abort_unless($request->isJson(), 415);

        $map = $marker->map()->firstOrFail();
        $validated = $this->validateMarker($request, $map);

        if ($validated['type'] !== 'cash') {
            $validated['source_image'] = null;
        }

        $marker->forceFill($validated)->save();

        return response()->json([
            'ok' => true,
            'marker' => $this->markerPayload($marker->fresh()),
        ]);
    }

    public function destroyMarker(Request $request, HntMapMarker $marker): JsonResponse
    {
        $this->guardAdmin($request);
        abort_unless($request->isJson(), 415);

        $markerId = $marker->id;
        $marker->delete();

        return response()->json([
            'ok' => true,
            'marker_id' => $markerId,
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

    public function showUpload(Request $request, HntMapMarkerUpload $upload): StreamedResponse
    {
        $this->guardAdmin($request);
        abort_unless(Storage::disk($upload->disk)->exists($upload->path), 404);

        return Storage::disk($upload->disk)->response(
            $upload->path,
            $upload->original_name,
            ['Content-Type' => $upload->mime_type],
        );
    }

    public function approveUpload(Request $request, HntMapMarkerUpload $upload): RedirectResponse
    {
        $this->guardAdmin($request);

        DB::transaction(function () use ($request, $upload): void {
            $lockedUpload = HntMapMarkerUpload::query()->lockForUpdate()->findOrFail($upload->id);
            $marker = HntMapMarker::query()->lockForUpdate()->findOrFail($lockedUpload->hnt_map_marker_id);

            abort_unless($marker->type === 'cash', 422, 'Nur Cash-Marker können ein freigegebenes Bild haben.');
            abort_unless($lockedUpload->status === HntMapMarkerUpload::STATUS_PENDING, 422, 'Nur ausstehende Uploads können freigegeben werden.');
            abort_unless(Storage::disk($lockedUpload->disk)->exists($lockedUpload->path), 422, 'Die Upload-Datei fehlt.');

            $extension = strtolower(pathinfo($lockedUpload->path, PATHINFO_EXTENSION) ?: 'jpg');
            $filename = Str::uuid().'.'.$extension;
            $publicPath = 'maps/cash-spots/'.$filename;
            $stream = Storage::disk($lockedUpload->disk)->readStream($lockedUpload->path);
            $stored = false;

            abort_unless(is_resource($stream), 500, 'Die Upload-Datei konnte nicht gelesen werden.');

            try {
                $stored = Storage::disk('public')->writeStream($publicPath, $stream);
            } finally {
                fclose($stream);
            }

            abort_unless($stored, 500, 'Das freigegebene Bild konnte nicht gespeichert werden.');

            HntMapMarkerUpload::query()
                ->where('hnt_map_marker_id', $marker->id)
                ->where('status', HntMapMarkerUpload::STATUS_APPROVED)
                ->update(['status' => HntMapMarkerUpload::STATUS_SUPERSEDED]);

            $lockedUpload->forceFill([
                'status' => HntMapMarkerUpload::STATUS_APPROVED,
                'public_path' => $publicPath,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => null,
            ])->save();

            // This single reference is the only image source consumed by the public map.
            $marker->forceFill(['source_image' => $filename])->save();
        });

        return back()->with('status', 'Bild wurde freigegeben und ist jetzt für diesen Cash-Marker aktiv.');
    }

    public function rejectUpload(Request $request, HntMapMarkerUpload $upload): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $upload, $validated): void {
            $lockedUpload = HntMapMarkerUpload::query()->lockForUpdate()->findOrFail($upload->id);
            abort_unless($lockedUpload->status === HntMapMarkerUpload::STATUS_PENDING, 422, 'Nur ausstehende Uploads können abgelehnt werden.');

            $lockedUpload->forceFill([
                'status' => HntMapMarkerUpload::STATUS_REJECTED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $this->nullableTrimmedString($validated['reason'] ?? null),
            ])->save();
        });

        return back()->with('status', 'Bild wurde abgelehnt. Die Datei wurde nicht gelöscht.');
    }

    private function validateMarker(Request $request, HntMap $map): array
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in([
                'compound', 'boss', 'spawn', 'supply', 'extract', 'cash', 'tower', 'bugs', 'wild', 'tarot',
            ])],
            'x' => ['required', 'numeric', 'min:0', 'max:'.$map->width],
            'y' => ['required', 'numeric', 'min:0', 'max:'.$map->height],
            'label_de' => ['nullable', 'string', 'max:120'],
            'label_en' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'string', Rule::in(['approved', 'pending', 'hidden'])],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $validated['x'] = (float) $validated['x'];
        $validated['y'] = (float) $validated['y'];
        $validated['label_de'] = $this->nullableTrimmedString($validated['label_de'] ?? null);
        $validated['label_en'] = $this->nullableTrimmedString($validated['label_en'] ?? null);
        return $validated;
    }

    private function nullableTrimmedString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function markerPayload(HntMapMarker $marker): array
    {
        return [
            'id' => $marker->id,
            'type' => $marker->type,
            'x' => $marker->x,
            'y' => $marker->y,
            'label' => $this->markerLabel($marker),
            'label_de' => $marker->label_de,
            'label_en' => $marker->label_en,
            'source_image' => $marker->source_image,
            'image_url' => $marker->type === 'cash'
                ? $this->cashSpotImageUrl($marker->source_image)
                : null,
            'status' => $marker->status,
            'sort_order' => $marker->sort_order,
            'position_url' => route('admin.maps.markers.position', $marker),
            'update_url' => route('admin.maps.markers.update', $marker),
            'delete_url' => route('admin.maps.markers.destroy', $marker),
            'uploads' => $marker->uploads->map(fn (HntMapMarkerUpload $upload): array => [
                'id' => $upload->id,
                'status' => $upload->status,
                'original_name' => $upload->original_name,
                'mime_type' => $upload->mime_type,
                'size' => $upload->size,
                'uploader' => $upload->uploader?->username,
                'reviewer' => $upload->reviewer?->username,
                'reviewed_at' => $upload->reviewed_at?->toIso8601String(),
                'rejection_reason' => $upload->rejection_reason,
                'preview_url' => route('admin.maps.uploads.show', $upload),
                'approve_url' => route('admin.maps.uploads.approve', $upload),
                'reject_url' => route('admin.maps.uploads.reject', $upload),
            ])->values(),
        ];
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
