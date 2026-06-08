@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'contracts-main')
@section('title', __('ui.contracts_meta_title'))

@php
    $contractsTotal = (int) $contracts->count();
    $completionPercent = $contractsTotal > 0 ? (int) round(($completedCount / max(1, $contractsTotal)) * 100) : 0;
    $levelProgress = max(0, min(100, (int) $levelProgressPercent));
    $number = static fn (int|float $value): string => number_format((float) $value, 0, app()->getLocale() === 'de' ? ',' : '.', app()->getLocale() === 'de' ? '.' : ',');
    $safeRoute = static function (string $routeName, string $fallback = '/') : string {
        return \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url($fallback);
    };
@endphp

@section('content')
<div class="contracts-shell">
    <nav class="gamification-tabs contracts-tabs" aria-label="{{ __('ui.contracts_title') }}">
        <a class="active" href="#contracts-active">{{ __('ui.contracts_tab_weekly') }} <span>{{ $contractsTotal }}</span></a>
        <a href="{{ $safeRoute('gamification.index', '/gamification') }}">{{ __('ui.badges_quests') }}</a>
    </nav>

    <section class="contracts-hero">
        <div class="contracts-hero-glow" aria-hidden="true"></div>
        <div class="contracts-hero-copy">
            <span class="contracts-kicker">{{ __('ui.contracts_kicker') }}</span>
            <h1>{{ __('ui.contracts_hero_title') }}</h1>
            <p>{{ __('ui.contracts_hero_text') }}</p>
        </div>
        <div class="contracts-hero-stats">
            <div>
                <strong>{{ (int) $completedCount }}/{{ $contractsTotal }}</strong>
                <span>{{ __('ui.contracts_completed') }}</span>
            </div>
            <div>
                <strong>{{ $number((int) $claimedXp) }}/{{ $number((int) $availableXp) }}</strong>
                <span>{{ __('ui.contracts_xp_collected') }}</span>
            </div>
        </div>
    </section>

    <section class="contracts-week-panel" aria-label="{{ __('ui.contracts_weekly_progress') }}">
        <div>
            <span>{{ __('ui.contracts_progress') }}</span>
            <h2>{{ __('ui.contracts_weekly_progress') }}</h2>
        </div>
        <strong>{{ $completionPercent }}%</strong>
        <div class="contracts-progress-track" aria-hidden="true"><span style="width: {{ $completionPercent }}%"></span></div>
    </section>

    <section class="contracts-grid" id="contracts-active">
        @forelse($contracts as $contract)
            @php
                $progress = $progressByQuestId->get($contract->id);
                $current = min((int) $contract->target_count, (int) ($progress?->progress_count ?? 0));
                $target = max(1, (int) $contract->target_count);
                $percent = min(100, (int) round(($current / $target) * 100));
                $isDone = filled($progress?->completed_at);
                $endsAt = $contract->contract_ends_at;
            @endphp
            <article class="contract-card {{ $isDone ? 'complete' : 'active' }}">
                <div class="contract-card-top">
                    <div class="contract-icon" aria-hidden="true">
                        <i class="ph ph-clipboard-text" aria-hidden="true"></i>
                    </div>
                    <div>
                        <small>{{ $contract->actionLabel() }}</small>
                        <h2>{{ $contract->name }}</h2>
                    </div>
                    <strong>+{{ $number((int) $contract->xp_reward) }} XP</strong>
                </div>

                @if($contract->description)
                    <p>{{ $contract->description }}</p>
                @endif

                <div class="contract-progress-meta">
                    <span>{{ $current }} / {{ $target }}</span>
                    <span>{{ $percent }}%</span>
                </div>
                <div class="contract-progress" aria-hidden="true"><span style="width: {{ $percent }}%"></span></div>

                <b>
                    {{ $isDone ? __('ui.contracts_done') : __('ui.contracts_open') }}
                    @if($endsAt)
                        · {{ __('ui.contracts_until') }} {{ $endsAt->format(app()->getLocale() === 'de' ? 'd.m.Y' : 'M j, Y') }}
                    @endif
                </b>
            </article>
        @empty
            <article class="gamification-empty-card gamification-empty-card-wide">
                <h2>{{ __('ui.contracts_empty_title') }}</h2>
                <p>{{ __('ui.contracts_empty_text') }}</p>
            </article>
        @endforelse
    </section>

    <section class="gamification-section">
        <div class="gamification-section-head">
            <div>
                <span>{{ __('ui.contracts_progress') }}</span>
                <h2>{{ __('ui.contracts_how_title') }}</h2>
            </div>
            <p>{{ __('ui.contracts_profile_text') }}</p>
        </div>

        <div class="quest-grid">
            <article class="quest-card">
                <div class="quest-card-top">
                    <div class="quest-icon" aria-hidden="true"><span>1</span></div>
                    <div>
                        <h3>{{ __('ui.contracts_tab_weekly') }}</h3>
                        <p>{{ __('ui.contracts_how_1') }}</p>
                    </div>
                </div>
            </article>
            <article class="quest-card">
                <div class="quest-card-top">
                    <div class="quest-icon" aria-hidden="true"><span>2</span></div>
                    <div>
                        <h3>{{ __('ui.contracts_xp_collected') }}</h3>
                        <p>{{ __('ui.contracts_how_2') }}</p>
                    </div>
                </div>
            </article>
            <article class="quest-card">
                <div class="quest-card-top">
                    <div class="quest-icon" aria-hidden="true"><span>3</span></div>
                    <div>
                        <h3>{{ __('ui.contracts_profile_title') }}</h3>
                        <p>{{ __('ui.contracts_how_3') }}</p>
                    </div>
                </div>
            </article>
            <article class="quest-card active">
                <div class="quest-card-top">
                    <div class="quest-icon" aria-hidden="true"><span>{{ (int) $user->level }}</span></div>
                    <div>
                        <h3>{{ __('ui.level') }} {{ (int) $user->level }}</h3>
                        <p>{{ __('ui.contracts_profile_text') }}</p>
                    </div>
                </div>
                <div class="quest-meta-row">
                    <span>{{ __('ui.contracts_progress') }}</span>
                    <strong>{{ $levelProgress }}%</strong>
                </div>
                <div class="game-progress-track" aria-hidden="true"><span style="width: {{ $levelProgress }}%"></span></div>
            </article>
        </div>
    </section>
</div>
@endsection
