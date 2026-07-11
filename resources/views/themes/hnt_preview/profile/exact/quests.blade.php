@php
    $profileQuests = collect($profileQuestPreview ?? [])->take(4)->values();
    $profileQuestClasses = ['quest-weekly', 'quest-daily', 'quest-moments', 'quest-social'];
    $profileQuestIcons = ['i-check', 'i-sliders', 'i-image', 'i-users'];
@endphp
<aside class="profile-section-nav profile-browser-column profile-quest-column">
<div class="profile-quest-list">
@forelse($profileQuests as $index => $quest)
@php
    $questProgress = $quest->progress->first();
    $questTarget = max(1, (int) ($quest->target_count ?? 1));
    $questCurrent = min($questTarget, max(0, (int) ($questProgress?->progress_count ?? 0)));
    $questPercent = (int) min(100, round(($questCurrent / $questTarget) * 100));
    $questClass = $profileQuestClasses[$index] ?? 'quest-weekly';
    $questIcon = $profileQuestIcons[$index] ?? 'i-check';
    $questLabel = $quest->is_weekly_contract
        ? 'WOCHENAUFTRAG'
        : match ($quest->period) {
            'daily' => 'TAGESAUFGABE',
            'weekly' => 'WOCHENAUFGABE',
            'monthly' => 'MONATSAUFGABE',
            default => strtoupper((string) ($quest->category ?: 'AUFGABE')),
        };
    $questDescription = trim((string) $quest->displayDescription(app()->getLocale()));
    if ($questDescription === '') {
        $questDescription = $quest->actionLabel();
    }
    $questTimeLabel = $quest->contract_ends_at && $quest->contract_ends_at->isFuture()
        ? 'Noch '.$quest->contract_ends_at->diffForHumans(now(), true)
        : $quest->periodLabel();
@endphp
<article class="profile-quest-card {{ $questClass }}">
<div class="profile-quest-head">
<span class="profile-quest-icon"><svg><use href="#{{ $questIcon }}"></use></svg></span>
<div>
<span>{{ $questLabel }}</span>
<h2>{{ $quest->displayName(app()->getLocale()) }}</h2>
</div>
<a aria-label="{{ $quest->displayName(app()->getLocale()) }} öffnen" class="profile-quest-arrow" href="{{ route('gamification.index') }}">
<svg><use href="#i-arrow"></use></svg>
</a>
</div>
<p>{{ $questDescription }}</p>
<div class="profile-quest-meta">
<strong>{{ $questCurrent }} / {{ $questTarget }}</strong>
<span>{{ $questPercent }}%</span>
</div>
<div class="profile-quest-progress"><i style="width:{{ $questPercent }}%"></i></div>
<footer>
<span>{{ $questTimeLabel }}</span>
<strong>+{{ (int) $quest->xp_reward }} XP</strong>
</footer>
</article>
@empty
<article class="profile-quest-card quest-weekly profile-quest-empty">
<div class="profile-quest-head">
<span class="profile-quest-icon"><svg><use href="#i-check"></use></svg></span>
<div><span>AUFGABEN</span><h2>Alles erledigt</h2></div>
<a aria-label="Aufgaben öffnen" class="profile-quest-arrow" href="{{ route('gamification.index') }}"><svg><use href="#i-arrow"></use></svg></a>
</div>
<p>Aktuell sind keine offenen Aufgaben vorhanden.</p>
<div class="profile-quest-meta"><strong>0 / 0</strong><span>100%</span></div>
<div class="profile-quest-progress"><i style="width:100%"></i></div>
<footer><span>Erledigt</span><strong>Sauber!</strong></footer>
</article>
@endforelse
</div>
<div class="profile-quest-footer">
<form action="{{ route('gamification.index') }}" method="get">
<button type="submit">
<span><b>{{ collect($profileQuestPreview ?? [])->count() }}</b> offene Aufgaben</span>
<span>Alle ansehen <svg><use href="#i-arrow"></use></svg></span>
</button>
</form>
</div>
</aside>
