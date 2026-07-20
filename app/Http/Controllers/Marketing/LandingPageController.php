<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\FeedPost;
use App\Models\LfgPost;
use App\Models\Moment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('feed.index');
        }

        $stats = [
            'members' => $this->activeMembersCount(),
            'posts' => $this->publicPostsCount(),
            'posts_week' => $this->publicPostsThisWeekCount(),
            'cups' => $this->publicCupsCount(),
            'moments' => $this->publicMomentsCount(),
            'open_lfgs' => $this->openLfgsCount(),
            'active_teams' => $this->activeTeamsCount(),
            'active_today' => $this->activeTodayCount(),
            'online_now' => $this->onlineNowCount(),
        ];

        return view('themes.hnt_preview.landing.index', [
            'stats' => $stats,
            'recentMembers' => $this->recentMembers(),
            'featuredPost' => $this->featuredPost(),
            'featuredLfg' => $this->featuredLfg(),
            'featuredCup' => $this->featuredCup(),
            'recentMoments' => $this->recentMoments(),
        ]);
    }

    private function activeMembersCount(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return (int) User::query()->where('status', 'active')->count();
    }

    private function publicPostsCount(): int
    {
        if (! Schema::hasTable('feed_posts')) {
            return 0;
        }

        return (int) FeedPost::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->count();
    }

    private function publicPostsThisWeekCount(): int
    {
        if (! Schema::hasTable('feed_posts')) {
            return 0;
        }

        return (int) FeedPost::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->where('created_at', '>=', now()->startOfWeek())
            ->count();
    }

    private function publicCupsCount(): int
    {
        if (! Schema::hasTable('cups')) {
            return 0;
        }

        return (int) Cup::query()
            ->visible()
            ->whereIn('status', ['planned', 'active', 'finished', 'archived'])
            ->count();
    }

    private function publicMomentsCount(): int
    {
        if (! Schema::hasTable('moments')) {
            return 0;
        }

        return (int) Moment::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->count();
    }

    private function openLfgsCount(): int
    {
        if (! Schema::hasTable('lfg_posts')) {
            return 0;
        }

        return (int) LfgPost::query()
            ->where('status', 'open')
            ->where('visibility', 'public')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }

    private function activeTeamsCount(): int
    {
        if (! Schema::hasTable('teams')) {
            return 0;
        }

        return (int) Team::query()
            ->where('status', 'active')
            ->where('visibility', '!=', 'private')
            ->count();
    }

    private function activeTodayCount(): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'last_seen_at')) {
            return 0;
        }

        return (int) User::query()
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subDay())
            ->count();
    }

    private function onlineNowCount(): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'last_seen_at')) {
            return 0;
        }

        return (int) User::query()
            ->where('status', 'active')
            ->where('last_seen_at', '>=', now()->subSeconds(User::ONLINE_WINDOW_SECONDS))
            ->count();
    }

    private function recentMembers(): Collection
    {
        if (! Schema::hasTable('users')) {
            return new Collection();
        }

        $query = User::query()->where('status', 'active');

        if (Schema::hasColumn('users', 'last_seen_at')) {
            $query->orderByDesc('last_seen_at');
        } else {
            $query->latest();
        }

        return $query->limit(4)->get();
    }

    private function featuredPost(): ?FeedPost
    {
        if (! Schema::hasTable('feed_posts')) {
            return null;
        }

        return FeedPost::query()
            ->with('user.profile')
            ->withCount(['comments', 'reactions'])
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNull('team_id')
            ->latest()
            ->first();
    }

    private function featuredLfg(): ?LfgPost
    {
        if (! Schema::hasTable('lfg_posts')) {
            return null;
        }

        return LfgPost::query()
            ->with('user.profile')
            ->where('status', 'open')
            ->where('visibility', 'public')
            ->where(function ($query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->latest()
            ->first();
    }

    private function featuredCup(): ?Cup
    {
        if (! Schema::hasTable('cups')) {
            return null;
        }

        return Cup::query()
            ->visible()
            ->withCount(['activeTeams', 'submissions'])
            ->whereIn('status', ['active', 'planned', 'finished'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END")
            ->orderByDesc('starts_at')
            ->first();
    }

    private function recentMoments(): Collection
    {
        if (! Schema::hasTable('moments')) {
            return new Collection();
        }

        return Moment::query()
            ->with('user.profile')
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->orderByDesc('published_at')
            ->latest('id')
            ->limit(3)
            ->get();
    }
}
