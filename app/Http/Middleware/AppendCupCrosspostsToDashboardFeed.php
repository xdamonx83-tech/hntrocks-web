<?php

namespace App\Http\Middleware;

use App\Models\FeedCupCard;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AppendCupCrosspostsToDashboardFeed
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->boolean('data')
            || ! $response instanceof JsonResponse
            || ! Schema::hasTable('feed_cup_cards')) {
            return $response;
        }

        $payload = $response->getData(true);
        $postIds = collect($payload['posts'] ?? [])
            ->pluck('id')
            ->push(data_get($payload, 'post.id'))
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($postIds->isEmpty()) {
            return $response;
        }

        $cards = FeedCupCard::query()
            ->whereIn('feed_post_id', $postIds->all())
            ->with(['cup.owner'])
            ->get()
            ->filter(fn (FeedCupCard $card): bool => $card->cup?->visibility === 'public')
            ->keyBy('feed_post_id');

        if (isset($payload['posts']) && is_array($payload['posts'])) {
            $payload['posts'] = array_map(
                fn (array $post): array => $this->appendCard($post, $cards->get((int) ($post['id'] ?? 0))),
                $payload['posts']
            );
        }

        if (isset($payload['post']) && is_array($payload['post'])) {
            $payload['post'] = $this->appendCard(
                $payload['post'],
                $cards->get((int) ($payload['post']['id'] ?? 0))
            );
        }

        $response->setData($payload);

        return $response;
    }

    private function appendCard(array $post, ?FeedCupCard $card): array
    {
        $cup = $card?->cup;
        if (! $cup) {
            return $post;
        }

        $teamSize = max(1, min(3, (int) ($cup->team_size ?: 1)));
        $mode = match ($teamSize) {
            1 => __('hnt_cup_crosspost.solo'),
            2 => __('hnt_cup_crosspost.duo'),
            3 => __('hnt_cup_crosspost.trio'),
        };

        $platforms = collect($cup->allowedPlatforms())
            ->map(fn (string $platform): string => match ($platform) {
                'PlayStation' => 'PS5',
                default => $platform,
            })
            ->implode(' / ');

        $post['badge'] = __('hnt_cup_crosspost.cup');
        $post['badge_class'] = 'cup';
        $post['cup_crosspost'] = [
            'kicker' => __('hnt_cup_crosspost.kicker'),
            'title' => $cup->title,
            'mode' => $mode,
            'platforms' => $platforms !== '' ? $platforms : __('hnt_cup_crosspost.all_platforms'),
            'date' => $cup->starts_at?->translatedFormat('d.m.') ?: __('hnt_cup_crosspost.date_open'),
            'details_label' => __('hnt_cup_crosspost.details'),
            'details_url' => route('cups.show', $cup),
        ];

        return $post;
    }
}
