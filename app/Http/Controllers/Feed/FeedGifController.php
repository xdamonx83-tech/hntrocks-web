<?php

namespace App\Http\Controllers\Feed;

use App\Http\Controllers\Controller;
use App\Services\Gifs\GifProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedGifController extends Controller
{
    public function trending(Request $request, GifProviderService $gifs): JsonResponse
    {
        return response()->json($gifs->trending((int) $request->integer('limit', 24)));
    }

    public function search(Request $request, GifProviderService $gifs): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'limit' => ['nullable', 'integer', 'min:6', 'max:30'],
        ]);

        return response()->json($gifs->search($validated['q'] ?? '', (int) ($validated['limit'] ?? 24)));
    }
}
