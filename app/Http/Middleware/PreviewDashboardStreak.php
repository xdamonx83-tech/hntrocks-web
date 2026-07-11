<?php

namespace App\Http\Middleware;

use App\Models\CrownDailyStreakClaim;
use App\Models\Cup;
use App\Models\LfgPost;
use App\Models\LoadoutChallenge;
use App\Models\MomentSpotlight;
use App\Models\Quest;
use App\Models\User;
use App\Models\XpEvent;
use App\Services\Economy\CrownDailyStreakService;
use App\Support\HntTheme;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PreviewDashboardStreak
{
    public function handle(Request $request, Closure $next): Response
    {
        $isActivityRequest = $request->boolean('dashboard_streak');
        $isAgendaRequest = $request->boolean('dashboard_agenda');

        if (! $request->routeIs('admin.theme-preview.shell') || (! $isActivityRequest && ! $isAgendaRequest)) {
            return $next($request);
        }

        $user = $request->user();

        abort_unless(
            $user?->isAdmin() && HntTheme::previewActive($user),
            403
        );

        if ($isAgendaRequest) {
            return response()->json([
                'agenda' => $this->agendaPayload($user),
            ]);
        }

        return response()->json([
            'daily_streak' => app(CrownDailyStreakService::class)->status($user),
            'activity' => $this->activityPayload((int) $user->id),
        ]);
    }

    private function agendaPayload(User $user): array
    {
        $now = now((string) config('app.timezone', 'UTC'));
        $user->loadMissing('profile');
        $items = collect();
        $unreadMessages = max(0, (int) $user->unreadMessagesCount());

        if ($unreadMessages > 0) {
            $items->push([
                'type' => 'messages',
                'timeframe' => 'now',
                'axis' => 'JETZT',
                'style' => 'dark',
                'eyebrow' => 'INBOX',
                'title' => $unreadMessages === 1 ? '1 neue Nachricht' : $unreadMessages.' neue Nachrichten',
                'meta' => 'Ungelesene Nachrichten warten auf dich',
                'url' => route('messages.index'),
                'button' => 'Öffnen',
                'progress' => null,
                'avatars' => [],
            ]);
        }

        $profile = $user->profile;
        $lfgQuery = LfgPost::query()
            ->with('user.profile')
            ->where('status', 'open')
            ->where('visibility', 'public')
            ->where('user_id', '!=', $user->id)
            ->whereColumn('slots_filled', '<', 'slots_total')
            ->where(function ($query) use ($now): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>=', $now);
            });

        foreach (['platform', 'region', 'language', 'playstyle'] as $field) {
            $value = trim((string) ($profile?->{$field} ?? ''));
            if ($value !== '') {
                $lfgQuery->orderByRaw("CASE WHEN {$field} = ? THEN 0 ELSE 1 END", [$value]);
            }
        }

        $lfg = $lfgQuery->latest()->first();

        if ($lfg) {
            $tags = array_slice($lfg->displayTags(), 0, 3);
            $tags[] = $lfg->slotsOpen().' freie '.($lfg->slotsOpen() === 1 ? 'Platz' : 'Plätze');

            $items->push([
                'type' => 'lfg',
                'timeframe' => 'now',
                'axis' => 'JETZT',
                'style' => '',
                'eyebrow' => 'PASSENDES LFG',
                'title' => Str::limit((string) $lfg->title, 48),
                'meta' => implode(' · ', array_filter($tags)),
                'url' => route('lfg.show', $lfg),
                'button' => 'Ansehen',
                'progress' => null,
                'avatars' => array_values(array_filter([
                    $lfg->user?->avatarUrl(),
                ])),
            ]);
        }

        $cup = Cup::query()
            ->visible()
            ->withCount('activeTeams')
            ->whereIn('status', ['active', 'planned'])
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'planned' THEN 1 ELSE 2 END")
            ->orderByRaw('starts_at IS NULL')
            ->orderBy('starts_at')
            ->first();

        if ($cup) {
            $cupTimeframe = $cup->status === 'active'
                ? 'now'
                : $this->timeframeFor($cup->starts_at, $now);
            $teamLimit = (int) ($cup->max_teams ?: 0);
            $teams = (int) ($cup->active_teams_count ?? 0);
            $teamLabel = $teamLimit > 0 ? $teams.' / '.$teamLimit.' Teams' : $teams.' Teams';
            $details = array_filter([
                $teamLabel,
                $cup->team_size ? 'Teamgröße '.$cup->team_size : null,
                $cup->platform,
            ]);

            $items->push([
                'type' => 'cup',
                'timeframe' => $cupTimeframe,
                'axis' => $this->axisLabel($cup->starts_at, $cupTimeframe),
                'style' => 'cup',
                'eyebrow' => 'COMMUNITY CUP',
                'title' => Str::limit((string) $cup->title, 48),
                'meta' => implode(' · ', $details),
                'url' => route('cups.show', $cup),
                'button' => 'Zum Cup',
                'progress' => null,
                'avatars' => [],
            ]);
        }

        $contract = Quest::query()
            ->where('is_weekly_contract', true)
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('contract_starts_at')->orWhere('contract_starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('contract_ends_at')->orWhere('contract_ends_at', '>=', $now);
            })
            ->with(['progress' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderByRaw('contract_ends_at IS NULL')
            ->orderBy('contract_ends_at')
            ->orderBy('sort_order')
            ->get()
            ->first(function (Quest $quest): bool {
                return ! filled($quest->progress->first()?->completed_at);
            });

        if ($contract) {
            $progress = $contract->progress->first();
            $target = max(1, (int) $contract->target_count);
            $current = min($target, max(0, (int) ($progress?->progress_count ?? 0)));
            $percent = (int) min(100, round(($current / $target) * 100));
            $remaining = $contract->contract_ends_at
                ? max(0, $now->startOfDay()->diffInDays($contract->contract_ends_at->copy()->startOfDay(), false))
                : null;
            $remainingLabel = $remaining === null
                ? 'diese Woche'
                : ($remaining === 0 ? 'endet heute' : 'noch '.$remaining.' '.($remaining === 1 ? 'Tag' : 'Tage'));
            $timeframe = $this->timeframeFor($contract->contract_ends_at, $now, 'week');

            $items->push([
                'type' => 'contract',
                'timeframe' => $timeframe,
                'axis' => $this->axisLabel($contract->contract_ends_at, $timeframe),
                'style' => 'contract',
                'eyebrow' => 'WOCHENAUFTRAG',
                'title' => Str::limit($contract->displayName(app()->getLocale()), 48),
                'meta' => $current.' / '.$target.' · '.$remainingLabel.' · +'.(int) $contract->xp_reward.' XP',
                'url' => route('contracts.index'),
                'button' => 'Öffnen',
                'progress' => $percent,
                'avatars' => [],
            ]);
        }

        $challenge = LoadoutChallenge::query()
            ->publicVisible()
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->whereDoesntHave('submissions', fn ($query) => $query->where('user_id', $user->id))
            ->orderByDesc('is_featured')
            ->orderByRaw('ends_at IS NULL')
            ->orderBy('ends_at')
            ->first();

        if ($challenge) {
            $timeframe = $this->timeframeFor($challenge->ends_at, $now, 'week');
            $challengeMeta = trim((string) $challenge->summary);
            if ($challengeMeta === '') {
                $challengeMeta = 'Einreichung offen';
            }
            if ((int) $challenge->xp_reward > 0) {
                $challengeMeta .= ' · +'.(int) $challenge->xp_reward.' XP';
            }

            $items->push([
                'type' => 'challenge',
                'timeframe' => $timeframe,
                'axis' => $this->axisLabel($challenge->ends_at, $timeframe),
                'style' => 'challenge',
                'eyebrow' => 'LOADOUT CHALLENGE',
                'title' => Str::limit((string) $challenge->title, 48),
                'meta' => Str::limit($challengeMeta, 86),
                'url' => route('loadout-challenges.show', $challenge),
                'button' => 'Einreichen',
                'progress' => null,
                'avatars' => [],
            ]);
        }

        $spotlight = MomentSpotlight::query()
            ->with('moment.user.profile')
            ->published()
            ->whereHas('moment', fn ($query) => $query->published()->where('visibility', 'public'))
            ->latest('week_starts_at')
            ->latest('id')
            ->first();

        if ($spotlight?->moment) {
            $moment = $spotlight->moment;
            $title = trim((string) ($spotlight->title ?: $moment->caption ?: 'Moment der Woche'));

            $items->push([
                'type' => 'moment',
                'timeframe' => 'week',
                'axis' => 'WOCHE',
                'style' => 'highlight',
                'eyebrow' => 'MOMENT DER WOCHE',
                'title' => Str::limit($title, 48),
                'meta' => number_format((int) $moment->likes_count, 0, ',', '.').' Likes · '.number_format((int) $moment->comments_count, 0, ',', '.').' Kommentare · '.number_format((int) $moment->views_count, 0, ',', '.').' Views',
                'url' => route('moments.show', $moment),
                'button' => '▶',
                'progress' => null,
                'avatars' => [],
            ]);
        }

        $ordered = $items
            ->sortBy(fn (array $item): int => match ($item['timeframe']) {
                'now' => 0,
                'today' => 1,
                'tomorrow' => 2,
                default => 3,
            })
            ->take(7)
            ->values();

        return [
            'items' => $ordered,
            'counts' => [
                'now' => $ordered->where('timeframe', 'now')->count(),
                'today' => $ordered->whereIn('timeframe', ['now', 'today'])->count(),
                'tomorrow' => $ordered->where('timeframe', 'tomorrow')->count(),
                'week' => $ordered->count(),
            ],
        ];
    }

    private function timeframeFor(?Carbon $date, Carbon $now, string $fallback = 'week'): string
    {
        if (! $date) {
            return $fallback;
        }

        if ($date->isToday()) {
            return 'today';
        }

        if ($date->isTomorrow()) {
            return 'tomorrow';
        }

        return 'week';
    }

    private function axisLabel(?Carbon $date, string $timeframe): string
    {
        return match ($timeframe) {
            'now' => 'JETZT',
            'today' => $date?->format('H:i') ?: 'HEUTE',
            'tomorrow' => 'MORGEN',
            default => 'WOCHE',
        };
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
