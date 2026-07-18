<?php

namespace App\Services\Guides;

use App\Models\Guide;
use App\Models\GuideReputationEntry;
use App\Models\User;

class GuideReputationService
{
    public function awardPublication(Guide $guide): void
    {
        GuideReputationEntry::query()->updateOrCreate(
            ['event_key' => "guide:{$guide->id}:published"],
            [
                'user_id' => $guide->author_id,
                'guide_id' => $guide->id,
                'event_type' => 'guide_published',
                'points' => (int) config('guides.reputation.published', 10),
                'description' => 'Published guide',
                'reversed_at' => null,
            ]
        );
    }

    public function awardHelpful(Guide $guide, User $voter): void
    {
        GuideReputationEntry::query()->updateOrCreate(
            ['event_key' => "guide:{$guide->id}:helpful:{$voter->id}"],
            [
                'user_id' => $guide->author_id,
                'guide_id' => $guide->id,
                'event_type' => 'helpful_added',
                'points' => (int) config('guides.reputation.helpful', 1),
                'description' => 'Helpful vote',
                'reversed_at' => null,
            ]
        );
    }

    public function reverseHelpful(Guide $guide, User $voter): void
    {
        GuideReputationEntry::query()
            ->where('event_key', "guide:{$guide->id}:helpful:{$voter->id}")
            ->whereNull('reversed_at')
            ->update(['reversed_at' => now()]);
    }

    public function reverseForArchive(Guide $guide): void
    {
        GuideReputationEntry::query()
            ->where('guide_id', $guide->id)
            ->whereNull('reversed_at')
            ->update(['reversed_at' => now()]);
    }

    public function restoreAfterArchive(Guide $guide): void
    {
        $keys = $guide->helpfulVotes()
            ->pluck('user_id')
            ->map(fn ($userId): string => "guide:{$guide->id}:helpful:{$userId}")
            ->push("guide:{$guide->id}:published")
            ->all();

        GuideReputationEntry::query()
            ->where('guide_id', $guide->id)
            ->whereIn('event_key', $keys)
            ->update(['reversed_at' => null]);
    }

    public function totalFor(User $user): int
    {
        return (int) GuideReputationEntry::query()
            ->where('user_id', $user->id)
            ->whereNull('reversed_at')
            ->sum('points');
    }
}
