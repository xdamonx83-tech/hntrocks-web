<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Cup;
use App\Models\CupFeedbackEntry;
use App\Models\CupSubmission;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\LfgPost;
use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\Quest;
use App\Models\Report;
use App\Models\Team;
use App\Models\TeamLfgPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $now = now();
        $today = $now->copy()->startOfDay();
        $weekStart = $now->copy()->subDays(7);
        $monthStart = $now->copy()->startOfMonth();
        $activityStart = $now->copy()->subDays(30);

        $usersTotal = User::count();
        $usersToday = User::where('created_at', '>=', $today)->count();
        $usersThisMonth = User::where('created_at', '>=', $monthStart)->count();
        $activeUsers = Schema::hasColumn('users', 'last_login_at')
            ? User::where('last_login_at', '>=', $weekStart)->count()
            : 0;

        $feedPostsTotal = FeedPost::count();
        $feedPostsWeek = FeedPost::where('created_at', '>=', $weekStart)->count();
        $commentsWeek = Schema::hasTable('feed_comments')
            ? FeedComment::where('created_at', '>=', $weekStart)->count()
            : 0;
        $momentsTotal = Moment::count();
        $momentsWeek = Moment::where('created_at', '>=', $weekStart)->count();

        $openReports = Report::whereIn('status', ['open', 'in_review'])->count();
        $newFeedback = CupFeedbackEntry::whereIn('status', [CupFeedbackEntry::STATUS_NEW, CupFeedbackEntry::STATUS_REVIEWING])->count();
        $pendingCupSubmissions = CupSubmission::whereIn('status', ['pending', 'review_required'])->count();
        $appBetaRequests = $this->appBetaRequestCount();

        $stats = [
            'users' => $usersTotal,
            'suspended_users' => User::where('status', 'suspended')->count(),
            'feed_posts' => $feedPostsTotal,
            'teams' => Team::count(),
            'lfg_posts' => LfgPost::count(),
            'team_lfg_posts' => TeamLfgPost::count(),
            'media_assets' => MediaAsset::count(),
            'moments' => $momentsTotal,
            'cups' => Cup::count(),
            'pending_cup_submissions' => $pendingCupSubmissions,
            'badges' => Badge::count(),
            'active_quests' => Quest::where('is_active', true)->count(),
            'open_reports' => $openReports,
            'cup_feedback' => $newFeedback,
            'app_beta_requests' => $appBetaRequests,
        ];

        $dashboardCards = [
            [
                'label' => 'Mitglieder',
                'value' => $usersTotal,
                'meta' => '+'.$usersThisMonth.' diesen Monat',
                'accent' => 'blue',
                'route' => route('admin.users.index'),
            ],
            [
                'label' => 'Aktive Nutzer',
                'value' => $activeUsers,
                'meta' => 'letzte 7 Tage',
                'accent' => 'green',
                'route' => route('admin.users.index'),
            ],
            [
                'label' => 'Offene Reports',
                'value' => $openReports,
                'meta' => $openReports > 0 ? 'Prüfung nötig' : 'alles ruhig',
                'accent' => $openReports > 0 ? 'red' : 'green',
                'route' => route('admin.reports.index'),
            ],
            [
                'label' => 'Cup-Feedback',
                'value' => $newFeedback,
                'meta' => 'neu / in Prüfung',
                'accent' => 'gold',
                'route' => route('admin.cup-feedback.index'),
            ],
        ];

        $healthItems = [
            [
                'label' => 'Cup-Einreichungen',
                'value' => $pendingCupSubmissions,
                'hint' => $pendingCupSubmissions > 0 ? 'wartet auf Prüfung' : 'keine offenen Fälle',
                'state' => $pendingCupSubmissions > 0 ? 'warning' : 'good',
            ],
            [
                'label' => 'Neue Nutzer heute',
                'value' => $usersToday,
                'hint' => 'Registrierungen seit Tagesbeginn',
                'state' => 'neutral',
            ],
            [
                'label' => 'Feed diese Woche',
                'value' => $feedPostsWeek,
                'hint' => $commentsWeek.' Kommentare',
                'state' => 'neutral',
            ],
            [
                'label' => 'Moments diese Woche',
                'value' => $momentsWeek,
                'hint' => $momentsTotal.' insgesamt',
                'state' => 'neutral',
            ],
            [
                'label' => 'App-Beta-Anfragen',
                'value' => $appBetaRequests,
                'hint' => 'CSV unter storage/app',
                'state' => 'neutral',
            ],
        ];

        return view('admin.index', [
            'stats' => $stats,
            'dashboardCards' => $dashboardCards,
            'healthItems' => $healthItems,
            'latestReports' => Report::with(['reporter', 'reportable'])
                ->latest()
                ->limit(6)
                ->get(),
            'latestUsers' => User::latest()
                ->limit(6)
                ->get(),
            'latestFeedback' => CupFeedbackEntry::with(['cup:id,title,slug', 'user:id,name,username,email,avatar_path'])
                ->latest('submitted_at')
                ->latest()
                ->limit(5)
                ->get(),
            'latestContent' => FeedPost::with('user:id,name,username,avatar_path')
                ->latest()
                ->limit(5)
                ->get(),
            'latestCupSubmissions' => CupSubmission::with(['cup:id,title,slug', 'submitter:id,name,username,avatar_path'])
                ->latest('submitted_at')
                ->latest()
                ->limit(5)
                ->get(),
            'weeklySeries' => $this->weeklySeries(),
            'memberGrowth' => $this->memberGrowthSeries(),
            'topActiveMembers' => $this->topActiveMembers($activityStart),
        ]);
    }

    private function weeklySeries(): array
    {
        $series = [];

        for ($daysAgo = 6; $daysAgo >= 0; $daysAgo--) {
            $date = now()->copy()->subDays($daysAgo)->startOfDay();
            $nextDate = $date->copy()->addDay();

            $posts = FeedPost::where('created_at', '>=', $date)->where('created_at', '<', $nextDate)->count();
            $comments = Schema::hasTable('feed_comments')
                ? FeedComment::where('created_at', '>=', $date)->where('created_at', '<', $nextDate)->count()
                : 0;
            $users = User::where('created_at', '>=', $date)->where('created_at', '<', $nextDate)->count();

            $series[] = [
                'label' => $date->locale(app()->getLocale())->isoFormat('dd'),
                'posts' => $posts,
                'comments' => $comments,
                'users' => $users,
                'total' => $posts + $comments + $users,
            ];
        }

        return $series;
    }

    private function memberGrowthSeries(): array
    {
        $today = now()->startOfDay();
        $currentStart = $today->copy()->subDays(29);
        $previousStart = $currentStart->copy()->subDays(30);

        $dailyLabels = [];
        $dailyCurrent = [];
        $dailyPrevious = [];

        for ($index = 0; $index < 30; $index++) {
            $currentDay = $currentStart->copy()->addDays($index);
            $previousDay = $previousStart->copy()->addDays($index);

            $dailyLabels[] = $currentDay->format('d.m');
            $dailyCurrent[] = User::where('created_at', '>=', $currentDay)
                ->where('created_at', '<', $currentDay->copy()->addDay())
                ->count();
            $dailyPrevious[] = User::where('created_at', '>=', $previousDay)
                ->where('created_at', '<', $previousDay->copy()->addDay())
                ->count();
        }

        $monthlyLabels = [];
        $monthlyCurrent = [];
        $monthlyPrevious = [];

        for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
            $month = now()->startOfMonth()->subMonths($monthsAgo);
            $previousYearMonth = $month->copy()->subYear();

            $monthlyLabels[] = $month->locale(app()->getLocale())->isoFormat('MMM');
            $monthlyCurrent[] = User::where('created_at', '>=', $month)
                ->where('created_at', '<', $month->copy()->addMonth())
                ->count();
            $monthlyPrevious[] = User::where('created_at', '>=', $previousYearMonth)
                ->where('created_at', '<', $previousYearMonth->copy()->addMonth())
                ->count();
        }

        $currentTotal = array_sum($dailyCurrent);
        $previousTotal = array_sum($dailyPrevious);
        $growthPercent = $previousTotal > 0
            ? (int) round((($currentTotal - $previousTotal) / $previousTotal) * 100)
            : ($currentTotal > 0 ? 100 : 0);

        return [
            'daily' => [
                'labels' => $dailyLabels,
                'current' => $dailyCurrent,
                'previous' => $dailyPrevious,
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'current' => $monthlyCurrent,
                'previous' => $monthlyPrevious,
            ],
            'currentTotal' => $currentTotal,
            'previousTotal' => $previousTotal,
            'growthPercent' => $growthPercent,
            'periodLabel' => $currentStart->format('d.m').' – '.$today->format('d.m.Y'),
        ];
    }

    private function topActiveMembers(Carbon $since): Collection
    {
        $activity = [];
        $sources = [
            ['table' => 'feed_posts', 'weight' => 5, 'key' => 'posts'],
            ['table' => 'feed_comments', 'weight' => 3, 'key' => 'comments'],
            ['table' => 'feed_reactions', 'weight' => 1, 'key' => 'reactions'],
            ['table' => 'moment_comments', 'weight' => 3, 'key' => 'comments'],
            ['table' => 'moment_reactions', 'weight' => 1, 'key' => 'reactions'],
        ];

        foreach ($sources as $source) {
            $table = $source['table'];

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'user_id') || ! Schema::hasColumn($table, 'created_at')) {
                continue;
            }

            $query = DB::table($table)
                ->select('user_id', DB::raw('COUNT(*) as total'))
                ->whereNotNull('user_id')
                ->where('created_at', '>=', $since);

            if (Schema::hasColumn($table, 'deleted_at')) {
                $query->whereNull('deleted_at');
            }

            foreach ($query->groupBy('user_id')->get() as $row) {
                $userId = (int) $row->user_id;
                $count = (int) $row->total;

                $activity[$userId] ??= [
                    'score' => 0,
                    'posts' => 0,
                    'comments' => 0,
                    'reactions' => 0,
                    'actions' => 0,
                ];

                $activity[$userId][$source['key']] += $count;
                $activity[$userId]['actions'] += $count;
                $activity[$userId]['score'] += $count * $source['weight'];
            }
        }

        if ($activity === []) {
            return collect();
        }

        uasort($activity, static fn (array $left, array $right): int => $right['score'] <=> $left['score']);
        $topActivity = array_slice($activity, 0, 5, true);
        $userIds = array_map('intval', array_keys($topActivity));
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');
        $maxScore = max(1, (int) collect($topActivity)->max('score'));

        return collect($topActivity)
            ->map(function (array $metrics, int|string $userId) use ($users, $maxScore): ?array {
                $user = $users->get((int) $userId);

                if (! $user) {
                    return null;
                }

                return [
                    'user' => $user,
                    'score' => (int) $metrics['score'],
                    'actions' => (int) $metrics['actions'],
                    'posts' => (int) $metrics['posts'],
                    'comments' => (int) $metrics['comments'],
                    'reactions' => (int) $metrics['reactions'],
                    'percent' => max(5, (int) round(($metrics['score'] / $maxScore) * 100)),
                ];
            })
            ->filter()
            ->values();
    }

    private function appBetaRequestCount(): int
    {
        $path = storage_path('app/app-beta-requests.csv');

        if (! is_file($path) || ! is_readable($path)) {
            return 0;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! is_array($lines)) {
            return 0;
        }

        return max(0, count($lines) - 1);
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
