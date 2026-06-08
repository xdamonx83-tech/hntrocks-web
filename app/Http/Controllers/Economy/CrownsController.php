<?php

namespace App\Http\Controllers\Economy;

use App\Http\Controllers\Controller;
use App\Models\CrownTransaction;
use App\Services\Economy\CrownsService;
use App\Support\HntTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CrownsController extends Controller
{
    public function index(Request $request, CrownsService $crowns): View
    {
        $user = $request->user();
        $crowns->rewardCompletedProfileIfEligible($user);

        return view($this->themeView('crowns.index'), [
            'user' => $user,
            'summary' => $crowns->summary($user),
            'pendingCollection' => $crowns->pendingCollection($user, 6, true),
            'rewardDefinitions' => config('crowns.rewards', []),
            'nonCashNotice' => (string) config('crowns.non_cash_notice'),
        ]);
    }

    public function history(Request $request, CrownsService $crowns): View
    {
        $user = $request->user();
        $crowns->rewardCompletedProfileIfEligible($user);

        $transactions = $crowns->enabled()
            ? CrownTransaction::query()
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(30)
                ->withQueryString()
            : null;

        return view($this->themeView('crowns.history'), [
            'summary' => $crowns->summary($user),
            'transactions' => $transactions,
        ]);
    }

    public function claimDailyLogin(Request $request, CrownsService $crowns): RedirectResponse
    {
        $transaction = $crowns->rewardDailyLogin($request->user());

        if ($transaction) {
            // The explicit daily-login button is already a collect action.
            // It may also collect other waiting rewards from the Crowns page.
            $crowns->collectPending($request->user());
        }

        return back()->with(
            $transaction ? 'status' : 'error',
            $transaction ? __('ui.crowns_daily_claimed') : __('ui.crowns_daily_already_claimed')
        );
    }

    public function collectPending(Request $request, CrownsService $crowns): RedirectResponse
    {
        $count = $crowns->collectPending($request->user());

        return back()->with(
            $count > 0 ? 'status' : 'error',
            $count > 0 ? __('ui.crowns_collect_success') : __('ui.crowns_collect_empty')
        );
    }

    public function dismissPending(Request $request, CrownsService $crowns): RedirectResponse|JsonResponse
    {
        $count = $crowns->dismissPendingCollection($request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'dismissed' => $count,
            ]);
        }

        return back();
    }
    private function themeView(string $view): string
    {
        return HntTheme::enabled()
            ? HntTheme::resolve($view)
            : 'themes.socialite.' . $view;
    }
}

