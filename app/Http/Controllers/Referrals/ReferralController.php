<?php

namespace App\Http\Controllers\Referrals;

use App\Http\Controllers\Controller;
use App\Models\ReferralSignup;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class ReferralController extends Controller
{
    public function index(Request $request, ReferralService $referrals): View
    {
        $user = $request->user();
        $link = $referrals->linkFor($user);
        $summary = $referrals->entrySummary($user);

        $successfulReferrals = ReferralSignup::query()
            ->where('referrer_id', $user->id)
            ->whereNotNull('profile_completed_at')
            ->count();

        $recentSignups = ReferralSignup::query()
            ->with('referredUser.profile')
            ->where('referrer_id', $user->id)
            ->latest()
            ->limit(12)
            ->get();

        return view('referrals.index', [
            'link' => $link,
            'referralUrl' => URL::to(route('referrals.accept', $link->code, false)),
            'summary' => $summary,
            'giveaway' => $summary['giveaway'],
            'successfulReferrals' => $successfulReferrals,
            'recentSignups' => $recentSignups,
        ]);
    }

    public function accept(Request $request, string $code, ReferralService $referrals): RedirectResponse
    {
        $link = $referrals->trackClick($code);

        if (! $link) {
            return redirect()
                ->route('register')
                ->with('status', 'Referral-Link wurde nicht gefunden. Du kannst dich trotzdem registrieren.');
        }

        if ($request->user() && (int) $request->user()->id !== (int) $link->user_id) {
            return redirect()
                ->route('referrals.index')
                ->with('status', 'Du bist bereits eingeloggt. Der Referral-Code wurde deshalb nicht auf deinen bestehenden Account angewendet.');
        }

        $request->session()->put('referral_code', $link->code);
        $request->session()->put('referral_referrer_name', $link->user?->name ?: 'ein hnt.rocks-Mitglied');

        return redirect()
            ->route('register')
            ->with('status', 'Referral-Link erkannt. Registriere dich, damit die Einladung gezählt werden kann.');
    }
}
