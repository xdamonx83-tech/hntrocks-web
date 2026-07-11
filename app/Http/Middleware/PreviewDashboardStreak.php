<?php

namespace App\Http\Middleware;

use App\Models\CrownDailyStreakClaim;
use App\Models\XpEvent;
use App\Services\Economy\CrownDailyStreakService;
use App\Support\HntTheme;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreviewDashboardStreak
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('admin.theme-preview.shell') || ! $request->boolean('dashboard_streak')) {
            return $next($request);
        }

        $user = $request->user();

        abort_unless(
            $user?->isAdmin() && HntTheme::previewActive($user),
            403
        );

        return response()->json([
            'daily_streak' => app(CrownDailyStreakService::class)->status($user),
            'activity' => $this->activityPayload((int) $user->id),
        ]);
    }

    private function activityPayload(int $userId): array
    {
        $timezone = (string) config('app.timezone', 'UTC');
        $now = now($timezone);
        $periodStart = $now->copy()->startOfDay()->subDays(29);
        $weekStart = $now->copy()->startOfWeek();

        $events = XpEvent::query()
            ->where('user_id', $userId)
            ->where('created_at', '>=', $periodStart)
            ->get(['created_at', 'points']);

        $loginClaims = CrownDailyStreakClaim::query()
            ->where('user_id', $userId)
            ->whereDate('claim_date', '>=', $periodStart->toDateString())
            ->get(['claim_date']);

        $countsByDate = [];

        for ($offset = 0; $offset < 30; $offset++) {
            $date = $periodStart->copy()->addDays($offset)->toDateString();
            $countsByDate[$date] = 0;
        }

        foreach ($events as $event) {
            $date = $event->created_at?->copy()->timezone($timezone)->toDateString();

            if ($date && array_key_exists($date, $countsByDate)) {
                $countsByDate[$date]++;
            }
        }

        foreach ($loginClaims as $claim) {
            $date = $claim->claim_date?->toDateString();

            if ($date && array_key_exists($date, $countsByDate)) {
                $countsByDate[$date]++;
            }
        }

        $days = collect($countsByDate)
            ->map(function (int $count, string $date): array {
                $level = match (true) {
                    $count <= 0 => 0,
                    $count === 1 => 1,
                    $count <= 3 => 2,
                    $count <= 6 => 3,
                    default => 4,
                };

                return [
                    'date' => $date,
                    'count' => $count,
                    'level' => $level,
                ];
            })
            ->values();

        $xpThisWeek = $events
            ->filter(fn (XpEvent $event): bool => $event->created_at?->gte($weekStart) ?? false)
            ->sum(fn (XpEvent $event): int => max(0, (int) $event->points));

        return [
            'period_days' => 30,
            'active_days' => $days->where('count', '>', 0)->count(),
            'actions' => $days->sum('count'),
            'xp_this_week' => (int) $xpThisWeek,
            'days' => $days,
        ];
    }
}
