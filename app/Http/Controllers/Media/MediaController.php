<?php

namespace App\Http\Controllers\Media;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Services\GamificationService;
use App\Services\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $baseQuery = MediaAsset::query()
            ->where('user_id', $user->id)
            ->where('status', 'ready');

        $query = (clone $baseQuery)->latest();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($context = $request->query('context')) {
            $query->where('context', $context);
        }

        $assets = $query->paginate(24)->withQueryString();

        $stats = [
            'total' => (clone $baseQuery)->count(),
            'images' => (clone $baseQuery)->where('type', 'image')->count(),
            'videos' => (clone $baseQuery)->where('type', 'video')->count(),
            'files' => (clone $baseQuery)->where('type', 'file')->count(),
            'storage_bytes' => (int) (clone $baseQuery)->sum('size_bytes'),
        ];

        $uploadLimits = [
            'count' => (int) config('hunthub.upload_limits.media_library_count', 10),
            'file_mb' => round(((int) config('hunthub.upload_limits.media_library_file_kb', 51200)) / 1024, 1),
        ];

        return view('media.index', [
            'assets' => $assets,
            'filters' => $request->only(['type', 'context']),
            'stats' => $stats,
            'uploadLimits' => $uploadLimits,
        ]);
    }

    public function store(Request $request, MediaService $mediaService, GamificationService $gamification): RedirectResponse
    {
        $validated = $request->validate([
            'context' => ['nullable', 'string', Rule::in(['library', 'feed', 'profile', 'team', 'messages', 'moments', 'cups'])],
            'visibility' => ['nullable', 'string', Rule::in(['private', 'registered', 'public'])],
            'files' => ['required', 'array', 'max:'.config('hunthub.upload_limits.media_library_count', 10)],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,mov,pdf,txt', 'max:'.config('hunthub.upload_limits.media_library_file_kb', 51200)],
        ]);

        $context = $validated['context'] ?? 'library';
        $visibility = $validated['visibility'] ?? 'registered';

        foreach ($request->file('files', []) as $file) {
            $mediaService->assertAllowed($file, $request->user(), $context);
        }

        $count = 0;
        $firstAsset = null;

        foreach ($request->file('files', []) as $file) {
            $asset = $mediaService->store($file, $request->user(), $context, [
                'visibility' => $visibility,
            ]);
            $firstAsset ??= $asset;
            $count++;
        }

        if ($firstAsset) {
            $gamification->award($request->user(), 'media_uploaded', source: $firstAsset, metadata: ['count' => $count]);
        }

        return redirect()
            ->route('media.index')
            ->with('status', $count === 1 ? __('ui.media_uploaded_one') : __('ui.media_uploaded_many', ['count' => $count]));
    }

    public function destroy(Request $request, MediaAsset $asset, MediaService $mediaService): RedirectResponse
    {
        abort_unless($asset->user_id === $request->user()->id, 403);

        if ($asset->isLinkedToContent()) {
            return back()->with('status', __('ui.media_linked_delete_blocked'));
        }

        $mediaService->delete($asset);

        return redirect()->route('media.index')->with('status', __('ui.media_deleted'));
    }
}
