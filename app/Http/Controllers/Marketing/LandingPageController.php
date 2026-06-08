<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\LoadoutChallenge;
use App\Models\Moment;
use App\Models\User;
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
            'cups' => $this->publicCupsCount(),
            'loadouts' => $this->activeLoadoutChallengesCount(),
            'moments' => $this->publicMomentsCount(),
        ];

        return view('themes.socialite.landing.index', compact('stats'));
    }

    private function activeMembersCount(): int
    {
        if (! Schema::hasTable('users')) {
            return 0;
        }

        return (int) User::query()
            ->where('status', 'active')
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

    private function activeLoadoutChallengesCount(): int
    {
        if (! Schema::hasTable('loadout_challenges')) {
            return 0;
        }

        return (int) LoadoutChallenge::query()
            ->publicVisible()
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
}
