<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReaction;
use App\Models\Friendship;
use App\Models\LfgPost;
use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Models\MomentReaction;
use App\Models\Quest;
use App\Models\Report;
use App\Models\Team;
use App\Models\User;
use App\Models\VisitorEvent;
use App\Models\XpEvent;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminOverviewController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $now = now();
        $monthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $monthStart->copy()->subMonth();
        $previousMonthEnd = $monthStart->copy()->subSecond();
        $yearStart = $now->copy()->startOfYear();

        $usersTotal = $this->countTable('users');
        $usersThisMonth = $this->countTableSince('users', $monthStart);
        $usersPreviousMonth = $this->countTableBetween('users', $previousMonthStart, $previousMonthEnd);
        $activeUsersThisMonth = Schema::hasColumn('users', 'last_login_at')
            ? User::where('last_login_at', '>=', $monthStart)->count()
            : 0;
        $activeUsersNow = Schema::hasTable('sessions')
            ? DB::table('sessions')->where('last_activity', '>=', $now->copy()->subMinutes(5)->timestamp)->count()
            : 0;
        $visitsThisMonth = $this->countTableSince('visitor_events', $monthStart);
        $visitsPreviousMonth = $this->countTableBetween('visitor_events', $previousMonthStart, $previousMonthEnd);
        $uniqueVisitorsThisMonth = $this->distinctVisitorCount($monthStart);
        $firstVisitsThisMonth = $this->firstVisitCount($monthStart);
        $campaignLinks = $this->campaignLinks($monthStart);
        $campaignClicksThisMonth = (int) $campaignLinks->sum('clicks_month');
        $campaignClicksTotal = (int) $campaignLinks->sum('clicks_total');

        $postsTotal = $this->countTable('feed_posts');
        $postsThisMonth = $this->countTableSince('feed_posts', $monthStart);
        $postsPreviousMonth = $this->countTableBetween('feed_posts', $previousMonthStart, $previousMonthEnd);
        $commentsTotal = $this->countTable('feed_comments') + $this->countTable('moment_comments');
        $commentsThisMonth = $this->countTableSince('feed_comments', $monthStart) + $this->countTableSince('moment_comments', $monthStart);
        $commentsPreviousMonth = $this->countTableBetween('feed_comments', $previousMonthStart, $previousMonthEnd) + $this->countTableBetween('moment_comments', $previousMonthStart, $previousMonthEnd);
        $reactionsTotal = $this->countTable('feed_reactions') + $this->countTable('moment_reactions') + $this->countTable('feed_comment_reactions');
        $reactionsThisMonth = $this->countTableSince('feed_reactions', $monthStart) + $this->countTableSince('moment_reactions', $monthStart) + $this->countTableSince('feed_comment_reactions', $monthStart);
        $reactionsPreviousMonth = $this->countTableBetween('feed_reactions', $previousMonthStart, $previousMonthEnd) + $this->countTableBetween('moment_reactions', $previousMonthStart, $previousMonthEnd) + $this->countTableBetween('feed_comment_reactions', $previousMonthStart, $previousMonthEnd);

        $acceptedFriendships = Schema::hasTable('friendships')
            ? Friendship::where('status', Friendship::STATUS_ACCEPTED)->count()
            : 0;
        $teamsTotal = $this->countTable('teams');
        $activeTeams = Schema::hasTable('teams') && Schema::hasColumn('teams', 'status')
            ? Team::where('status', 'active')->count()
            : $teamsTotal;
        $lfgOpen = Schema::hasTable('lfg_posts')
            ? LfgPost::where('status', 'open')->count()
            : 0;
        $badgesTotal = $this->countTable('badges');
        $badgesAwarded = $this->countTable('badge_user');
        $questsActive = Schema::hasTable('quests') ? Quest::where('is_active', true)->count() : 0;
        $questsCompleted = Schema::hasTable('quest_user') && Schema::hasColumn('quest_user', 'completed_at')
            ? DB::table('quest_user')->whereNotNull('completed_at')->count()
            : 0;
        $mediaTotal = $this->countTable('media_assets');
        $momentsTotal = $this->countTable('moments');
        $momentViews = Schema::hasTable('moments') && Schema::hasColumn('moments', 'views_count')
            ? (int) DB::table('moments')->whereNull('deleted_at')->sum('views_count')
            : 0;
        $reportsOpen = Schema::hasTable('reports')
            ? Report::whereIn('status', ['open', 'in_review'])->count()
            : 0;
        $messagesTotal = $this->countTable('messages');
        $notificationsUnread = Schema::hasTable('user_notifications')
            ? DB::table('user_notifications')->whereNull('read_at')->count()
            : 0;
        $xpTotal = Schema::hasTable('xp_events') ? (int) XpEvent::sum('points') : 0;
        $highestLevel = Schema::hasColumn('users', 'level') ? (int) User::max('level') : 1;

        $engagementsThisMonth = $commentsThisMonth + $reactionsThisMonth;
        $engagementsTotal = $commentsTotal + $reactionsTotal;
        $activeUserRate = $usersTotal > 0 ? (int) round(($activeUsersThisMonth / $usersTotal) * 100) : 0;
        $contentEngagementRate = $postsTotal > 0 ? min(100, (int) round(($engagementsTotal / max(1, $postsTotal)) * 10)) : 0;
        $returningUsersRate = $activeUsersThisMonth > 0
            ? (int) round(($this->returningUsersThisMonth($monthStart) / $activeUsersThisMonth) * 100)
            : 0;

        $gamificationCompletionRate = (int) round(
            ($badgesTotal > 0 ? min(1, $badgesAwarded / max(1, $badgesTotal)) * 50 : 0)
            + ($questsActive > 0 ? min(1, $questsCompleted / max(1, $questsActive)) * 50 : 0)
        );

        $latestBadgeAward = $this->latestBadgeAward();
        $latestQuestCompletion = $this->latestQuestCompletion();
        $activityItems = $this->latestActivityItems();
        $systemEvents = $this->latestSystemEvents();
        $xpEvents = $this->latestXpEvents();
        $topMembers = $this->topMembers();
        $topReactors = $this->topUserCounts('feed_reactions', $monthStart, 2);
        $topCommenters = $this->topUserCounts('feed_comments', $monthStart, 2);
        $featuredPost = $this->featuredFeedPost();
        $sessionRows = Schema::hasTable('visitor_events') ? $this->topVisitCountries() : $this->activeSessionsByIp();

        $dailyContent = $this->mergeDailySeries([
            $this->dailyCounts('feed_posts', $monthStart),
            $this->dailyCounts('lfg_posts', $monthStart),
            $this->dailyCounts('teams', $monthStart),
            $this->dailyCounts('moments', $monthStart),
        ], $now->daysInMonth);
        $dailyEngagements = $this->mergeDailySeries([
            $this->dailyCounts('feed_comments', $monthStart),
            $this->dailyCounts('moment_comments', $monthStart),
            $this->dailyCounts('feed_reactions', $monthStart),
            $this->dailyCounts('moment_reactions', $monthStart),
            $this->dailyCounts('feed_comment_reactions', $monthStart),
        ], $now->daysInMonth);
        $dailyVisits = Schema::hasTable('visitor_events')
            ? $this->dailyCounts('visitor_events', $monthStart)
            : $dailyContent;
        $monthlyMembers = $this->monthlyCounts('users', $yearStart);
        $monthlyVisits = Schema::hasTable('visitor_events')
            ? $this->monthlyCounts('visitor_events', $yearStart)
            : $monthlyMembers;
        $monthlyEngagements = $this->mergeMonthlySeries([
            $this->monthlyCounts('feed_comments', $yearStart),
            $this->monthlyCounts('moment_comments', $yearStart),
            $this->monthlyCounts('feed_reactions', $yearStart),
            $this->monthlyCounts('moment_reactions', $yearStart),
            $this->monthlyCounts('feed_comment_reactions', $yearStart),
        ]);

        $charts = [
            'profileCompletionData' => [$gamificationCompletionRate, max(0, 100 - $gamificationCompletionRate)],
            'postsEngagementData' => [$contentEngagementRate, max(0, 100 - $contentEngagementRate)],
            'postsSharedData' => [$returningUsersRate, max(0, 100 - $returningUsersRate)],
            'engagementsData' => [
                max(0, $reactionsTotal),
                max(0, $this->countTable('feed_comments')),
                max(0, Schema::hasColumn('feed_posts', 'shared_post_id') ? DB::table('feed_posts')->whereNotNull('shared_post_id')->whereNull('deleted_at')->count() : 0),
                max(0, $this->countTable('moment_comments')),
            ],
            'engagementsLabels' => ['Reaktionen', 'Feed-Kommentare', 'Shares', 'Moment-Kommentare'],
            'veMonthlyReportData1' => $dailyEngagements,
            'veMonthlyReportData2' => $dailyVisits,
            'veMonthlyReportLabels' => array_map(fn (int $day): string => str_pad((string) $day, 2, '0', STR_PAD_LEFT), range(1, $now->daysInMonth)),
            'veMonthlyReportRatioData' => $this->ratioPair($engagementsThisMonth, array_sum($dailyVisits)),
            'rcYearlyReportData1' => $monthlyEngagements,
            'rcYearlyReportData2' => $monthlyVisits,
            'rcYearlyReportLabels' => ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
        ];

        return view('overview.index', [
            'overview' => [
                'admin' => $request->user(),
                'monthLabel' => $now->translatedFormat('F Y'),
                'yearLabel' => (string) $now->year,
                'topStats' => [
                    ['class' => 'stat-profile-views', 'value' => $usersTotal, 'label' => 'Registrierte Nutzer', 'text' => 'Gesamtbestand', 'diff' => $this->diff($usersThisMonth, $usersPreviousMonth)],
                    ['class' => 'stat-posts-created', 'value' => $postsThisMonth, 'label' => 'Feed-Beiträge', 'text' => 'in diesem Monat', 'diff' => $this->diff($postsThisMonth, $postsPreviousMonth)],
                    ['class' => 'stat-reactions-received', 'value' => $reactionsThisMonth, 'label' => 'Reaktionen', 'text' => 'in diesem Monat', 'diff' => $this->diff($reactionsThisMonth, $reactionsPreviousMonth)],
                    ['class' => 'stat-comments-received', 'value' => $commentsThisMonth, 'label' => 'Kommentare', 'text' => 'in diesem Monat', 'diff' => $this->diff($commentsThisMonth, $commentsPreviousMonth)],
                ],
                'profileStats' => [
                    'postsAvg' => $this->averagePerMonth($postsTotal),
                    'commentsAvg' => $this->averagePerMonth($commentsTotal),
                    'contentEngagementRate' => $contentEngagementRate,
                    'activeUserRate' => $activeUserRate,
                    'activeUsersThisMonth' => $activeUsersThisMonth,
                    'returningUsersRate' => $returningUsersRate,
                    'highestLevel' => $highestLevel,
                ],
                'sliderStats' => [
                    ['value' => $postsTotal, 'label' => 'Posts'],
                    ['value' => $acceptedFriendships, 'label' => 'Freundschaften'],
                    ['value' => $teamsTotal, 'label' => 'Teams'],
                    ['value' => $badgesAwarded, 'label' => 'Badges vergeben'],
                    ['value' => $mediaTotal, 'label' => 'Medien'],
                    ['value' => $reactionsTotal, 'label' => 'Reaktionen'],
                    ['value' => $commentsTotal, 'label' => 'Kommentare'],
                    ['value' => $momentsTotal, 'label' => 'Moments'],
                ],
                'activityItems' => $activityItems,
                'systemEvents' => $systemEvents,
                'gamification' => [
                    'latestBadge' => $latestBadgeAward,
                    'latestQuest' => $latestQuestCompletion,
                    'completionRate' => $gamificationCompletionRate,
                    'highestLevel' => $highestLevel,
                    'xpTotal' => $xpTotal,
                    'badgesTotal' => $badgesTotal,
                    'badgesAwarded' => $badgesAwarded,
                    'questsActive' => $questsActive,
                    'questsCompleted' => $questsCompleted,
                ],
                'xpEvents' => $xpEvents,
                'analytics' => [
                    'activeUsersNow' => $activeUsersNow,
                    'newUsersMonth' => $usersThisMonth,
                    'activeUsersMonth' => $activeUsersThisMonth,
                    'visitsMonth' => $visitsThisMonth,
                    'uniqueVisitorsMonth' => $uniqueVisitorsThisMonth,
                    'firstVisitsMonth' => $firstVisitsThisMonth,
                    'campaignClicksMonth' => $campaignClicksThisMonth,
                    'campaignClicksTotal' => $campaignClicksTotal,
                    'returningUsersRate' => $returningUsersRate,
                    'engagementsMonth' => $engagementsThisMonth,
                    'contentMonth' => array_sum($dailyVisits),
                    'messagesTotal' => $messagesTotal,
                    'notificationsUnread' => $notificationsUnread,
                    'reportsOpen' => $reportsOpen,
                    'lfgOpen' => $lfgOpen,
                    'activeTeams' => $activeTeams,
                    'momentViews' => $momentViews,
                ],
                'topMembers' => $topMembers,
                'topReactors' => $topReactors,
                'topCommenters' => $topCommenters,
                'featuredPost' => $featuredPost,
                'personalActivityItems' => $activityItems,
                'sessionRows' => $sessionRows,
                'campaignLinks' => $campaignLinks,
                'charts' => $charts,
                'yearlySecondaryLabel' => Schema::hasTable('visitor_events') ? 'Visits' : 'Registrierungen',
                'geoTrackingAvailable' => Schema::hasTable('visitor_events'),
            ],
        ]);
    }

    private function table(string $table): ?Builder
    {
        if (! Schema::hasTable($table)) {
            return null;
        }

        $query = DB::table($table);

        if (Schema::hasColumn($table, 'deleted_at')) {
            $query->whereNull($table . '.deleted_at');
        }

        return $query;
    }

    private function countTable(string $table): int
    {
        return $this->table($table)?->count() ?? 0;
    }

    private function countTableSince(string $table, Carbon $since, string $column = 'created_at'): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return $this->table($table)?->where($column, '>=', $since)->count() ?? 0;
    }

    private function countTableBetween(string $table, Carbon $start, Carbon $end, string $column = 'created_at'): int
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
            return 0;
        }

        return $this->table($table)?->whereBetween($column, [$start, $end])->count() ?? 0;
    }

    private function diff(int $current, int $previous): array
    {
        if ($previous <= 0) {
            return [
                'value' => $current > 0 ? 'neu' : '0%',
                'direction' => $current > 0 ? 'positive' : 'negative',
                'icon' => $current > 0 ? 'plus-small' : 'minus-small',
            ];
        }

        $percent = (($current - $previous) / $previous) * 100;

        return [
            'value' => number_format(abs($percent), 1, ',', '.') . '%',
            'direction' => $percent >= 0 ? 'positive' : 'negative',
            'icon' => $percent >= 0 ? 'plus-small' : 'minus-small',
        ];
    }

    private function averagePerMonth(int $total): string
    {
        $oldest = null;
        foreach (['users', 'feed_posts', 'feed_comments'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'created_at')) {
                continue;
            }
            $date = DB::table($table)->min('created_at');
            if ($date && (! $oldest || $date < $oldest)) {
                $oldest = $date;
            }
        }

        $months = $oldest ? max(1, Carbon::parse($oldest)->diffInMonths(now()) + 1) : 1;

        return number_format($total / $months, 1, ',', '.');
    }

    private function returningUsersThisMonth(Carbon $monthStart): int
    {
        if (! Schema::hasColumn('users', 'last_login_at')) {
            return 0;
        }

        return User::where('last_login_at', '>=', $monthStart)
            ->where('created_at', '<', $monthStart)
            ->count();
    }

    private function latestBadgeAward(): ?array
    {
        if (! Schema::hasTable('badge_user')) {
            return null;
        }

        $awardedAtColumn = Schema::hasColumn('badge_user', 'awarded_at') ? 'badge_user.awarded_at' : 'badge_user.created_at';
        $row = DB::table('badge_user')
            ->join('badges', 'badges.id', '=', 'badge_user.badge_id')
            ->join('users', 'users.id', '=', 'badge_user.user_id')
            ->select('badges.name as badge_name', 'badges.description', 'users.name as user_name', 'users.username', $awardedAtColumn . ' as awarded_at')
            ->orderByDesc($awardedAtColumn)
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'title' => $row->badge_name,
            'text' => '@' . $row->username,
            'date' => $this->humanDate($row->awarded_at),
        ];
    }

    private function latestQuestCompletion(): ?array
    {
        if (! Schema::hasTable('quest_user') || ! Schema::hasColumn('quest_user', 'completed_at')) {
            return null;
        }

        $row = DB::table('quest_user')
            ->join('quests', 'quests.id', '=', 'quest_user.quest_id')
            ->join('users', 'users.id', '=', 'quest_user.user_id')
            ->whereNotNull('quest_user.completed_at')
            ->select('quests.name as quest_name', 'users.username', 'quest_user.completed_at')
            ->orderByDesc('quest_user.completed_at')
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'title' => $row->quest_name,
            'text' => '@' . $row->username,
            'date' => $this->humanDate($row->completed_at),
        ];
    }

    private function latestActivityItems(): Collection
    {
        $items = collect();

        if (Schema::hasTable('feed_posts')) {
            FeedPost::with('user')->latest()->limit(6)->get()->each(function (FeedPost $post) use ($items): void {
                $items->push($this->activityItem($post->user, 'Feed-Beitrag erstellt', $post->body ?: 'Neuer Beitrag', $post->created_at, 'status', route('feed.show', $post)));
            });
        }

        if (Schema::hasTable('feed_comments')) {
            FeedComment::with('user')->latest()->limit(6)->get()->each(function (FeedComment $comment) use ($items): void {
                $items->push($this->activityItem($comment->user, 'Kommentar geschrieben', $comment->body, $comment->created_at, 'comment', route('feed.index')));
            });
        }

        if (Schema::hasTable('teams')) {
            Team::with('owner')->latest()->limit(5)->get()->each(function (Team $team) use ($items): void {
                $items->push($this->activityItem($team->owner, 'Team erstellt', $team->name, $team->created_at, 'members', route('teams.show', $team)));
            });
        }

        if (Schema::hasTable('lfg_posts')) {
            LfgPost::with('user')->latest()->limit(5)->get()->each(function (LfgPost $post) use ($items): void {
                $items->push($this->activityItem($post->user, 'LFG erstellt', $post->title, $post->created_at, 'status', route('lfg.show', $post)));
            });
        }

        if (Schema::hasTable('users')) {
            User::latest()->limit(5)->get()->each(function (User $user) use ($items): void {
                $items->push($this->activityItem($user, 'hat sich registriert', 'Neues hnt.rocks-Konto', $user->created_at, 'members', route('profile.public', $user)));
            });
        }

        return $items->sortByDesc('created_at')->take(10)->values();
    }

    private function activityItem(?User $user, string $action, ?string $title, ?Carbon $date, string $icon, ?string $url = null): array
    {
        $safeTitle = trim(strip_tags((string) $title));

        return [
            'members' => $user,
            'name' => $user?->name ?: 'System',
            'username' => $user?->username,
            'avatar' => $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
            'action' => $action,
            'title' => mb_strlen($safeTitle) > 72 ? mb_substr($safeTitle, 0, 69) . '…' : ($safeTitle ?: 'Ohne Titel'),
            'created_at' => $date ?: now(),
            'time' => $date ? $date->diffForHumans() : 'gerade eben',
            'icon' => $icon,
            'url' => $url ?: '#',
        ];
    }

    private function latestSystemEvents(): Collection
    {
        $events = collect([
            ['title' => 'Offene Reports', 'value' => $this->countTable('reports'), 'text' => 'davon offen/in Prüfung', 'extra' => Schema::hasTable('reports') ? Report::whereIn('status', ['open', 'in_review'])->count() : 0],
            ['title' => 'Offene LFGs', 'value' => Schema::hasTable('lfg_posts') ? LfgPost::where('status', 'open')->count() : 0, 'text' => 'aktuell suchend', 'extra' => $this->countTable('lfg_applications')],
            ['title' => 'Aktive Teams', 'value' => Schema::hasTable('teams') ? Team::where('status', 'active')->count() : 0, 'text' => 'Teamprofile', 'extra' => $this->countTable('team_members')],
            ['title' => 'Ungelesene Hinweise', 'value' => Schema::hasTable('user_notifications') ? DB::table('user_notifications')->whereNull('read_at')->count() : 0, 'text' => 'Benachrichtigungen', 'extra' => $this->countTable('user_notifications')],
        ]);

        return $events;
    }

    private function latestXpEvents(): Collection
    {
        if (! Schema::hasTable('xp_events')) {
            return collect();
        }

        return XpEvent::with('user')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (XpEvent $event): array => [
                'action' => $event->description ?: str_replace('_', ' ', $event->action),
                'user' => $event->user?->username ? '@' . $event->user->username : 'System',
                'members' => $event->user?->username ? '@' . $event->user->username : 'System',
                'points' => (int) $event->points,
                'date' => $event->created_at?->diffForHumans() ?: '-',
            ]);
    }

    private function topMembers(): Collection
    {
        if (! Schema::hasTable('users')) {
            return collect();
        }

        return User::query()
            ->withCount(['feedPosts', 'feedComments', 'badges'])
            ->orderByDesc('xp_total')
            ->orderByDesc('feed_posts_count')
            ->limit(8)
            ->get()
            ->map(fn (User $user): array => [
                'name' => $user->name,
                'username' => $user->username,
                'avatar' => $user->avatarUrl(),
                'level' => (int) ($user->level ?: 1),
                'posts' => (int) $user->feed_posts_count,
                'badges' => (int) $user->badges_count,
                'comments' => (int) $user->feed_comments_count,
                'url' => route('profile.public', $user),
            ]);
    }


    private function topUserCounts(string $table, Carbon $monthStart, int $limit = 2): Collection
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id')) {
            return collect();
        }

        $queryFor = function (?Carbon $since = null) use ($table) {
            $query = DB::table($table)
                ->selectRaw('user_id, COUNT(*) as total')
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderByDesc('total');

            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            if ($since && Schema::hasColumn($table, 'created_at')) {
                $query->where('created_at', '>=', $since);
            }

            return $query->limit(1)->first();
        };

        $rows = collect([$queryFor($monthStart), $queryFor(null)])->filter();

        if ($rows->isEmpty()) {
            return collect();
        }

        $users = User::whereIn('id', $rows->pluck('user_id')->unique()->all())->get()->keyBy('id');

        return $rows->values()->map(function ($row, int $index) use ($users): array {
            $user = $users->get($row->user_id);

            return [
                'name' => $user?->name ?: 'System',
                'username' => $user?->username,
                'avatar' => $user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
                'level' => (int) ($user?->level ?: 1),
                'count' => (int) $row->total,
                'period' => $index === 0 ? 'of last month' : 'of all time',
                'url' => $user ? route('profile.public', $user) : '#',
            ];
        });
    }

    private function featuredFeedPost(): ?array
    {
        if (! Schema::hasTable('feed_posts')) {
            return null;
        }

        $post = FeedPost::query()
            ->with(['user', 'media'])
            ->withCount(['reactions', 'comments'])
            ->orderByDesc('reactions_count')
            ->orderByDesc('comments_count')
            ->latest()
            ->first();

        if (! $post) {
            return null;
        }

        $media = $post->media->first();
        $mediaUrl = null;

        if ($media) {
            try {
                $mediaUrl = $media->url();
            } catch (\Throwable) {
                $mediaUrl = null;
            }
        }

        $body = trim(strip_tags((string) $post->body));

        return [
            'author' => $post->user?->name ?: 'System',
            'username' => $post->user?->username,
            'avatar' => $post->user?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg'),
            'level' => (int) ($post->user?->level ?: 1),
            'time' => $post->created_at?->diffForHumans() ?: '-',
            'body' => $body !== '' ? $body : 'Neuer Feed-Beitrag',
            'excerpt' => mb_strlen($body) > 180 ? mb_substr($body, 0, 177) . '…' : ($body !== '' ? $body : 'Neuer Feed-Beitrag'),
            'url' => route('feed.show', $post),
            'media_url' => $mediaUrl,
            'media_is_video' => $media ? $media->isVideo() : false,
            'reactions_count' => (int) $post->reactions_count,
            'comments_count' => (int) $post->comments_count,
            'shares_count' => Schema::hasColumn('feed_posts', 'shared_post_id') ? FeedPost::where('shared_post_id', $post->id)->count() : 0,
            'visibility' => $post->visibilityLabel(),
        ];
    }


    private function campaignLinks(Carbon $monthStart): Collection
    {
        if (! Schema::hasTable('campaign_links')) {
            return collect();
        }

        $query = DB::table('campaign_links as links');

        if (Schema::hasTable('campaign_link_clicks')) {
            $monthlyClicks = DB::table('campaign_link_clicks')
                ->selectRaw('campaign_link_id, COUNT(*) as total')
                ->where('occurred_at', '>=', $monthStart)
                ->groupBy('campaign_link_id');

            $query->leftJoinSub($monthlyClicks, 'month_clicks', function ($join): void {
                $join->on('month_clicks.campaign_link_id', '=', 'links.id');
            });
        }

        $select = [
            'links.slug',
            'links.label',
            'links.target_url',
            'links.utm_source',
            'links.utm_medium',
            'links.utm_campaign',
            'links.clicks_count',
            'links.is_active',
            'links.last_clicked_at',
        ];

        if (Schema::hasTable('campaign_link_clicks')) {
            $select[] = DB::raw('COALESCE(month_clicks.total, 0) as clicks_month');
        } else {
            $select[] = DB::raw('0 as clicks_month');
        }

        return $query
            ->select($select)
            ->orderByDesc('links.clicks_count')
            ->orderBy('links.label')
            ->limit(8)
            ->get()
            ->map(fn ($row): array => [
                'slug' => (string) $row->slug,
                'label' => (string) $row->label,
                'short_url' => url('/go/' . $row->slug),
                'target_url' => $this->campaignTargetUrl($row),
                'utm_source' => $row->utm_source,
                'utm_medium' => $row->utm_medium,
                'utm_campaign' => $row->utm_campaign,
                'clicks_total' => (int) $row->clicks_count,
                'clicks_month' => (int) $row->clicks_month,
                'is_active' => (bool) $row->is_active,
                'last_clicked_at' => $row->last_clicked_at ? Carbon::parse($row->last_clicked_at)->diffForHumans() : 'noch nie',
            ]);
    }

    private function campaignTargetUrl(object $row): string
    {
        $targetUrl = trim((string) $row->target_url);

        if ($targetUrl === '') {
            $targetUrl = '/';
        }

        if (! str_starts_with($targetUrl, 'http://') && ! str_starts_with($targetUrl, 'https://')) {
            $targetUrl = url('/' . ltrim($targetUrl, '/'));
        }

        $query = array_filter([
            'utm_source' => $row->utm_source,
            'utm_medium' => $row->utm_medium,
            'utm_campaign' => $row->utm_campaign,
        ], static fn ($value): bool => $value !== null && $value !== '');

        if ($query === []) {
            return $targetUrl;
        }

        return $targetUrl . (str_contains($targetUrl, '?') ? '&' : '?') . http_build_query($query);
    }


    private function distinctVisitorCount(Carbon $since): int
    {
        if (! Schema::hasTable('visitor_events')) {
            return 0;
        }

        return VisitorEvent::where('occurred_at', '>=', $since)
            ->distinct('visitor_hash')
            ->count('visitor_hash');
    }

    private function firstVisitCount(Carbon $since): int
    {
        if (! Schema::hasTable('visitor_events')) {
            return 0;
        }

        return VisitorEvent::where('occurred_at', '>=', $since)
            ->where('is_first_visit', true)
            ->count();
    }

    private function topVisitCountries(): Collection
    {
        if (! Schema::hasTable('visitor_events')) {
            return collect();
        }

        return DB::table('visitor_events')
            ->selectRaw('country_code, country_name, source, COUNT(*) as total, COUNT(DISTINCT visitor_hash) as visitors_total, MAX(occurred_at) as last_seen')
            ->where('occurred_at', '>=', now()->subDays(30))
            ->groupBy('country_code', 'country_name', 'source')
            ->orderByDesc('total')
            ->limit(9)
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->country_name ?: ($row->country_code ?: ($row->source ?: 'unbekannt')),
                'sessions' => (int) $row->total,
                'users' => (int) $row->visitors_total,
                'last_seen' => $row->last_seen ? Carbon::parse($row->last_seen)->diffForHumans() : '-',
                'flag_slug' => $this->countryFlagSlug($row->country_code),
            ]);
    }

    private function countryFlagSlug(?string $countryCode): string
    {
        return [
            'AR' => 'argentina',
            'BR' => 'brazil',
            'CA' => 'canada',
            'DE' => 'germany',
            'FR' => 'france',
            'IN' => 'india',
            'RU' => 'russia',
            'TR' => 'turkey',
            'US' => 'usa',
        ][strtoupper((string) $countryCode)] ?? 'germany';
    }

    private function activeSessionsByIp(): Collection
    {
        if (! Schema::hasTable('sessions')) {
            return collect();
        }

        return DB::table('sessions')
            ->selectRaw('COALESCE(ip_address, "unbekannt") as ip_address, COUNT(*) as total, COUNT(DISTINCT user_id) as users_total, MAX(last_activity) as last_seen')
            ->groupBy('ip_address')
            ->orderByDesc('total')
            ->limit(9)
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->ip_address,
                'sessions' => (int) $row->total,
                'users' => (int) $row->users_total,
                'last_seen' => $row->last_seen ? Carbon::createFromTimestamp((int) $row->last_seen)->diffForHumans() : '-',
            ]);
    }

    private function dailyCounts(string $table, Carbon $monthStart): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'created_at')) {
            return [];
        }

        $rows = $this->table($table)?->selectRaw('DAY(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', $monthStart)
            ->where('created_at', '<=', $monthStart->copy()->endOfMonth())
            ->groupByRaw('DAY(created_at)')
            ->pluck('total', 'day')
            ->all() ?? [];

        return array_map('intval', $rows);
    }

    private function mergeDailySeries(array $series, int $daysInMonth): array
    {
        $result = array_fill(1, $daysInMonth, 0);

        foreach ($series as $items) {
            foreach ($items as $day => $total) {
                if (isset($result[(int) $day])) {
                    $result[(int) $day] += (int) $total;
                }
            }
        }

        return array_values($result);
    }

    private function monthlyCounts(string $table, Carbon $yearStart): array
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'created_at')) {
            return array_fill(0, 12, 0);
        }

        $rows = $this->table($table)?->selectRaw('MONTH(created_at) as month, COUNT(*) as total')
            ->where('created_at', '>=', $yearStart)
            ->where('created_at', '<=', $yearStart->copy()->endOfYear())
            ->groupByRaw('MONTH(created_at)')
            ->pluck('total', 'month')
            ->all() ?? [];

        $result = array_fill(1, 12, 0);
        foreach ($rows as $month => $total) {
            $result[(int) $month] = (int) $total;
        }

        return array_values($result);
    }

    private function mergeMonthlySeries(array $series): array
    {
        $result = array_fill(0, 12, 0);

        foreach ($series as $items) {
            foreach ($items as $index => $total) {
                $result[(int) $index] += (int) $total;
            }
        }

        return $result;
    }

    private function ratioPair(int $first, int $second): array
    {
        $total = max(1, $first + $second);

        return [
            round(($first / $total) * 100, 1),
            round(($second / $total) * 100, 1),
        ];
    }

    private function humanDate(mixed $date): string
    {
        return $date ? Carbon::parse($date)->diffForHumans() : '-';
    }
}
