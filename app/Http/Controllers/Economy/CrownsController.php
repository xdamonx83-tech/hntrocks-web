<?php

namespace App\Http\Controllers\Economy;

use App\Http\Controllers\Controller;
use App\Models\CrownTransaction;
use App\Services\Economy\CrownsService;
use App\Support\HntTheme;
use App\Support\ReworkFeedSidebar;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View as ViewFactory;
use Illuminate\View\View;

class CrownsController extends Controller
{
    public function index(Request $request, CrownsService $crowns): View
    {
        $user = $request->user();
        $crowns->rewardCompletedProfileIfEligible($user);

        $recentTransactions = $crowns->enabled()
            ? CrownTransaction::query()
                ->where('user_id', $user->id)
                ->latest()
                ->limit(8)
                ->get()
            : collect();


        $sidebarData = ReworkFeedSidebar::forViewer($user);

        return view($this->themeView('crowns.index'), [
            'user' => $user,
            'summary' => $crowns->summary($user),
            'pendingCollection' => $crowns->pendingCollection($user, 6, true),
            'recentTransactions' => $recentTransactions,
            'rewardDefinitions' => config('crowns.rewards', []),
            'nonCashNotice' => (string) config('crowns.non_cash_notice'),
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
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

        $historyView = HntTheme::previewLive()
            && ViewFactory::exists('themes.hnt_preview.crowns.history')
                ? 'themes.hnt_preview.crowns.history'
                : $this->themeView('crowns.history');

        return view($historyView, [
            'summary' => $crowns->summary($user),
            'transactions' => $transactions,
        ]);
    }

    public function claimDailyLogin(Request $request): RedirectResponse
    {
        return back()->with('error', 'Die tägliche Belohnung ist nur in der App verfügbar.');
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
