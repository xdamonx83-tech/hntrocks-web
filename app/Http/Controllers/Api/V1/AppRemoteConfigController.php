<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AppRemoteFeedCard;
use App\Models\AppRemoteFeedCardDismissal;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppRemoteConfigController extends Controller
{
    public function show(Request $request, AppRemoteConfigService $remoteConfig): JsonResponse
    {
        $locale = strtolower((string) $request->query('locale', app()->getLocale()));
        $appVersionCode = $request->integer('app_version_code') ?: null;
        $user = $request->user();

        return response()->json([
            'message' => 'Remote config loaded.',
            'config' => $remoteConfig->activeConfig(),
            'feed_cards' => $user
                ? $remoteConfig->visibleFeedCards($user, $appVersionCode, $locale)
                : [],
            'server_time' => now()->toIso8601String(),
        ]);
    }

    public function dismiss(Request $request, string $remote_id): JsonResponse
    {
        $card = AppRemoteFeedCard::query()
            ->where('remote_id', $remote_id)
            ->first();

        if (! $card) {
            return response()->json(['message' => 'Remote feed card not found.'], 404);
        }

        AppRemoteFeedCardDismissal::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'remote_id' => $card->remote_id,
            ],
            [
                'remote_card_id' => $card->id,
                'dismissed_at' => now(),
            ]
        );

        return response()->json([
            'message' => 'Remote feed card dismissed.',
            'dismissed' => true,
            'remote_id' => $card->remote_id,
        ]);
    }
}
