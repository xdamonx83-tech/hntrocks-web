@extends('themes.socialite.layouts.app')

@section('title', __('ui.crowns_meta_title'))
@section('meta_description', __('ui.crowns_meta_description'))

@section('content')
@php
    $balance = (int) ($summary['balance'] ?? 0);
    $earned = (int) ($summary['lifetime_earned'] ?? 0);
    $spent = (int) ($summary['lifetime_spent'] ?? 0);
    $dailyLoginClaimed = (bool) ($summary['daily_login_claimed'] ?? true);
    $dailyLoginAmount = (int) ($summary['daily_login_amount'] ?? 0);
    $pendingTotal = (int) data_get($pendingCollection ?? [], 'total', data_get($summary, 'pending_total', 0));
    $pendingCount = (int) data_get($pendingCollection ?? [], 'count', data_get($summary, 'pending_count', 0));
    $visibleRewards = collect($rewardDefinitions)
        ->filter(fn ($definition, $action) => (int) data_get($definition, 'amount', 0) > 0)
        ->take(12);
@endphp

<div class="2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-crowns-page">
    <div class="page-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="page-title">{{ __('ui.crowns_title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/60">{{ __('ui.crowns_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('crowns.shop') }}" class="button bg-primary text-white">
                    <i class="ph ph-storefront" aria-hidden="true"></i>
                    {{ __('ui.crowns_shop_link') }}
                </a>
                <a href="{{ route('crowns.inventory') }}" class="button bg-secondery text-black dark:text-white">
                    <i class="ph ph-backpack" aria-hidden="true"></i>
                    {{ __('ui.crowns_inventory_link') }}
                </a>
                <a href="{{ route('crowns.history') }}" class="button bg-secondery text-black dark:text-white">
                    <i class="ph ph-clock-clockwise" aria-hidden="true"></i>
                    {{ __('ui.crowns_history_link') }}
                </a>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="box p-4 mb-5 border border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="box p-4 mb-5 border border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-200">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid lg:grid-cols-[1fr_340px] gap-6">
        <div class="min-w-0">
            <section id="crowns-wallet" class="box overflow-hidden mb-6">
                <div class="relative isolate min-h-[320px] p-6 sm:p-8 text-white" style="background-image: linear-gradient(90deg, rgba(3, 7, 18, .94) 0%, rgba(3, 7, 18, .78) 42%, rgba(3, 7, 18, .34) 72%, rgba(3, 7, 18, .72) 100%), url('{{ asset('assets/hnt/crowns/crowns-wallet-bg.webp') }}'); background-size: cover; background-position: center right;">
                    <div class="absolute inset-0 -z-10 bg-gradient-to-br from-amber-500/10 via-transparent to-red-950/30"></div>
                    <div class="relative z-10 flex flex-wrap items-start justify-between gap-6">
                        <div>
                            <span class="inline-flex items-center gap-2 rounded-full border border-amber-300/20 bg-black/35 px-3 py-1 text-xs font-bold uppercase tracking-wide text-amber-100 shadow-sm backdrop-blur-sm">
                                <i class="ph ph-crown" aria-hidden="true"></i>
                                {{ __('ui.crowns_kicker') }}
                            </span>
                            <h2 class="mt-4 text-3xl sm:text-4xl font-black text-white drop-shadow">{{ number_format($balance, 0, ',', '.') }} Crowns</h2>
                            <p class="mt-3 max-w-2xl text-sm text-white/75 drop-shadow">{{ __('ui.crowns_intro') }}</p>
                        </div>
                        @if($pendingTotal > 0)
                            <form method="POST" action="{{ route('crowns.collect') }}">
                                @csrf
                                <button type="submit" class="button bg-primary text-white shadow-lg">
                                    +{{ number_format($pendingTotal, 0, ',', '.') }} Crowns abholen
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('crowns.daily-login') }}">
                                @csrf
                                <button type="submit" class="button bg-primary text-white shadow-lg disabled:opacity-60 disabled:cursor-not-allowed" @disabled($dailyLoginClaimed)>
                                    {{ $dailyLoginClaimed ? __('ui.crowns_daily_done') : __('ui.crowns_daily_claim', ['amount' => $dailyLoginAmount]) }}
                                </button>
                            </form>
                        @endif
                    </div>
                    <div class="relative z-10 grid sm:grid-cols-3 gap-3 mt-6">
                        <div class="rounded-xl border border-white/10 bg-black/35 p-4 shadow-sm backdrop-blur-sm">
                            <div class="text-xs font-bold uppercase tracking-wide text-white/55">{{ __('ui.crowns_balance') }}</div>
                            <div class="mt-1 text-2xl font-black text-white">{{ number_format($balance, 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-black/35 p-4 shadow-sm backdrop-blur-sm">
                            <div class="text-xs font-bold uppercase tracking-wide text-white/55">{{ __('ui.crowns_lifetime_earned') }}</div>
                            <div class="mt-1 text-2xl font-black text-white">{{ number_format($earned, 0, ',', '.') }}</div>
                        </div>
                        <div class="rounded-xl border border-white/10 bg-black/35 p-4 shadow-sm backdrop-blur-sm">
                            <div class="text-xs font-bold uppercase tracking-wide text-white/55">{{ __('ui.crowns_lifetime_spent') }}</div>
                            <div class="mt-1 text-2xl font-black text-white">{{ number_format($spent, 0, ',', '.') }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="crowns-rewards" class="box p-5 sm:p-6 mb-6">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                    <div>
                        <p class="text-sm font-semibold text-yellow-600 uppercase tracking-wide">{{ __('ui.crowns_rewards_kicker') }}</p>
                        <h2 class="mt-1 text-xl font-bold text-black dark:text-white">{{ __('ui.crowns_rewards_title') }}</h2>
                    </div>
                    <span class="text-xs font-semibold text-gray-500 dark:text-white/60">{{ __('ui.crowns_rewards_limited') }}</span>
                </div>

                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach($visibleRewards as $action => $definition)
                        @php
                            $amount = (int) data_get($definition, 'amount', 0);
                            $limit = data_get($definition, 'daily_limit');
                            $label = __('ui.crowns_reward_' . str_replace(['.', '-'], '_', $action));
                            if ($label === 'ui.crowns_reward_' . str_replace(['.', '-'], '_', $action)) {
                                $label = (string) data_get($definition, 'description', $action);
                            }
                        @endphp
                        <article class="rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="font-bold text-black dark:text-white">{{ $label }}</h3>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-white/60">
                                        {{ $limit === null ? __('ui.crowns_reward_once_source') : __('ui.crowns_reward_daily_limit', ['count' => $limit]) }}
                                    </p>
                                </div>
                                <strong class="rounded-full bg-yellow-100 px-3 py-1 text-sm text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-200">+{{ $amount }}</strong>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

        </div>

        <aside class="space-y-5">
            <section class="box p-5">
                <h3 class="font-bold text-base text-black dark:text-white">{{ __('ui.crowns_notice_title') }}</h3>
                <p class="mt-2 text-sm text-gray-500 dark:text-white/70">{{ $nonCashNotice }}</p>
            </section>
        </aside>
    </div>
</div>
@endsection
