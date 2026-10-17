<?php

namespace App\Services;

use App\Models\CrownInventoryItem;
use App\Models\CrownShopItem;
use App\Models\HunterPrestigeHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class HunterPrestigeService
{
    public const MAX_LEVEL = 50;

    public const CYCLE_CAP = 12250;

    // Purely cosmetic items. Moment Studio feature unlocks must not be granted
    // by a random prize.
    public const COSMETIC_TYPES = [
        'avatar_frame',
        'profile_banner',
        'username_effect',
        'title',
        'moment_overlay',
    ];

    public function availableCosmetics(User $user): int
    {
        return $this->cosmeticCandidates($user)->count();
    }

    public function cosmeticCandidates(User $user)
    {
        return CrownShopItem::query()
            ->available()
            ->whereIn('type', self::COSMETIC_TYPES)
            ->whereNotIn('id', CrownInventoryItem::query()
                ->select('shop_item_id')->where('user_id', $user->id));
    }

    public function status(User $user): array
    {
        $level = min(self::MAX_LEVEL, max(1, (int) $user->level));
        $choices = $level >= self::MAX_LEVEL;
        return [
            'prestige' => (int) $user->prestige,
            'level' => $level,
            'max_level' => self::MAX_LEVEL,
            'career_xp' => (int) $user->xp_total,
            'cycle_xp' => (int) $user->xp_cycle,
            'cycle_cap' => self::CYCLE_CAP,
            'xp_boost_percent' => $user->prestige_xp_boost ? 10 : 0,
            'can_prestige' => $choices,
            'cosmetics_available' => $choices ? $this->availableCosmetics($user) : null,
        ];
    }

    public function start(User $user, string $reward): HunterPrestigeHistory
    {
        if (! in_array($reward, ['xp', 'cosmetic'], true)) {
            throw ValidationException::withMessages(['reward' => __('prestige.invalid_reward')]);
        }

        return DB::transaction(function () use ($user, $reward): HunterPrestigeHistory {
            // Serialize consecutive requests by the same user. The history unique
            // index provides an additional safeguard against duplicate rewards.
            $locked = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ((int) $locked->level < self::MAX_LEVEL) {
                throw ValidationException::withMessages([
                    'prestige' => __('prestige.required_level'),
                ]);
            }

            $cosmetic = null;
            if ($reward === 'cosmetic') {
                $cosmetic = $this->cosmeticCandidates($locked)->inRandomOrder()->first();
                if (! $cosmetic) {
                    throw ValidationException::withMessages([
                        'reward' => __('prestige.cosmetics_complete'),
                    ]);
                }

                CrownInventoryItem::query()->create([
                    'user_id' => $locked->id,
                    'shop_item_id' => $cosmetic->id,
                    'purchased_at' => null,
                    'metadata' => [
                        'source' => 'prestige',
                        'prestige_number' => (int) $locked->prestige + 1,
                    ],
                ]);
            }

            $history = HunterPrestigeHistory::query()->create([
                'user_id' => $locked->id,
                'prestige_number' => (int) $locked->prestige + 1,
                'reward_type' => $reward,
                'shop_item_id' => $cosmetic?->id,
                'career_xp_at_prestige' => (int) $locked->xp_total,
                'cycle_xp_at_prestige' => (int) $locked->xp_cycle,
            ]);

            $locked->prestige = $history->prestige_number;
            $locked->xp_cycle = 0;
            $locked->level = 1;
            $locked->prestige_xp_boost = $reward === 'xp';
            $locked->prestige_xp_bonus_remainder = 0;
            $locked->save();

            return $history;
        }, 3);
    }
}
