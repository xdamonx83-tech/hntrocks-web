@extends('themes.socialite.layouts.app')

@section('title', __('ui.contracts_meta_title'))
@section('meta_description', __('ui.contracts_meta_description'))

@section('content')
@php
    $contractsTotal = $contracts->count();
    $completionPercent = $contractsTotal > 0 ? (int) round(($completedCount / max(1, $contractsTotal)) * 100) : 0;
@endphp

<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-contracts-page">
    <div class="flex-1 min-w-0">
        <div class="page-heading">
            <h1 class="page-title">{{ __('ui.contracts_title') }}</h1>
            <nav class="nav__underline">
                <ul class="group">
                    <li class="uk-active"><a href="#contracts-active">{{ __('ui.contracts_tab_weekly') }}</a></li>
                    <li><a href="{{ route('gamification.index') }}">{{ __('ui.badges_quests') }}</a></li>
                </ul>
            </nav>
        </div>

        <section class="hnt-contract-hero box overflow-hidden mb-6">
            <div class="hnt-contract-hero-bg"></div>
            <div class="hnt-contract-hero-content">
                <div>
                    <span class="hnt-contract-kicker">{{ __('ui.contracts_kicker') }}</span>
                    <h2>{{ __('ui.contracts_hero_title') }}</h2>
                    <p>{{ __('ui.contracts_hero_text') }}</p>
                </div>
                <div class="hnt-contract-hero-stats">
                    <div>
                        <strong>{{ $completedCount }}/{{ $contractsTotal }}</strong>
                        <span>{{ __('ui.contracts_completed') }}</span>
                    </div>
                    <div>
                        <strong>{{ number_format($claimedXp, 0, ',', '.') }}/{{ number_format($availableXp, 0, ',', '.') }}</strong>
                        <span>{{ __('ui.contracts_xp_collected') }}</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="box p-5 sm:p-6 mb-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-blue-600 uppercase tracking-wide">{{ __('ui.contracts_progress') }}</p>
                    <h2 class="mt-1 text-xl font-bold text-black dark:text-white">{{ __('ui.contracts_weekly_progress') }}</h2>
                </div>
                <strong class="text-2xl text-black dark:text-white">{{ $completionPercent }}%</strong>
            </div>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-secondery dark:bg-white/10">
                <div class="h-full rounded-full bg-blue-600" style="width: {{ $completionPercent }}%"></div>
            </div>
        </section>

        <section id="contracts-active" class="grid md:grid-cols-2 gap-4">
            @forelse($contracts as $contract)
                @php
                    $progress = $progressByQuestId->get($contract->id);
                    $current = min((int) $contract->target_count, (int) ($progress?->progress_count ?? 0));
                    $target = max(1, (int) $contract->target_count);
                    $percent = min(100, (int) round(($current / $target) * 100));
                    $isDone = filled($progress?->completed_at);
                @endphp
                <article class="hnt-contract-card {{ $isDone ? 'is-done' : '' }}">
                    <div class="hnt-contract-card-head">
                        <div class="hnt-contract-icon">
                            <ion-icon name="clipboard-outline"></ion-icon>
                        </div>
                        <div class="min-w-0">
                            <span>{{ $contract->actionLabel() }}</span>
                            <h3>{{ $contract->name }}</h3>
                        </div>
                        <strong>{{ $contract->xp_reward }} XP</strong>
                    </div>

                    @if($contract->description)
                        <p class="hnt-contract-description">{{ $contract->description }}</p>
                    @endif

                    <div class="hnt-contract-progress-line">
                        <span>{{ $current }}/{{ $target }}</span>
                        <span>{{ $percent }}%</span>
                    </div>
                    <div class="hnt-contract-progress-track">
                        <div style="width: {{ $percent }}%"></div>
                    </div>

                    <div class="hnt-contract-meta">
                        @if($isDone)
                            <span class="is-complete">{{ __('ui.contracts_done') }}</span>
                        @else
                            <span>{{ __('ui.contracts_open') }}</span>
                        @endif
                        @if($contract->contract_ends_at)
                            <span>{{ __('ui.contracts_until') }} {{ $contract->contract_ends_at->format('d.m.Y') }}</span>
                        @endif
                    </div>
                </article>
            @empty
                <div class="card p-8 text-center md:col-span-2">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-secondery dark:bg-white/10">
                        <ion-icon name="clipboard-outline" class="text-3xl text-gray-500"></ion-icon>
                    </div>
                    <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.contracts_empty_title') }}</h3>
                    <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.contracts_empty_text') }}</p>
                </div>
            @endforelse
        </section>
    </div>

    <aside class="2xl:w-[360px] lg:w-[330px] w-full">
        <div class="box p-5 mb-5">
            <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.contracts_how_title') }}</h3>
            <ul class="hnt-contract-help-list mt-4">
                <li>{{ __('ui.contracts_how_1') }}</li>
                <li>{{ __('ui.contracts_how_2') }}</li>
                <li>{{ __('ui.contracts_how_3') }}</li>
            </ul>
        </div>
        <div class="box p-5">
            <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.contracts_profile_title') }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ __('ui.contracts_profile_text') }}</p>
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-secondery dark:bg-white/10">
                <div class="h-full rounded-full bg-blue-600" style="width: {{ (int) $levelProgressPercent }}%"></div>
            </div>
            <div class="mt-3 flex justify-between text-sm font-semibold text-gray-500">
                <span>Level {{ $user->level }}</span>
                <span>{{ (int) $levelProgressPercent }}%</span>
            </div>
        </div>
    </aside>
</div>
@endsection
