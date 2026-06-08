@auth
    @php
        $hhAchievementToasts = session()->pull('hunthub_achievement_toasts', []);
    @endphp

    <div class="hh-achievement-stack" data-hh-achievement-stack data-pending-url="{{ route('gamification.achievements.pending') }}" aria-live="polite" aria-atomic="false">
        @foreach ($hhAchievementToasts as $hhAchievementToast)
            @php
                $hhAchievementIcon = $hhAchievementToast['icon'] ?? null;
                $hhAchievementType = $hhAchievementToast['type'] ?? 'achievement';
                $hhAchievementXp = (int) ($hhAchievementToast['xp'] ?? 0);
            @endphp
            <article class="hh-achievement-toast is-preloaded" data-hh-achievement-toast data-achievement-type="{{ e($hhAchievementType) }}">
                <a class="hh-achievement-toast-link" href="{{ e($hhAchievementToast['url'] ?? route('gamification.index')) }}">
                    <span class="hh-achievement-toast-icon" aria-hidden="true">
                        @if ($hhAchievementIcon)
                            <img src="{{ e($hhAchievementIcon) }}" alt="">
                        @else
                            <svg><use xlink:href="#svg-badges"></use></svg>
                        @endif
                    </span>
                    <span class="hh-achievement-toast-content">
                        <span class="hh-achievement-toast-eyebrow">{{ $hhAchievementToast['eyebrow'] ?? __('ui.achievement_unlocked') }}</span>
                        <strong>{{ $hhAchievementToast['title'] ?? __('ui.gamification') }}</strong>
                        @if (! empty($hhAchievementToast['body']))
                            <span>{{ $hhAchievementToast['body'] }}</span>
                        @endif
                        @if ($hhAchievementXp > 0)
                            <em>{{ __('ui.achievement_xp_reward', ['xp' => $hhAchievementXp]) }}</em>
                        @endif
                    </span>
                </a>
                <button class="hh-achievement-toast-close" type="button" data-hh-achievement-toast-close aria-label="{{ __('ui.achievement_close') }}">×</button>
            </article>
        @endforeach
    </div>
@endauth
