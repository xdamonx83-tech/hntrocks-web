<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\FeedPost;
use App\Models\LfgPost;
use App\Models\Moment;
use App\Models\User;
use App\Services\Search\PlayerSearchQuery;
use App\Support\Hashtag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ApiSearchController extends Controller
{
    private const TYPES = ['all', 'players', 'hashtags', 'posts', 'moments', 'lfg', 'cups'];

    public function __invoke(Request $request, PlayerSearchQuery $playerSearch): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'type' => ['nullable', 'string', 'in:'.implode(',', self::TYPES)],
            'sort' => ['nullable', 'string', 'in:relevant,newest,popular,name'],
            'period' => ['nullable', 'string', 'in:any,today,week,month'],
            'platform' => ['nullable', 'string', 'max:40'],
        ]);

        $query = trim((string) ($validated['q'] ?? ''));
        $type = (string) ($validated['type'] ?? 'all');
        $sort = (string) ($validated['sort'] ?? 'relevant');
        $period = (string) ($validated['period'] ?? 'any');
        $platform = trim((string) ($validated['platform'] ?? ''));
        $empty = collect();

        if ($query === '') {
            return response()->json([
                'data' => $this->emptyData(),
                'meta' => compact('query', 'type', 'sort', 'period', 'platform'),
            ]);
        }

        $data = [
            'players' => $this->wants($type, 'players')
                ? $this->players($request, $playerSearch, $query, $platform, $sort)
                : $empty,
            'hashtags' => $this->wants($type, 'hashtags')
                ? $this->hashtags($query, $period)
                : $empty,
            'posts' => $this->wants($type, 'posts')
                ? $this->posts($request, $query, $period, $sort)
                : $empty,
            'moments' => $this->wants($type, 'moments')
                ? $this->moments($query, $period, $sort)
                : $empty,
            'lfg' => $this->wants($type, 'lfg')
                ? $this->lfg($request, $query, $platform, $period, $sort)
                : $empty,
            'cups' => $this->wants($type, 'cups')
                ? $this->cups($query, $platform, $period, $sort)
                : $empty,
        ];

        return response()->json([
            'data' => collect($data)->map(fn (Collection $items) => $items->values())->all(),
            'meta' => compact('query', 'type', 'sort', 'period', 'platform'),
        ]);
    }

    private function players(
        Request $request,
        PlayerSearchQuery $playerSearch,
        string $query,
        string $platform,
        string $sort
    ): Collection {
        $builder = $playerSearch->build($request->user(), $query, $platform);

        match ($sort) {
            'name' => $builder->orderBy('name')->orderBy('username'),
            'newest' => $builder->latest(),
            default => $builder->orderByRaw('username like ? desc', [$query.'%'])->latest(),
        };

        return $builder->limit(10)->get()->map(function (User $user): array {
            $profile = $user->profile;
            $subtitle = collect([
                $profile?->headline,
                $profile?->platform,
                $profile?->region,
                $profile?->is_lfg_available ? 'LFG' : null,
            ])->filter()->implode(' · ');

            return $this->item(
                $user->id,
                'player',
                '@'.$user->username,
                $subtitle !== '' ? $subtitle : $user->name,
                $user->avatarUrl(),
                route('profile.public', $user),
                ['name' => 'profile', 'username' => $user->username]
            );
        });
    }

    private function posts(Request $request, string $query, string $period, string $sort): Collection
    {
        $builder = FeedPost::query()
            ->with(['user.profile', 'team', 'media.mediaAsset'])
            ->withCount(['comments', 'reactions'])
            ->where('status', 'published')
            ->whereNotNull('body')
            ->where('body', 'like', '%'.$query.'%')
            ->where(function (Builder $scope) use ($request): void {
                $scope->where(function (Builder $posts) use ($request): void {
                    $posts->whereNull('team_id')
                        ->where(function (Builder $visibility) use ($request): void {
                            $visibility->whereIn('visibility', ['public', 'followers'])
                                ->orWhere(fn (Builder $private) => $private
                                    ->where('visibility', 'private')
                                    ->where('user_id', $request->user()->id));
                        });
                })->orWhere(function (Builder $teamPosts) use ($request): void {
                    $teamPosts->whereNotNull('team_id')
                        ->whereHas('team', fn (Builder $team) => $team
                            ->where('visibility', '!=', 'private')
                            ->orWhereHas('activeMembers', fn (Builder $member) => $member
                                ->where('user_id', $request->user()->id)));
                });
            });

        $this->applyPeriod($builder, $period);
        $this->applySort($builder, $sort, 'reactions_count');

        return $builder->limit(10)->get()->map(function (FeedPost $post): array {
            $media = $post->media->first();

            return $this->item(
                $post->id,
                'post',
                Str::limit(trim(strip_tags((string) $post->body)), 90),
                '@'.($post->user?->username ?? 'hunter').' · '.$post->reactions_count.' Reaktionen',
                $media?->mediaAsset?->thumbnailUrl() ?: $media?->mediaAsset?->url(),
                route('feed.show', $post),
                ['name' => 'post', 'post_id' => $post->id]
            );
        });
    }

    private function moments(string $query, string $period, string $sort): Collection
    {
        $builder = Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->published()
            ->where(fn (Builder $search) => $search
                ->where('caption', 'like', '%'.$query.'%')
                ->orWhere('description', 'like', '%'.$query.'%'));

        $this->applyPeriod($builder, $period, 'published_at');
        $this->applySort($builder, $sort, 'likes_count', 'caption', 'published_at');

        return $builder->limit(10)->get()->map(fn (Moment $moment): array => $this->item(
            $moment->id,
            'moment',
            trim((string) $moment->caption) ?: 'Moment',
            '@'.($moment->user?->username ?? 'hunter').' · '.$moment->likes_count.' Likes',
            $moment->coverUrl(),
            route('moments.show', $moment),
            ['name' => 'moment', 'moment_id' => $moment->id]
        ));
    }

    private function lfg(
        Request $request,
        string $query,
        string $platform,
        string $period,
        string $sort
    ): Collection {
        $builder = LfgPost::query()
            ->with('user.profile')
            ->withCount('applications')
            ->whereIn('status', ['open', 'full'])
            ->where(fn (Builder $visibility) => $visibility
                ->where('visibility', 'public')
                ->orWhere('user_id', $request->user()->id))
            ->where(fn (Builder $search) => $search
                ->where('title', 'like', '%'.$query.'%')
                ->orWhere('body', 'like', '%'.$query.'%')
                ->orWhere('playstyle', 'like', '%'.$query.'%')
                ->orWhere('region', 'like', '%'.$query.'%'))
            ->when($platform !== '', fn (Builder $filter) => $filter->where('platform', 'like', '%'.$platform.'%'));

        $this->applyPeriod($builder, $period);
        $this->applySort($builder, $sort, 'applications_count', 'title');

        return $builder->limit(10)->get()->map(fn (LfgPost $post): array => $this->item(
            $post->id,
            'lfg',
            $post->title,
            collect([$post->platform, $post->region, $post->playstyle])->filter()->implode(' · '),
            $post->cover_path
                ? Storage::disk('public')->url($post->cover_path)
                : ($post->user?->cover_path ? $post->user->coverUrl() : null),
            route('lfg.show', $post),
            ['name' => 'lfg', 'lfg_id' => $post->id],
            ['status' => $post->status, 'badge' => strtoupper((string) ($post->platform ?: $post->status))]
        ));
    }

    private function cups(string $query, string $platform, string $period, string $sort): Collection
    {
        $builder = Cup::query()
            ->withCount('activeTeams')
            ->where('visibility', 'public')
            ->where(fn (Builder $search) => $search
                ->where('title', 'like', '%'.$query.'%')
                ->orWhere('summary', 'like', '%'.$query.'%')
                ->orWhere('region', 'like', '%'.$query.'%'))
            ->when($platform !== '', fn (Builder $filter) => $filter->where('platform', 'like', '%'.$platform.'%'));

        $this->applyPeriod($builder, $period);
        $this->applySort($builder, $sort, 'active_teams_count', 'title', 'starts_at');

        return $builder->limit(10)->get()->map(fn (Cup $cup): array => $this->item(
            $cup->id,
            'cup',
            $cup->title,
            collect([$cup->platform, $cup->statusLabel()])->filter()->implode(' · '),
            $cup->cover_path ? $cup->coverUrl() : null,
            route('cups.show', $cup),
            ['name' => 'cup', 'slug' => $cup->slug],
            ['status' => $cup->status, 'badge' => strtoupper($cup->status)]
        ));
    }

    private function hashtags(string $query, string $period): Collection
    {
        $needle = Hashtag::normalize($query) ?? '';
        $like = '%#%'.$needle.'%';
        $tags = collect();

        $postQuery = FeedPost::query()->where('status', 'published')->whereIn('visibility', ['public', 'followers'])
            ->whereNotNull('body')->where('body', 'like', $like);
        $momentQuery = Moment::query()->published()->where(fn (Builder $search) => $search
            ->where('caption', 'like', $like)->orWhere('description', 'like', $like));
        $lfgQuery = LfgPost::query()->where('visibility', 'public')->whereIn('status', ['open', 'full'])
            ->where(fn (Builder $search) => $search->where('title', 'like', $like)->orWhere('body', 'like', $like));

        $this->applyPeriod($postQuery, $period);
        $this->applyPeriod($momentQuery, $period, 'published_at');
        $this->applyPeriod($lfgQuery, $period);

        $postQuery->limit(50)->pluck('body')->each(fn ($text) => $tags->push(...Hashtag::extract($text)));
        $momentQuery->limit(50)->get(['caption', 'description'])->each(
            fn (Moment $moment) => $tags->push(...Hashtag::extract($moment->caption), ...Hashtag::extract($moment->description))
        );
        $lfgQuery->limit(50)->get(['title', 'body'])->each(
            fn (LfgPost $post) => $tags->push(...Hashtag::extract($post->title), ...Hashtag::extract($post->body))
        );

        return $tags->filter(fn (string $tag) => $needle === '' || str_contains($tag, $needle))
            ->countBy()
            ->sortDesc()
            ->take(10)
            ->map(fn (int $count, string $tag): array => $this->item(
                $tag,
                'hashtag',
                '#'.$tag,
                $count.' Inhalte',
                null,
                route('hashtags.show', $tag),
                ['name' => 'hashtag', 'tag' => $tag],
                ['count' => $count]
            ))
            ->values();
    }

    private function applyPeriod(Builder $query, string $period, string $column = 'created_at'): void
    {
        $from = match ($period) {
            'today' => now()->startOfDay(),
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            default => null,
        };

        if ($from) {
            $query->where($column, '>=', $from);
        }
    }

    private function applySort(
        Builder $query,
        string $sort,
        string $popularColumn,
        string $nameColumn = 'id',
        string $dateColumn = 'created_at'
    ): void {
        match ($sort) {
            'popular' => $query->orderByDesc($popularColumn)->orderByDesc($dateColumn),
            'name' => $query->orderBy($nameColumn)->orderByDesc($dateColumn),
            default => $query->orderByDesc($dateColumn)->orderByDesc('id'),
        };
    }

    private function item(
        int|string $id,
        string $type,
        string $title,
        string $subtitle,
        ?string $imageUrl,
        ?string $targetUrl,
        array $routeData,
        array $extra = []
    ): array {
        return array_merge([
            'id' => $id,
            'type' => $type,
            'title' => $title,
            'subtitle' => $subtitle,
            'image_url' => $imageUrl,
            'target_url' => $targetUrl,
            'route_data' => $routeData,
        ], $extra);
    }

    private function wants(string $requested, string $type): bool
    {
        return $requested === 'all' || $requested === $type;
    }

    private function emptyData(): array
    {
        return array_fill_keys(array_slice(self::TYPES, 1), []);
    }
}
