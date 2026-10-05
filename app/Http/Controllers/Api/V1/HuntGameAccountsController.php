<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\HuntGameConnection;
use App\Models\HuntGameLinkAttempt;
use App\Services\Hunt\SteamHuntStatsProvider;
use App\Services\Hunt\SteamOpenIdVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class HuntGameAccountsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $connections = HuntGameConnection::query()
            ->where('user_id', $request->user()->id)
            ->get()
            ->map(fn (HuntGameConnection $account) => $this->present($account))
            ->values();

        return response()->json([
            'data' => $connections,
            'providers' => [
                'steam' => [
                    'enabled' => (bool) config('hunt_platform.steam.enabled')
                        && filled(config('hunt_platform.steam.api_key')),
                ],
                'xbox' => ['enabled' => false],
                'playstation' => ['enabled' => false],
            ],
        ]);
    }

    public function startSteam(Request $request, SteamOpenIdVerifier $steam): JsonResponse
    {
        if (! config('hunt_platform.steam.enabled') || blank(config('hunt_platform.steam.api_key'))) {
            return response()->json(['message' => 'Steam-Verknüpfung ist noch nicht eingerichtet.'], 503);
        }

        $state = bin2hex(random_bytes(32));

        DB::transaction(function () use ($request, $state): void {
            // Only one outstanding link attempt per user.
            HuntGameLinkAttempt::query()->where('user_id', $request->user()->id)->delete();

            HuntGameLinkAttempt::query()->create([
                'user_id' => $request->user()->id,
                'state_hash' => hash('sha256', $state),
                'expires_at' => now()->addMinutes(10),
            ]);
        });

        return response()->json([
            'authorize_url' => $steam->authorizeUrl($state),
            'expires_in' => 600,
        ]);
    }

    public function syncSteam(Request $request, SteamHuntStatsProvider $provider): JsonResponse
    {
        $account = HuntGameConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'steam')
            ->first();

        if (! $account) {
            return response()->json(['message' => 'Steam ist nicht verbunden.'], 404);
        }

        $provider->sync($account);

        return response()->json(['data' => $this->present($account->refresh())]);
    }

    public function disconnectSteam(Request $request): JsonResponse
    {
        HuntGameConnection::query()
            ->where('user_id', $request->user()->id)
            ->where('provider', 'steam')
            ->delete();

        HuntGameLinkAttempt::query()->where('user_id', $request->user()->id)->delete();

        return response()->json(['disconnected' => true]);
    }

    private function present(HuntGameConnection $account): array
    {
        return [
            'provider' => $account->provider,
            'provider_name' => $account->provider_name,
            'provider_user_id' => $account->provider_user_id,
            'hunt' => $account->hunt_stats,
            'sync_status' => $account->sync_status,
            'sync_error' => $account->sync_error,
            'last_synced_at' => $account->last_synced_at?->toIso8601String(),
        ];
    }
}
