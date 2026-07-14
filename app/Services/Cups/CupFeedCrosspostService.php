<?php

namespace App\Services\Cups;

use App\Models\Cup;
use App\Models\FeedCupCard;
use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CupFeedCrosspostService
{
    public const AUTHOR_USER_ID = 2;

    public function sync(Cup $cup): ?FeedPost
    {
        if (! Schema::hasTable('feed_cup_cards')) {
            return null;
        }

        if ($cup->visibility !== 'public') {
            $this->remove($cup);

            return null;
        }

        return $this->publish($cup);
    }

    public function publish(Cup $cup): ?FeedPost
    {
        if (! Schema::hasTable('feed_cup_cards') || $cup->visibility !== 'public') {
            return null;
        }

        $author = User::query()->find(self::AUTHOR_USER_ID);
        if (! $author) {
            Log::warning('Cup feed crosspost author is missing.', [
                'cup_id' => $cup->id,
                'user_id' => self::AUTHOR_USER_ID,
            ]);

            return null;
        }

        return DB::transaction(function () use ($cup, $author): ?FeedPost {
            $existing = FeedCupCard::query()
                ->where('cup_id', $cup->id)
                ->with('feedPost')
                ->lockForUpdate()
                ->first();

            if ($existing?->feedPost) {
                return $existing->feedPost;
            }

            $cup->loadMissing('owner');

            $post = FeedPost::query()->create([
                'user_id' => $author->id,
                'body' => $this->postBody($cup),
                'source_language' => 'de',
                'visibility' => 'public',
                'status' => 'published',
            ]);

            FeedCupCard::query()->create([
                'feed_post_id' => $post->id,
                'cup_id' => $cup->id,
                'published_by_user_id' => $author->id,
            ]);

            return $post;
        });
    }

    public function remove(Cup $cup): void
    {
        if (! Schema::hasTable('feed_cup_cards')) {
            return;
        }

        DB::transaction(function () use ($cup): void {
            $card = FeedCupCard::query()
                ->where('cup_id', $cup->id)
                ->with('feedPost')
                ->lockForUpdate()
                ->first();

            if (! $card) {
                return;
            }

            $card->feedPost?->delete();
            $card->delete();
        });
    }

    private function postBody(Cup $cup): string
    {
        $owner = $cup->owner;
        $organizer = $owner?->username
            ? '@'.$owner->username
            : ($owner?->name ?: 'der Community');

        $mode = match ((int) $cup->team_size) {
            1 => 'Solo',
            2 => 'Duo',
            3 => 'Trio',
            default => 'Community Cup',
        };

        $platforms = collect($cup->allowedPlatforms())
            ->map(fn (string $platform): string => $platform === 'PlayStation' ? 'PlayStation' : $platform)
            ->implode(' / ');
        $platforms = $platforms !== '' ? $platforms : 'alle Plattformen';

        if ($cup->isRegistrationOpen()) {
            return sprintf(
                'Die Anmeldung für den Community Cup „%s“ ist offen. %s, %s. Veranstaltet von %s. Regeln, Wertung und Preise findest du in den Details.',
                $cup->title,
                $mode,
                $platforms,
                $organizer
            );
        }

        if ($cup->registration_opens_at?->isFuture()) {
            return sprintf(
                'Der Community Cup „%s“ wurde angekündigt. Die Anmeldung startet am %s. %s, %s. Veranstaltet von %s.',
                $cup->title,
                $cup->registration_opens_at->format('d.m.Y'),
                $mode,
                $platforms,
                $organizer
            );
        }

        return sprintf(
            'Ein neuer Community Cup ist da: „%s“. %s, %s. Veranstaltet von %s. Alle Informationen findest du in den Details.',
            $cup->title,
            $mode,
            $platforms,
            $organizer
        );
    }
}
