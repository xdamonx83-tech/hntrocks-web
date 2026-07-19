<?php

namespace App\Support;

use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\Report;
use App\Models\Team;
use App\Models\TeamContract;
use App\Models\TeamMember;
use App\Models\TeamSession;
use App\Models\TeamSessionResponse;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class TeamDetailDashboardData
{
    public function build(Team $team, User $viewer): array
    {
        $team->loadMissing([
            'owner.profile',
            'owner.crownWallet',
            'members.user.profile',
            'members.user.crownWallet',
            'progression',
        ]);

        $team->loadCount([
            'activeMembers as members_count',
            'pendingMembers as pending_count',
            'feedPosts as posts_count' => fn ($query) => $query->where('status', 'published'),
            'sessions as sessions_count',
        ]);

        $activeMembers = $team->members
            ->where('status', 'active')
            ->sortBy(fn (TeamMember $member): string => sprintf(
                '%d-%020d',
                match ($member->role) {
                    'owner' => 0,
                    'officer' => 1,
                    default => 2,
                },
                PHP_INT_MAX - (int) optional($member->joined_at)->timestamp
            ))
            ->values();

        $pendingMembers = $team->members
            ->where('status', 'pending')
            ->sortByDesc(fn (TeamMember $member) => optional($member->created_at)->timestamp ?? 0)
            ->values();

        $activeMembers->each(function (TeamMember $member): void {
            $member->user?->loadMissing(['profile', 'crownWallet']);
            $member->user?->loadCount([
                'feedPosts as published_posts_count' => fn ($query) => $query->where('status', 'published'),
            ]);
        });

        $pendingMembers->each(function (TeamMember $member): void {
            $member->user?->loadMissing(['profile', 'crownWallet']);
        });

        $posts = $team->feedPosts()
            ->with([
                'user.profile',
                'media.mediaAsset',
                'poll.options.votes',
                'poll.votes',
                'viewerReaction',
                'viewerBookmark',
            ])
            ->withCount(['comments', 'reactions', 'bookmarks', 'sharedByPosts as shares_count'])
            ->where('status', 'published')
            ->orderByDesc('is_pinned')
            ->orderByDesc('pinned_at')
            ->latest()
            ->take(12)
            ->get();

        $postIds = $posts->pluck('id')->map(fn ($id) => (int) $id)->filter()->values();
        $commentIds = FeedComment::query()
            ->whereIn('feed_post_id', $postIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();

        $reportedFeedKeys = ($postIds->isEmpty() && $commentIds->isEmpty())
            ? collect()
            : Report::query()
                ->where('reporter_id', $viewer->id)
                ->whereIn('status', ['open', 'in_review'])
                ->where(function ($query) use ($postIds, $commentIds): void {
                    if ($postIds->isNotEmpty()) {
                        $query->orWhere(function ($postQuery) use ($postIds): void {
                            $postQuery->where('reportable_type', FeedPost::class)
                                ->whereIn('reportable_id', $postIds->all());
                        });
                    }

                    if ($commentIds->isNotEmpty()) {
                        $query->orWhere(function ($commentQuery) use ($commentIds): void {
                            $commentQuery->where('reportable_type', FeedComment::class)
                                ->whereIn('reportable_id', $commentIds->all());
                        });
                    }
                })
                ->get(['reportable_type', 'reportable_id'])
                ->mapWithKeys(fn (Report $report): array => [
                    ($report->reportable_type === FeedComment::class ? 'feed_comment:' : 'feed_post:')
                        . (int) $report->reportable_id => true,
                ]);

        $upcomingSession = $team->sessions()
            ->with(['creator.profile', 'responses.user.profile'])
            ->whereIn('status', [TeamSession::STATUS_SCHEDULED, TeamSession::STATUS_ACTIVE])
            ->where('starts_at', '>=', now()->subHours(4))
            ->orderBy('starts_at')
            ->first();

        $recentSessions = $team->sessions()
            ->with(['creator.profile', 'responses.user.profile'])
            ->latest('starts_at')
            ->take(6)
            ->get();

        $activeContracts = $team->contracts()
            ->where('status', TeamContract::STATUS_ACTIVE)
            ->orderByRaw('CASE WHEN ends_at IS NULL THEN 1 ELSE 0 END')
            ->orderBy('ends_at')
            ->latest('created_at')
            ->take(4)
            ->get();

        $postsThisWeek = $team->feedPosts()
            ->where('status', 'published')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();

        $sessionsThisWeek = $team->sessions()
            ->whereBetween('starts_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $completedSessionsThisWeek = $team->sessions()
            ->where('status', TeamSession::STATUS_COMPLETED)
            ->where('completed_at', '>=', now()->startOfWeek())
            ->count();

        $onlineCutoff = now()->subSeconds(max(User::ONLINE_WINDOW_SECONDS, 180));
        $onlineMembers = $activeMembers
            ->filter(fn (TeamMember $member): bool => (bool) $member->user?->last_seen_at?->gte($onlineCutoff))
            ->values();

        $profileFields = [
            $team->name,
            $team->tagline,
            $team->description,
            $team->platform,
            $team->playstyle,
            $team->region,
            $team->language,
            $team->avatar_path,
            $team->cover_path,
        ];
        $profileCompletion = (int) round(
            collect($profileFields)->filter(fn ($value): bool => filled($value))->count()
                / max(1, count($profileFields))
                * 100
        );

        $sessionGoing = $upcomingSession
            ? $upcomingSession->responses->where('response', TeamSessionResponse::RESPONSE_GOING)->values()
            : collect();
        $sessionMaybe = $upcomingSession
            ? $upcomingSession->responses->where('response', TeamSessionResponse::RESPONSE_MAYBE)->values()
            : collect();
        $viewerSessionResponse = $upcomingSession
            ? $upcomingSession->responses->firstWhere('user_id', $viewer->id)
            : null;

        $averageContractProgress = $activeContracts->isEmpty()
            ? 0
            : (int) round($activeContracts->avg(function (TeamContract $contract): float {
                return min(100, max(0, ((int) $contract->progress_value / max(1, (int) $contract->target_value)) * 100));
            }));

        $activities = $this->activities($posts, $activeMembers, $recentSessions);
        $isEnglish = app()->getLocale() === 'en';

        return [
            'activeMembers' => $activeMembers,
            'pendingMembers' => $pendingMembers,
            'posts' => $posts,
            'latestPost' => $posts->first(),
            'reportedFeedKeys' => $reportedFeedKeys,
            'upcomingSession' => $upcomingSession,
            'recentSessions' => $recentSessions,
            'activeContracts' => $activeContracts,
            'activities' => $activities,
            'onlineMembers' => $onlineMembers,
            'profileCompletion' => $profileCompletion,
            'postsThisWeek' => $postsThisWeek,
            'sessionsThisWeek' => $sessionsThisWeek,
            'completedSessionsThisWeek' => $completedSessionsThisWeek,
            'averageContractProgress' => $averageContractProgress,
            'sessionGoing' => $sessionGoing,
            'sessionMaybe' => $sessionMaybe,
            'viewerSessionResponse' => $viewerSessionResponse,
            'teamInitials' => $this->initials($team->name),
            'teamLevel' => max(1, (int) ($team->progression?->level ?? 1)),
            'teamXp' => max(0, (int) ($team->progression?->xp_total ?? 0)),
            'canManage' => $team->canManage($viewer),
            'isMember' => $team->isActiveMember($viewer),
            'viewerMembership' => $team->membershipFor($viewer),
            'isEnglish' => $isEnglish,
        ];
    }

    private function activities(Collection $posts, Collection $members, Collection $sessions): Collection
    {
        $isEnglish = app()->getLocale() === 'en';
        $items = collect();

        $posts->take(4)->each(function (FeedPost $post) use ($items, $isEnglish): void {
            $name = $post->user?->name ?: ($post->user?->username ?: 'HNT Hunter');
            $items->push([
                'at' => $post->created_at,
                'avatar' => $post->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'title' => $isEnglish ? $name . ' published a team post' : $name . ' hat einen Team-Beitrag veröffentlicht',
                'meta' => Str::limit(trim((string) $post->body) ?: ($isEnglish ? 'Team update' : 'Team-Update'), 76),
                'url' => $post->permalink(),
            ]);
        });

        $members->filter(fn (TeamMember $member): bool => (bool) $member->joined_at)
            ->take(4)
            ->each(function (TeamMember $member) use ($items, $isEnglish): void {
                $name = $member->user?->name ?: ($member->user?->username ?: 'HNT Hunter');
                $items->push([
                    'at' => $member->joined_at,
                    'avatar' => $member->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                    'title' => $isEnglish ? $name . ' joined the team' : $name . ' ist dem Team beigetreten',
                    'meta' => $member->roleLabel(),
                    'url' => $member->user ? route('profile.public', $member->user) : null,
                ]);
            });

        $sessions->take(4)->each(function (TeamSession $session) use ($items, $isEnglish): void {
            $completed = $session->status === TeamSession::STATUS_COMPLETED;
            $items->push([
                'at' => $session->completed_at ?: $session->created_at,
                'avatar' => $session->creator?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'title' => $completed
                    ? ($isEnglish ? 'Team session completed' : 'Team-Session abgeschlossen')
                    : ($isEnglish ? 'Team session planned' : 'Team-Session geplant'),
                'meta' => $session->title ?: ($isEnglish ? 'Team session' : 'Team-Session'),
                'url' => null,
            ]);
        });

        return $items
            ->filter(fn (array $item): bool => (bool) ($item['at'] ?? null))
            ->sortByDesc(fn (array $item) => optional($item['at'])->timestamp ?? 0)
            ->take(6)
            ->values();
    }

    private function initials(string $name): string
    {
        $initials = Str::of($name)
            ->trim()
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');

        return $initials !== '' ? $initials : 'HT';
    }
}
