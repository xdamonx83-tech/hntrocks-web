<?php

namespace App\Services\Economy;

use App\Models\CrownDailyStreak;
use App\Models\CrownDailyStreakClaim;
use App\Models\CrownTransaction;
use App\Models\CrownWallet;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CrownDailyStreakService
{
    public function enabled(): bool
    {
        return (bool) config('crowns.enabled', true)
            && (bool) data_get($this->definition(), 'enabled', true)
            && $this->tablesReady();
    }

    public function tablesReady(): bool
    {
        return Schema::hasTable('crown_wallets')
            && Schema::hasTable('crown_transactions')
            && Schema::hasTable('crown_daily_streaks')
            && Schema::hasTable('crown_daily_streak_claims');
    }

    public function status(User $user): array
    {
        $today = $this->today();
        $schedule = $this->rewardSchedule();
        $maxDays = $this->maxDays($schedule);

        if (! $this->enabled()) {
            return $this->statusPayload(false, false, false, false, 0, 1, $maxDays, 0, $schedule, null, $today, $today);
        }

        $streak = CrownDailyStreak::query()->where('user_id', $user->id)->first();
        $claimedToday = $this->isSameDate($streak?->last_claimed_on, $today);
        $dismissedToday = $this->isSameDate($streak?->dismissed_on, $today);
        $currentStreak = $streak ? (int) $streak->current_streak : 0;
        $nextStreakDay = $claimedToday ? max(1, $currentStreak) : $this->nextStreakDay($streak, $today, $maxDays);

        return $this->statusPayload(
            true,
            ! $claimedToday,
            $claimedToday,
            $dismissedToday,
            $currentStreak,
            $nextStreakDay,
            $maxDays,
            $this->amountForDay($nextStreakDay, $schedule),
            $schedule,
            $streak?->last_claimed_on,
            $today,
            $today->copy()->addDay()
        );
    }

    public function claim(User $user): array
    {
        $today = $this->today();

        if (! $this->enabled()) {
            return [
                'claimed' => false,
                'already_claimed' => false,
                'amount' => 0,
                'streak_day' => 0,
                'daily_streak' => $this->status($user),
            ];
        }

        return DB::transaction(function () use ($user, $today): array {
            $this->ensureStreakRow($user);

            $streak = CrownDailyStreak::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->isSameDate($streak->last_claimed_on, $today)) {
                return [
                    'claimed' => false,
                    'already_claimed' => true,
                    'amount' => 0,
                    'streak_day' => (int) $streak->current_streak,
                    'daily_streak' => $this->status($user),
                ];
            }

            $existingClaim = CrownDailyStreakClaim::query()
                ->where('user_id', $user->id)
                ->whereDate('claim_date', $today->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existingClaim) {
                return [
                    'claimed' => false,
                    'already_claimed' => true,
                    'amount' => 0,
                    'streak_day' => (int) $existingClaim->streak_day,
                    'daily_streak' => $this->status($user),
                ];
            }

            $schedule = $this->rewardSchedule();
            $maxDays = $this->maxDays($schedule);
            $streakDay = $this->nextStreakDay($streak, $today, $maxDays);
            $amount = $this->amountForDay($streakDay, $schedule);
            $previousClaimDate = $streak->last_claimed_on;
            $wallet = $this->lockedWallet($user);

            $wallet->balance = max(0, (int) $wallet->balance + $amount);
            $wallet->lifetime_earned = max(0, (int) $wallet->lifetime_earned + $amount);
            $wallet->save();

            $transactionData = [
                'user_id' => $user->id,
                'wallet_id' => $wallet->id,
                'type' => CrownTransaction::TYPE_CREDIT,
                'action' => 'daily_login_streak',
                'amount' => $amount,
                'balance_after' => (int) $wallet->balance,
                'description' => 'Tägliche Login-Serie: Tag '.$streakDay,
                'metadata' => [
                    'streak_day' => $streakDay,
                    'streak_index' => max(0, $streakDay - 1),
                    'streak_max' => $maxDays,
                    'claim_date' => $today->toDateString(),
                    'previous_claim_date' => $previousClaimDate ? $this->asDate($previousClaimDate)?->toDateString() : null,
                    'reward_schedule' => $schedule,
                ],
            ];

            if (Schema::hasColumn('crown_transactions', 'collected_at')) {
                $transactionData['collected_at'] = now();
            }

            $transaction = CrownTransaction::query()->create($transactionData);

            $claim = CrownDailyStreakClaim::query()->create([
                'user_id' => $user->id,
                'claim_date' => $today->toDateString(),
                'streak_day' => $streakDay,
                'amount' => $amount,
                'transaction_id' => $transaction->id,
            ]);

            $streak->forceFill([
                'current_streak' => $streakDay,
                'last_claimed_on' => $today->toDateString(),
                'last_claimed_at' => now(),
                'dismissed_on' => null,
                'total_claims' => (int) $streak->total_claims + 1,
                'longest_streak' => max((int) $streak->longest_streak, $streakDay),
            ])->save();

            return [
                'claimed' => true,
                'already_claimed' => false,
                'amount' => (int) $claim->amount,
                'streak_day' => (int) $claim->streak_day,
                'current_streak' => (int) $streak->current_streak,
                'balance' => (int) $wallet->balance,
                'lifetime_earned' => (int) $wallet->lifetime_earned,
                'daily_streak' => $this->status($user),
            ];
        });
    }

    public function dismiss(User $user): array
    {
        if (! $this->enabled()) {
            return $this->status($user);
        }

        $this->ensureStreakRow($user);

        CrownDailyStreak::query()
            ->where('user_id', $user->id)
            ->update(['dismissed_on' => $this->today()->toDateString()]);

        return $this->status($user);
    }

    private function definition(): array
    {
        $definition = config('crowns.rewards.daily_login_streak', []);

        return is_array($definition) ? $definition : [];
    }

    private function rewardSchedule(): array
    {
        $rewards = data_get($this->definition(), 'rewards', [5, 7, 10, 12, 15, 20, 30]);
        $schedule = collect(is_array($rewards) ? $rewards : [])
            ->map(fn ($amount): int => max(0, (int) $amount))
            ->filter(fn (int $amount): bool => $amount > 0)
            ->values()
            ->all();

        return $schedule ?: [5, 7, 10, 12, 15, 20, 30];
    }

    private function maxDays(array $schedule): int
    {
        return max(1, min((int) data_get($this->definition(), 'max_days', count($schedule)), count($schedule)));
    }

    private function amountForDay(int $streakDay, array $schedule): int
    {
        $index = max(0, min(count($schedule) - 1, $streakDay - 1));

        return (int) $schedule[$index];
    }

    private function nextStreakDay(?CrownDailyStreak $streak, Carbon $today, int $maxDays): int
    {
        if (! $streak || ! $streak->last_claimed_on) {
            return 1;
        }

        $lastClaimedOn = $this->asDate($streak->last_claimed_on);

        if (! $lastClaimedOn) {
            return 1;
        }

        if ($lastClaimedOn->isSameDay($today)) {
            return max(1, min((int) $streak->current_streak, $maxDays));
        }

        if ($lastClaimedOn->isSameDay($today->copy()->subDay())) {
            return max(1, min((int) $streak->current_streak + 1, $maxDays));
        }

        return 1;
    }

    private function ensureStreakRow(User $user): void
    {
        $now = now();

        CrownDailyStreak::query()->upsert([[
            'user_id' => $user->id,
            'current_streak' => 0,
            'total_claims' => 0,
            'longest_streak' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['user_id'], ['updated_at']);
    }

    private function lockedWallet(User $user): CrownWallet
    {
        CrownWallet::query()->firstOrCreate(['user_id' => $user->id], [
            'balance' => 0,
            'lifetime_earned' => 0,
            'lifetime_spent' => 0,
        ]);

        return CrownWallet::query()
            ->where('user_id', $user->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function today(): Carbon
    {
        return now((string) config('app.timezone'))->startOfDay();
    }

    private function asDate(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy()->startOfDay();
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->startOfDay();
        }

        return $value ? Carbon::parse($value, (string) config('app.timezone'))->startOfDay() : null;
    }

    private function isSameDate(mixed $value, Carbon $date): bool
    {
        return $this->asDate($value)?->isSameDay($date) ?? false;
    }

    private function statusPayload(
        bool $enabled,
        bool $claimAvailable,
        bool $claimedToday,
        bool $dismissedToday,
        int $currentStreak,
        int $nextStreakDay,
        int $maxDays,
        int $todayAmount,
        array $schedule,
        mixed $lastClaimedOn,
        Carbon $serverDate,
        Carbon $resetsIfNotClaimedBy
    ): array {
        return [
            'enabled' => $enabled,
            'claim_available' => $claimAvailable,
            'claimed_today' => $claimedToday,
            'dismissed_today' => $dismissedToday,
            'current_streak' => $currentStreak,
            'next_streak_day' => $nextStreakDay,
            'max_streak_days' => $maxDays,
            'today_amount' => $todayAmount,
            'reward_schedule' => $schedule,
            'last_claimed_on' => $lastClaimedOn ? $this->asDate($lastClaimedOn)?->toDateString() : null,
            'resets_if_not_claimed_by' => $resetsIfNotClaimedBy->toDateString(),
            'server_date' => $serverDate->toDateString(),
            'copy' => [
                'title_de' => 'Deine tägliche Belohnung!',
                'title_en' => 'Your daily reward!',
                'claim_de' => 'Einsammeln',
                'claim_en' => 'Collect',
                'skip_de' => 'Heute nicht',
                'skip_en' => 'Not today',
            ],
        ];
    }
}
