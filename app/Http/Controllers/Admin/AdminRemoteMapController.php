<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HntMap;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminRemoteMapController extends Controller
{
    public function create(Request $request): View
    {
        $this->guardAdmin($request);

        return view('admin.maps.edit', [
            'map' => null,
            'imageUrl' => null,
            'linesUrl' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => [
                'required',
                'string',
                'max:120',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('hnt_maps', 'slug'),
            ],
            'width' => ['required', 'integer', 'min:1', 'max:10000'],
            'height' => ['required', 'integer', 'min:1', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'map_image' => ['required', 'image', 'mimes:webp,png,jpg,jpeg', 'max:30720'],
            'lines_image' => ['nullable', 'image', 'mimes:webp,png', 'max:30720'],
        ]);

        $slug = (string) $validated['slug'];
        $imagePath = null;
        $linesPath = null;

        try {
            $imagePath = $this->storeAsset($validated['map_image'], $slug, 'map');
            if (($validated['lines_image'] ?? null) instanceof UploadedFile) {
                $linesPath = $this->storeAsset($validated['lines_image'], $slug, 'lines');
            }

            $map = HntMap::query()->create([
                'slug' => $slug,
                'name' => trim((string) $validated['name']),
                'width' => (int) $validated['width'],
                'height' => (int) $validated['height'],
                'image_path' => $imagePath,
                'lines_path' => $linesPath,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => false,
            ]);
        } catch (\Throwable $error) {
            Storage::disk('public')->deleteDirectory('maps/'.$slug);
            throw $error;
        }

        return redirect()
            ->route('admin.maps.edit', $map)
            ->with('status', 'Map wurde inaktiv angelegt. Jetzt Marker setzen und anschließend aktivieren.');
    }

    public function edit(Request $request, HntMap $map): View
    {
        $this->guardAdmin($request);

        return view('admin.maps.edit', [
            'map' => $map,
            'imageUrl' => $this->publicAssetUrl($map->image_path),
            'linesUrl' => $this->publicAssetUrl($map->lines_path),
        ]);
    }

    public function update(Request $request, HntMap $map): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'width' => ['required', 'integer', 'min:1', 'max:10000'],
            'height' => ['required', 'integer', 'min:1', 'max:10000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active' => ['nullable', 'boolean'],
            'map_image' => ['nullable', 'image', 'mimes:webp,png,jpg,jpeg', 'max:30720'],
            'lines_image' => ['nullable', 'image', 'mimes:webp,png', 'max:30720'],
            'remove_lines' => ['nullable', 'boolean'],
        ]);

        $newImagePath = null;
        $newLinesPath = null;

        try {
            if (($validated['map_image'] ?? null) instanceof UploadedFile) {
                $newImagePath = $this->storeAsset($validated['map_image'], $map->slug, 'map');
            }

            if (($validated['lines_image'] ?? null) instanceof UploadedFile) {
                $newLinesPath = $this->storeAsset($validated['lines_image'], $map->slug, 'lines');
            }

            $imagePath = $newImagePath ?? $map->image_path;
            $linesPath = $request->boolean('remove_lines')
                ? null
                : ($newLinesPath ?? $map->lines_path);
            $isActive = $request->boolean('is_active');

            if ($isActive && ! $this->publicAssetExists($imagePath)) {
                throw ValidationException::withMessages([
                    'map_image' => 'Eine aktive Map benötigt ein erreichbares Kartenbild.',
                ]);
            }

            $oldImagePath = $map->image_path;
            $oldLinesPath = $map->lines_path;

            $map->forceFill([
                'name' => trim((string) $validated['name']),
                'width' => (int) $validated['width'],
                'height' => (int) $validated['height'],
                'image_path' => $imagePath,
                'lines_path' => $linesPath,
                'sort_order' => (int) ($validated['sort_order'] ?? 0),
                'is_active' => $isActive,
            ])->save();

            if ($newImagePath !== null) {
                $this->deleteOwnedAsset($oldImagePath, $map->slug, $newImagePath);
            }

            if ($newLinesPath !== null || $request->boolean('remove_lines')) {
                $this->deleteOwnedAsset($oldLinesPath, $map->slug, $newLinesPath);
            }
        } catch (\Throwable $error) {
            if ($newImagePath !== null) {
                $this->deletePublicStoragePath($newImagePath);
            }

            if ($newLinesPath !== null) {
                $this->deletePublicStoragePath($newLinesPath);
            }

            throw $error;
        }

        return back()->with(
            'status',
            $map->is_active
                ? 'Map gespeichert und aktiv. Sie ist jetzt über Web und /api/v1/maps verfügbar.'
                : 'Map gespeichert. Sie bleibt inaktiv.',
        );
    }

    private function storeAsset(UploadedFile $file, string $slug, string $kind): string
    {
        $extension = strtolower($file->extension() ?: $file->guessExtension() ?: 'bin');
        $filename = $kind.'-'.Str::uuid().'.'.$extension;
        $directory = 'maps/'.$slug;

        $stored = Storage::disk('public')->putFileAs($directory, $file, $filename);

        abort_unless(is_string($stored) && $stored !== '', 500, 'Map-Datei konnte nicht gespeichert werden.');

        return 'storage/'.$stored;
    }

    private function publicAssetUrl(?string $relativePath): ?string
    {
        return $this->publicAssetExists($relativePath) ? asset($relativePath) : null;
    }

    private function publicAssetExists(?string $relativePath): bool
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

    private function deleteOwnedAsset(?string $relativePath, string $slug, ?string $replacementPath = null): void
    {
        if (! is_string($relativePath) || $relativePath === '' || $relativePath === $replacementPath) {
            return;
        }

        $prefix = 'storage/maps/'.$slug.'/';

        if (! str_starts_with($relativePath, $prefix)) {
            return;
        }

        $this->deletePublicStoragePath($relativePath);
    }

    private function deletePublicStoragePath(string $relativePath): void
    {
        if (! str_starts_with($relativePath, 'storage/')) {
            return;
        }

        Storage::disk('public')->delete(substr($relativePath, strlen('storage/')));
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
