<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\Guide;
use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\LfgPost;
use App\Models\Moment;
use App\Models\User;
use App\Services\UserBlockService;
use App\Support\ReworkFeedSidebar;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class SearchController extends Controller
{
    public function __construct(private readonly UserBlockService $blocks)
    {
    }

    public function index(Request $request): View
    {
        $viewer = $request->user();
        $query = trim(Str::limit((string) $request->query('q', ''), 80, ''));
        $activeType = (string) $request->query('type', 'all');

        $types = [
            'all' => ['label' => 'Alle', 'icon' => 'ph-squares-four'],
            'players' => ['label' => 'Spieler', 'icon' => 'ph-user'],
            'posts' => ['label' => 'Posts', 'icon' => 'ph-note-pencil'],
            'comments' => ['label' => 'Kommentare', 'icon' => 'ph-chat-circle'],
            'moments' => ['label' => 'Moments', 'icon' => 'ph-play-circle'],
            'lfg' => ['label' => 'LFG', 'icon' => 'ph-crosshair'],
            'maps' => ['label' => 'Maps', 'icon' => 'ph-map-trifold'],
            'cups' => ['label' => 'Cups', 'icon' => 'ph-trophy'],
            'guides' => ['label' => __('guides.title'), 'icon' => 'ph-book-open-text'],
        ];

        if (! array_key_exists($activeType, $types)) {
            $activeType = 'all';
        }

        $results = collect($types)
            ->except('all')
            ->mapWithKeys(fn ($_meta, string $key): array => [$key => collect()]);

        if (mb_strlen($query) >= 2) {
            $like = $this->like($query);

            if ($this->shouldSearch($activeType, 'players')) {
                $results['players'] = $this->players($like, $viewer);
            }

            if ($this->shouldSearch($activeType, 'posts')) {
                $results['posts'] = $this->posts($like, $viewer);
            }

            if ($this->shouldSearch($activeType, 'comments')) {
                $results['comments'] = $this->comments($like, $viewer);
            }

            if ($this->shouldSearch($activeType, 'moments')) {
                $results['moments'] = $this->moments($like);
            }

            if ($this->shouldSearch($activeType, 'lfg')) {
                $results['lfg'] = $this->lfg($like);
            }

            if ($this->shouldSearch($activeType, 'maps')) {
                $results['maps'] = $this->maps($like);
            }

            if ($this->shouldSearch($activeType, 'cups')) {
                $results['cups'] = $this->cups($like);
            }

            if ($this->shouldSearch($activeType, 'guides')) {
                $results['guides'] = $this->guides($like, $viewer);
            }
        }

        $sidebarData = ReworkFeedSidebar::forViewer($viewer);

        return view('themes.rework.search.index', [
            'searchQuery' => $query,
            'activeType' => $activeType,
            'searchTypes' => $types,
            'searchResults' => $results,
            'searchResultTotal' => $results->sum(fn (Collection $items): int => $items->count()),
            'socialiteMembers' => $sidebarData['members'] ?? collect(),
            'socialiteProfileStats' => $sidebarData['profileStats'] ?? [],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'] ?? ['balance' => 0, 'enabled' => false],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'] ?? null,
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'] ?? null,
            'socialiteHighlightCup' => $sidebarData['highlightCup'] ?? null,
        ]);
    }

    private function shouldSearch(string $activeType, string $key): bool
    {
        return $activeType === 'all' || $activeType === $key;
    }

    private function like(string $query): string
    {
        return '%' . addcslashes($query, '\\%_') . '%';
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function whereLike(Builder $query, array $columns, string $like): Builder
    {
        return $query->where(function (Builder $inner) use ($columns, $like): void {
            foreach ($columns as $column) {
                $inner->orWhere($column, 'like', $like);
            }
        });
    }

    private function feedPostVisibility(Builder $query, ?User $viewer): Builder
    {
        return $query->where('status', 'published')
            ->where(function (Builder $visibility) use ($viewer): void {
                $visibility->where('visibility', '!=', 'private');

                if ($viewer) {
                    $visibility->orWhere('user_id', $viewer->id);
                }
            });
    }

    private function players(string $like, User $viewer): Collection
    {
        return $this->blocks->applyToUserQuery(
            User::query()->with('profile'),
            $viewer
        )
            ->where('status', 'active')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->where(function (Builder $query) use ($like): void {
                $query->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhereHas('profile', function (Builder $profile) use ($like): void {
                        $profile->where('headline', 'like', $like)
                            ->orWhere('bio', 'like', $like)
                            ->orWhere('platform', 'like', $like)
                            ->orWhere('region', 'like', $like)
                            ->orWhere('playstyle', 'like', $like);
                    });
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (User $user): array {
                $profile = $user->profile;
                $meta = collect([$profile?->platform, $profile?->region, $profile?->playstyle])->filter()->implode(' · ');

                return [
                    'title' => $user->name ?: $user->username,
                    'subtitle' => trim(($user->username ? '@' . $user->username : 'Hunter') . ($meta ? ' · ' . $meta : '')),
                    'text' => Str::limit(trim((string) ($profile?->headline ?: $profile?->bio ?: 'HNT Hunter')), 150),
                    'url' => route('profile.public', $user),
                    'icon' => 'ph-user',
                    'image' => $user->avatarUrl(),
                ];
            });
    }

    private function posts(string $like, ?User $viewer): Collection
    {
        return FeedPost::query()
            ->with('user.profile')
            ->withCount(['comments', 'reactions'])
            ->tap(fn (Builder $query) => $this->feedPostVisibility($query, $viewer))
            ->where('body', 'like', $like)
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (FeedPost $post): array => [
                'title' => 'Post von ' . ($post->user?->name ?: $post->user?->username ?: 'HNT Hunter'),
                'subtitle' => $post->created_at?->diffForHumans() ?: 'Feed',
                'text' => $post->excerpt(170) ?: 'Feed-Post öffnen',
                'url' => $post->permalink(),
                'icon' => 'ph-note-pencil',
                'image' => $post->user?->avatarUrl(),
                'meta' => [
                    ['icon' => 'ph-heart', 'label' => number_format((int) $post->reactions_count)],
                    ['icon' => 'ph-chat-circle', 'label' => number_format((int) $post->comments_count)],
                ],
            ]);
    }

    private function comments(string $like, ?User $viewer): Collection
    {
        return FeedComment::query()
            ->with(['user.profile', 'post.user.profile'])
            ->where('body', 'like', $like)
            ->whereHas('post', fn (Builder $query) => $this->feedPostVisibility($query, $viewer))
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (FeedComment $comment): array => [
                'title' => 'Kommentar von ' . ($comment->user?->name ?: $comment->user?->username ?: 'HNT Hunter'),
                'subtitle' => 'in Post von ' . ($comment->post?->user?->name ?: $comment->post?->user?->username ?: 'HNT Hunter'),
                'text' => Str::limit(trim(strip_tags((string) $comment->body)), 170),
                'url' => $comment->post?->permalink($comment) ?: route('feed.index'),
                'icon' => 'ph-chat-circle',
                'image' => $comment->user?->avatarUrl(),
            ]);
    }

    private function moments(string $like): Collection
    {
        return Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->published()
            ->where(function (Builder $query) use ($like): void {
                $query->where('caption', 'like', $like)
                    ->orWhere('description', 'like', $like);
            })
            ->latest('published_at')
            ->limit(8)
            ->get()
            ->map(fn (Moment $moment): array => [
                'title' => $moment->caption ?: 'HNT Moment',
                'subtitle' => ($moment->user?->name ?: $moment->user?->username ?: 'HNT Hunter') . ' · ' . ($moment->published_at?->diffForHumans() ?: 'Moment'),
                'text' => Str::limit(trim((string) $moment->description), 170) ?: 'Moment ansehen',
                'url' => route('moments.show', $moment),
                'icon' => 'ph-play-circle',
                'image' => $moment->coverUrl(),
                'meta' => [
                    ['icon' => 'ph-eye', 'label' => number_format((int) $moment->views_count)],
                    ['icon' => 'ph-heart', 'label' => number_format((int) $moment->likes_count)],
                    ['icon' => 'ph-chat-circle', 'label' => number_format((int) $moment->comments_count)],
                ],
            ]);
    }

    private function lfg(string $like): Collection
    {
        return LfgPost::query()
            ->with('user.profile')
            ->where('visibility', 'public')
            ->where(function (Builder $query) use ($like): void {
                $this->whereLike($query, ['title', 'body', 'platform', 'playstyle', 'region', 'language'], $like);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (LfgPost $post): array => [
                'title' => $post->title,
                'subtitle' => collect([$post->statusLabel(), ...$post->displayTags()])->filter()->take(4)->implode(' · '),
                'text' => Str::limit(trim(strip_tags((string) $post->body)), 170),
                'url' => route('lfg.show', $post),
                'icon' => 'ph-crosshair',
                'image' => $post->user?->avatarUrl(),
            ]);
    }

    private function maps(string $like): Collection
    {
        try {
            if (! Schema::hasTable('hnt_maps')) {
                return collect();
            }

            $maps = HntMap::query()
                ->where('is_active', true)
                ->where(function (Builder $query) use ($like): void {
                    $query->where('name', 'like', $like)->orWhere('slug', 'like', $like);
                })
                ->orderBy('sort_order')
                ->limit(4)
                ->get()
                ->map(fn (HntMap $map): array => [
                    'title' => $map->name,
                    'subtitle' => 'Hunt Map',
                    'text' => 'Interaktive Map öffnen',
                    'url' => route('maps.show', $map->slug),
                    'icon' => 'ph-map-trifold',
                    'image' => null,
                ]);

            $markers = collect();

            if (Schema::hasTable('hnt_map_markers')) {
                $markers = HntMapMarker::query()
                    ->with('map')
                    ->where('status', 'approved')
                    ->where(function (Builder $query) use ($like): void {
                        $query->where('label_de', 'like', $like)
                            ->orWhere('label_en', 'like', $like)
                            ->orWhere('type', 'like', $like);
                    })
                    ->orderBy('sort_order')
                    ->limit(6)
                    ->get()
                    ->filter(fn (HntMapMarker $marker): bool => $marker->map !== null)
                    ->map(fn (HntMapMarker $marker): array => [
                        'title' => $marker->label_de ?: $marker->label_en ?: ucfirst((string) $marker->type),
                        'subtitle' => ($marker->map?->name ?: 'Map') . ' · ' . ucfirst((string) $marker->type),
                        'text' => 'Map-Spot öffnen',
                        'url' => route('maps.show', $marker->map?->slug),
                        'icon' => 'ph-map-pin',
                        'image' => null,
                    ]);
            }

            return $maps->concat($markers)->take(8)->values();
        } catch (Throwable) {
            return collect();
        }
    }

    private function cups(string $like): Collection
    {
        return Cup::query()
            ->visible()
            ->where(function (Builder $query) use ($like): void {
                $this->whereLike($query, ['title', 'summary', 'platform', 'region', 'language', 'status'], $like);
            })
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (Cup $cup): array => [
                'title' => $cup->title,
                'subtitle' => collect([$cup->statusLabel(), $cup->platform, $cup->region])->filter()->implode(' · '),
                'text' => Str::limit(trim((string) $cup->summary), 170) ?: 'Cup öffnen',
                'url' => route('cups.show', $cup),
                'icon' => 'ph-trophy',
                'image' => null,
            ]);
    }

    private function guides(string $like, ?User $viewer): Collection
    {
        $blockedIds = $viewer ? $this->blocks->blockedUserIds($viewer) : [];

        return Guide::query()
            ->published()
            ->with(['publishedRevision.category', 'publishedRevision.coverMedia', 'author.profile'])
            ->when($blockedIds !== [], fn (Builder $query) => $query->whereNotIn('author_id', $blockedIds))
            ->where(function (Builder $query) use ($like): void {
                $query->whereHas('publishedRevision', function (Builder $revision) use ($like): void {
                    $revision
                        ->where('title', 'like', $like)
                        ->orWhere('summary', 'like', $like)
                        ->orWhere('tags', 'like', $like)
                        ->orWhereHas('category', fn (Builder $category) => $category
                            ->where('name_de', 'like', $like)
                            ->orWhere('name_en', 'like', $like));
                })->orWhereHas('author', fn (Builder $author) => $author
                    ->where('name', 'like', $like)
                    ->orWhere('username', 'like', $like));
            })
            ->latest('published_at')
            ->limit(8)
            ->get()
            ->map(fn (Guide $guide): array => [
                'title' => $guide->publishedRevision?->title ?: __('guides.title'),
                'subtitle' => collect([
                    $guide->publishedRevision?->category?->label(),
                    $guide->author?->username ? '@'.$guide->author->username : $guide->author?->name,
                ])->filter()->implode(' · '),
                'text' => \Illuminate\Support\Str::limit((string) $guide->publishedRevision?->summary, 170),
                'url' => route('guides.show', $guide),
                'icon' => 'ph-book-open-text',
                'image' => $guide->publishedRevision?->cover_media_id
                    ? route('guides.media.show', $guide->publishedRevision->cover_media_id)
                    : null,
                'meta' => [
                    ['icon' => 'ph-thumbs-up', 'label' => number_format($guide->helpful_count)],
                    ['icon' => 'ph-chat-circle', 'label' => number_format($guide->comments_count)],
                ],
            ]);
    }
}
