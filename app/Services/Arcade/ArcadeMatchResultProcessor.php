<?php

namespace App\Services\Arcade;

use App\Enums\Arcade\ArcadeMatchMode;
use App\Enums\Arcade\ArcadeMatchPlayerResult;
use App\Enums\Arcade\ArcadeMatchStatus;
use App\Models\Arcade\ArcadeMatch;
use App\Models\Arcade\ArcadeMatchFinalization;
use App\Models\Arcade\ArcadeMatchReward;
use App\Models\Arcade\ArcadeUserStat;
use App\Services\Economy\CrownsService;
use Illuminate\Support\Facades\DB;

class ArcadeMatchResultProcessor
{
    public function __construct(private readonly CrownsService $crowns)
    {
    }

    public function process(ArcadeMatch $match): bool
    {
        return DB::transaction(function () use ($match): bool {
            $match = ArcadeMatch::query()
                ->with(['game', 'players.user'])
                ->lockForUpdate()
                ->findOrFail($match->id);

            if ($match->status !== ArcadeMatchStatus::Finished || $match->finished_at === null) {
                return false;
            }

            $inserted = DB::table('arcade_match_finalizations')->insertOrIgnore([
                'match_id' => $match->id,
                'processed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($inserted === 0) {
                return false;
            }

            foreach ($match->players as $player) {
                if (! $player->user_id || ! $player->user) {
                    continue;
                }

                $result = $player->result;
                if (! in_array($result, [
                    ArcadeMatchPlayerResult::Win,
                    ArcadeMatchPlayerResult::Loss,
                    ArcadeMatchPlayerResult::Draw,
                ], true)) {
                    continue;
                }

                $this->incrementStats($match, (int) $player->user_id, $result);

                if ($match->mode === ArcadeMatchMode::Ranked) {
                    $this->rewardRankedResult($match, $player->user, $result);
                }
            }

            return true;
        });
    }

    private function incrementStats(ArcadeMatch $match, int $userId, ArcadeMatchPlayerResult $result): void
    {
        DB::table('arcade_user_stats')->insertOrIgnore([
            'user_id' => $userId,
            'game_id' => $match->game_id,
            'mode' => $match->mode->value,
            'matches_played' => 0,
            'wins' => 0,
            'losses' => 0,
            'draws' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stat = ArcadeUserStat::query()
            ->where('user_id', $userId)
            ->where('game_id', $match->game_id)
            ->where('mode', $match->mode->value)
            ->lockForUpdate()
            ->firstOrFail();

        $stat->matches_played++;

        match ($result) {
            ArcadeMatchPlayerResult::Win => $stat->wins++,
            ArcadeMatchPlayerResult::Loss => $stat->losses++,
            ArcadeMatchPlayerResult::Draw => $stat->draws++,
            default => null,
        };

        $stat->save();
    }

    private function rewardRankedResult(ArcadeMatch $match, $user, ArcadeMatchPlayerResult $result): void
    {
        $settings = $match->game->rankedRewardSettings();
        if (! $settings['ranked_reward_enabled'] || ! $this->crowns->enabled()) {
            return;
        }

        [$rewardType, $amount] = match ($result) {
            ArcadeMatchPlayerResult::Win => ['win', $settings['ranked_win_reward']],
            ArcadeMatchPlayerResult::Draw => ['draw', $settings['ranked_draw_reward']],
            ArcadeMatchPlayerResult::Loss => ['loss', $settings['ranked_loss_reward']],
            default => [null, 0],
        };

        if ($rewardType === null || $amount <= 0) {
            return;
        }

        $inserted = DB::table('arcade_match_rewards')->insertOrIgnore([
            'match_id' => $match->id,
            'user_id' => $user->id,
            'reward_type' => $rewardType,
            'amount' => $amount,
            'crown_transaction_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($inserted === 0) {
            return;
        }

        $transaction = $this->crowns->reward(
            $user,
            'arcade_ranked_' . $rewardType,
            source: $match,
            amount: $amount,
            description: 'Arcade Ranked ' . ucfirst($rewardType),
            metadata: [
                'game_key' => $match->game->key,
                'match_id' => $match->id,
                'mode' => ArcadeMatchMode::Ranked->value,
                'result' => $result->value,
            ],
            oncePerSource: true,
        );

        if ($transaction) {
            ArcadeMatchReward::query()
                ->where('match_id', $match->id)
                ->where('user_id', $user->id)
                ->where('reward_type', $rewardType)
                ->update(['crown_transaction_id' => $transaction->id]);
        }
    }
}
