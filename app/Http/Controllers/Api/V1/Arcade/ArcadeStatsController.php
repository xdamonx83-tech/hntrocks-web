<?php

namespace App\Http\Controllers\Api\V1\Arcade;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Http\Controllers\Controller;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeUserStat;
use App\Services\Arcade\ArcadeGameCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ArcadeStatsController extends Controller
{
    public function __construct(private readonly ArcadeGameCatalogService $catalog)
    {
    }

    public function leaderboard(Request $request, string $game): JsonResponse
    {
        $arcadeGame = $this->catalog->visibleGame($game);
        abort_unless($arcadeGame->ranked_enabled, 404);

        $limit = max(1, min(100, (int) $request->integer('limit', 50)));

        $stats = ArcadeUserStat::query()
            ->with('user')
            ->where('game_id', $arcadeGame->id)
            ->where('mode', ArcadeMatchMode::Ranked->value)
            ->where('matches_played', '>', 0)
            ->whereHas('user')
            ->orderByDesc('wins')
            ->orderByRaw('(wins * 1.0 / NULLIF(matches_played, 0)) DESC')
            ->orderByDesc('matches_played')
            ->orderBy('user_id')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => $stats->values()->map(function (ArcadeUserStat $stat, int $index): array {
                $user = $stat->user;
                $avatar = $user->avatarUrl();

                return [
                    'rank' => $index + 1,
                    'user' => [
                        'id' => $user->id,
                        'username' => $user->username,
                        'name' => $user->name,
                        'avatar_url' => $avatar,
                    ],
                    'avatar' => $avatar,
                    'matches_played' => $stat->matches_played,
                    'wins' => $stat->wins,
                    'losses' => $stat->losses,
                    'draws' => $stat->draws,
                    'win_rate' => $stat->winRate(),
                ];
            }),
            'meta' => [
                'game_key' => $arcadeGame->key,
                'mode' => ArcadeMatchMode::Ranked->value,
                'limit' => $limit,
            ],
        ]);
    }

    public function mine(Request $request, string $game): JsonResponse
    {
        $user = $request->user();
        abort_unless($user, 401);

        $arcadeGame = ArcadeGame::query()->where('key', $game)->firstOrFail();
        $stats = ArcadeUserStat::query()
            ->where('user_id', $user->id)
            ->where('game_id', $arcadeGame->id)
            ->get()
            ->keyBy(fn (ArcadeUserStat $stat): string => $stat->mode->value);

        return response()->json([
            'data' => [
                'game_key' => $arcadeGame->key,
                'ranked' => $this->statsPayload($stats->get(ArcadeMatchMode::Ranked->value)),
                'casual' => $this->statsPayload($stats->get(ArcadeMatchMode::Casual->value)),
            ],
        ]);
    }

    private function statsPayload(?ArcadeUserStat $stat): array
    {
        return [
            'matches_played' => $stat?->matches_played ?? 0,
            'wins' => $stat?->wins ?? 0,
            'losses' => $stat?->losses ?? 0,
            'draws' => $stat?->draws ?? 0,
            'win_rate' => $stat?->winRate() ?? 0.0,
        ];
    }
}
