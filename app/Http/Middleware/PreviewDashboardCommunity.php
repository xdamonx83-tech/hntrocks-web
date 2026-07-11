<?php

namespace App\Http\Middleware;

use App\Models\CupTeam;
use App\Models\FeedPost;
use App\Models\LfgPost;
use App\Models\Moment;
use App\Models\QuestProgress;
use App\Models\User;
use App\Support\Hashtag;
use App\Support\HntTheme;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PreviewDashboardCommunity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('admin.theme-preview.shell') || ! $request->boolean('dashboard_community')) {
            return $next($request);
        }

        $user = $request->user();

        abort_unless(
            $user?->isAdmin() && HntTheme::previewActive($user),
            403
        );

        return response()->json([
            'community' => $this->communityPayload($user),
        ]);
    }

    private function communityPayload(User $viewer): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $now = now($timezone);
        $today = $now->copy()->startOfDay();
        $weekStart = $now->copy()->startOfWeek();

        $memberQuery = User::query()->where('status', 'active');
        $totalMembers = (clone $memberQuery)->count();
        $activeToday = (clone $memberQuery)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $today)
            ->count();
        $onlineNow = (clone $memberQuery)
            ->where(function ($query) use ($now, $viewer): void {
                $query->where('last_seen_at', '>=', $now->copy()->subSeconds(User::ONLINE_WINDOW_SECONDS))
                    ->orWhereKey($viewer->id);
            })
            ->count();
        $newThisWeek = (clone $memberQuery)
            ->where('created_at', '>=', $weekStart)
            ->count();

        $openLfgs = LfgPost::query()
            ->where('status', 'open')
            ->where('visibility', 'public')
            ->whereColumn('slots_filled', '<', 'slots_total')
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            })
            ->count();

        $momentsToday = Moment::query()
            ->published()
            ->where(function ($query) use ($today): void {
                $query->where('published_at', '>=', $today)
                    ->orWhere(function ($fallback) use ($today): void {
                        $fallback->whereNull('published_at')
                            ->where('created_at', '>=', $today);
                    });
            })
            ->count();

        $activeCupTeams = CupTeam::query()
            ->where('status', 'active')
            ->whereNull('disqualified_at')
            ->whereHas('cup', function ($query): void {
                $query->visible()->whereIn('status', ['active', 'planned']);
            })
            ->count();

        $activeRate = $totalMembers > 0
            ? min(100, (int) round(($activeToday / $totalMembers) * 100))
            : 0;

        return [
            'total_members' => $totalMembers,
            'active_today' => $activeToday,
            'online_now' => $onlineNow,
            'new_this_week' => $newThisWeek,
            'active_rate' => $activeRate,
            'open_lfgs' => $openLfgs,
            'moments_today' => $momentsToday,
            'active_cup_teams' => $activeCupTeams,
            'activity' => $this->recentActivity($now),
            'hashtags' => $this->trendingHashtags($now->copy()->subDays(30)),
        ];
    }

    private function recentActivity(Carbon $now): array
    {
        $items = collect();

        FeedPost::query()
            ->with('user')
            ->where('status', 'published')
            ->where('visibility', '!=', 'private')
            ->latest()
            ->limit(4)
            ->get()
            ->each(function (FeedPost $post) use ($items): void {
                $items->push($this->activityItem(
                    $post->user,
                    'hat einen Beitrag veröffentlicht',
                    $post->excerpt(72) ?: 'Neuer Community-Beitrag',
                    $post->created_at,
                    route('feed.show', $post)
                ));
            });

        LfgPost::query()
            ->with('user')
            ->where('visibility', 'public')
            ->latest()
            ->limit(4)
            ->get()
            ->each(function (LfgPost $post) use ($items): void {
                $items->push($this->activityItem(
                    $post->user,
                    'hat ein LFG erstellt',
                    Str::limit((string) $post->title, 72),
                    $post->created_at,
                    route('lfg.show', $post)
                ));
            });

        Moment::query()
            ->with('user')
            ->published()
            ->latest('published_at')
            ->latest('id')
            ->limit(4)
            ->get()
            ->each(function (Moment $moment) use ($items): void {
                $items->push($this->activityItem(
                    $moment->user,
                    'hat einen Moment geteilt',
                    Str::limit(trim((string) $moment->caption) ?: 'Neuer HNT Moment', 72),
                    $moment->published_at ?: $moment->created_at,
                    route('moments.show', $moment)
                ));
            });

        QuestProgress::query()
            ->with(['user', 'quest'])
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->limit(4)
            ->get()
            ->each(function (QuestProgress $progress) use ($items): void {
                $questName = $progress->quest
                    ? $progress->quest->displayName(app()->getLocale())
                    : 'Wochenauftrag';

                $items->push($this->activityItem(
                    $progress->user,
                    'hat einen Auftrag abgeschlossen',
                    Str::limit($questName, 72),
                    $progress->completed_at,
                    route('contracts.index')
                ));
            });

        CupTeam::query()
            ->with(['owner', 'cup'])
            ->where('status', 'active')
            ->latest()
            ->limit(4)
            ->get()
            ->each(function (CupTeam $team) use ($items): void {
                $meta = trim((string) $team->name);
                if ($team->cup?->title) {
                    $meta .= ($meta !== '' ? ' · ' : '').$team->cup->title;
                }

                $items->push($this->activityItem(
                    $team->owner,
                    'hat ein Cup-Team erstellt',
                    Str::limit($meta ?: 'Neues Cup-Team', 72),
                    $team->created_at,
                    $team->cup ? route('cups.show', $team->cup) : null
                ));
            });

        User::query()
            ->where('status', 'active')
            ->latest()
            ->limit(4)
            ->get()
            ->each(function (User $user) use ($items): void {
                $items->push($this->activityItem(
                    $user,
                    'ist HNT.ROCKS beigetreten',
                    'Neuer Hunter in der Community',
                    $user->created_at,
                    route('profile.public', $user)
                ));
            });

        return $items
            ->filter(fn (array $item): bool => $item['created_at']->lte($now))
            ->sortByDesc(fn (array $item): int => $item['created_at']->getTimestamp())
            ->take(6)
            ->map(function (array $item): array {
                $date = $item['created_at'];
                unset($item['created_at']);
                $item['time'] = $date->diffForHumans(null, true, true, 1);

                return $item;
            })
            ->values()
            ->all();
    }

    private function trendingHashtags(Carbon $since): array
    {
        $tags = collect();

        FeedPost::query()
            ->where('status', 'published')
            ->where('visibility', '!=', 'private')
            ->where('created_at', '>=', $since)
            ->whereNotNull('body')
            ->latest()
            ->limit(120)
            ->pluck('body')
            ->each(fn (?string $text) => $tags->push(...Hashtag::extract($text)));

        Moment::query()
            ->published()
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(120)
            ->get(['caption', 'description'])
            ->each(function (Moment $moment) use ($tags): void {
                $tags->push(...Hashtag::extract($moment->caption));
                $tags->push(...Hashtag::extract($moment->description));
            });

        LfgPost::query()
            ->where('visibility', 'public')
            ->where('created_at', '>=', $since)
            ->latest()
            ->limit(120)
            ->get(['title', 'body'])
            ->each(function (LfgPost $post) use ($tags): void {
                $tags->push(...Hashtag::extract($post->title));
                $tags->push(...Hashtag::extract($post->body));
            });

        return $tags
            ->filter()
            ->countBy()
            ->sortDesc()
            ->take(6)
            ->map(fn (int $count, string $tag): array => [
                'tag' => $tag,
                'label' => '#'.$tag,
                'count' => $count,
                'url' => route('hashtags.show', $tag),
            ])
            ->values()
            ->all();
    }

    private function activityItem(
        ?User $user,
        string $action,
        string $meta,
        ?Carbon $createdAt,
        ?string $url = null
    ): array {
        $name = trim((string) ($user?->name ?: $user?->username ?: 'HNT.ROCKS'));

        return [
            'title' => Str::limit($name.' '.$action, 84),
            'meta' => Str::limit(trim(strip_tags($meta)), 84),
            'avatar' => $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
            'url' => $url,
            'created_at' => $createdAt ?: now(),
        ];
    }
}
