<?php

namespace App\Services;

use App\Models\Giveaway;
use App\Models\GiveawayEntry;
use App\Models\ReferralLink;
use App\Models\ReferralSignup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReferralService
{
    public function linkFor(User $user): ReferralLink
    {
        return ReferralLink::firstOrCreate(
            ['user_id' => $user->id],
            ['code' => $this->makeUniqueCode()]
        );
    }

    public function trackClick(string $code): ?ReferralLink
    {
        $link = ReferralLink::query()->where('code', $code)->first();

        if (! $link) {
            return null;
        }

        $link->increment('clicks_count');
        $link->forceFill(['last_clicked_at' => now()])->save();

        return $link;
    }

    public function attachSignup(User $newUser, ?string $code, array $metadata = []): ?ReferralSignup
    {
        if (! $code) {
            $this->syncGiveawayEntries($newUser);
            return null;
        }

        $link = ReferralLink::query()->where('code', $code)->first();

        if (! $link || (int) $link->user_id === (int) $newUser->id) {
            $this->syncGiveawayEntries($newUser);
            return null;
        }

        return DB::transaction(function () use ($newUser, $link, $code, $metadata): ReferralSignup {
            $signup = ReferralSignup::updateOrCreate(
                ['referred_user_id' => $newUser->id],
                [
                    'referral_link_id' => $link->id,
                    'referrer_id' => $link->user_id,
                    'referral_code' => $code,
                    'registered_at' => now(),
                    'status' => 'registered',
                    'metadata' => $metadata ?: null,
                ]
            );

            $link->forceFill([
                'signups_count' => $link->signups()->whereNotNull('referred_user_id')->count(),
                'completed_profiles_count' => $link->signups()->whereNotNull('profile_completed_at')->count(),
            ])->save();

            if ($link->user) {
                app(GamificationService::class)->award($link->user, 'referral_signup', source: $signup, description: 'Nutzer über Referral registriert');
                app(NotificationService::class)->send($link->user, $newUser, 'referral_signup', 'Neue Registrierung über deinen Referral-Link', $newUser->name.' hat sich über deinen Link registriert.', route('referrals.index'));
            }

            $this->syncGiveawayEntries($newUser);
            $this->syncGiveawayEntries($link->user);

            return $signup;
        });
    }

    public function syncProfileCompletion(User $user): void
    {
        $user->loadMissing('profile');

        $signup = ReferralSignup::query()
            ->where('referred_user_id', $user->id)
            ->first();

        if ($signup && ! $signup->profile_completed_at && \App\Support\ProfileCompletion::score($user) >= 100) {
            $signup->forceFill([
                'profile_completed_at' => now(),
                'status' => 'profile_completed',
            ])->save();

            $signup->link?->forceFill([
                'completed_profiles_count' => $signup->link->signups()->whereNotNull('profile_completed_at')->count(),
            ])->save();

            if ($signup->referrer) {
                app(GamificationService::class)->award($signup->referrer, 'referral_profile_completed', source: $signup, description: 'Referral-Profil vervollständigt');
                app(NotificationService::class)->send($signup->referrer, $user, 'referral_profile_completed', 'Referral erfolgreich', $user->name.' hat das Profil vollständig gepflegt. Deine Gewinnspiel-Bonus-Chance wurde aktualisiert.', route('referrals.index'));
                $this->syncGiveawayEntries($signup->referrer);
            }
        }

        $this->syncGiveawayEntries($user);
    }

    public function syncGiveawayEntries(User $user): void
    {
        $giveaways = Giveaway::query()
            ->where('status', 'active')
            ->get()
            ->filter(fn (Giveaway $giveaway): bool => $giveaway->isActive());

        foreach ($giveaways as $giveaway) {
            $this->syncUserForGiveaway($user, $giveaway);
        }
    }

    public function entrySummary(User $user, ?Giveaway $giveaway = null): array
    {
        $giveaway ??= $this->activeGiveaway();

        if (! $giveaway) {
            return [
                'giveaway' => null,
                'total' => 0,
                'base' => 0,
                'profile' => 0,
                'referral' => 0,
                'entries' => collect(),
            ];
        }

        $this->syncUserForGiveaway($user, $giveaway);

        $entries = GiveawayEntry::query()
            ->where('giveaway_id', $giveaway->id)
            ->where('user_id', $user->id)
            ->latest('awarded_at')
            ->get();

        return [
            'giveaway' => $giveaway,
            'total' => (int) $entries->sum('entries'),
            'base' => (int) $entries->where('source', 'registration')->sum('entries'),
            'profile' => (int) $entries->where('source', 'profile_complete')->sum('entries'),
            'referral' => (int) $entries->where('source', 'referral_profile_complete')->sum('entries'),
            'entries' => $entries,
        ];
    }

    public function activeGiveaway(): ?Giveaway
    {
        return Giveaway::query()
            ->where('status', 'active')
            ->orderByDesc('starts_at')
            ->get()
            ->first(fn (Giveaway $giveaway): bool => $giveaway->isActive());
    }

    private function syncUserForGiveaway(User $user, Giveaway $giveaway): void
    {
        GiveawayEntry::firstOrCreate(
            [
                'giveaway_id' => $giveaway->id,
                'user_id' => $user->id,
                'source' => 'registration',
                'source_type' => User::class,
                'source_id' => $user->id,
            ],
            [
                'entries' => max(1, (int) $giveaway->base_entries),
                'reason' => 'Basis-Chance für Registrierung',
                'awarded_at' => now(),
                'metadata' => ['profile_completion' => \App\Support\ProfileCompletion::score($user)],
            ]
        );

        $user->loadMissing('profile');
        if (\App\Support\ProfileCompletion::score($user) >= (int) $giveaway->profile_completion_required) {
            GiveawayEntry::firstOrCreate(
                [
                    'giveaway_id' => $giveaway->id,
                    'user_id' => $user->id,
                    'source' => 'profile_complete',
                    'source_type' => User::class,
                    'source_id' => $user->id,
                ],
                [
                    'entries' => max(0, (int) $giveaway->profile_bonus_entries),
                    'reason' => 'Bonus-Chance für vollständiges Profil',
                    'awarded_at' => now(),
                    'metadata' => ['profile_completion' => \App\Support\ProfileCompletion::score($user)],
                ]
            );
        }

        $successfulReferrals = ReferralSignup::query()
            ->where('referrer_id', $user->id)
            ->whereNotNull('profile_completed_at')
            ->oldest('profile_completed_at')
            ->limit((int) $giveaway->max_referral_bonus_entries)
            ->get();

        foreach ($successfulReferrals as $signup) {
            GiveawayEntry::firstOrCreate(
                [
                    'giveaway_id' => $giveaway->id,
                    'user_id' => $user->id,
                    'source' => 'referral_profile_complete',
                    'source_type' => ReferralSignup::class,
                    'source_id' => $signup->id,
                ],
                [
                    'entries' => max(0, (int) $giveaway->referral_bonus_entries),
                    'reason' => 'Bonus-Chance für erfolgreichen Invite',
                    'awarded_at' => now(),
                    'metadata' => ['referred_user_id' => $signup->referred_user_id],
                ]
            );
        }
    }

    private function makeUniqueCode(): string
    {
        do {
            $code = strtolower(Str::random(10));
        } while (ReferralLink::query()->where('code', $code)->exists());

        return $code;
    }
}
