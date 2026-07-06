@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'crowns-main')
@section('title', __('ui.preview_crowns_page_title'))

@php
    $balance = (int) ($summary['balance'] ?? 0);
    $earned = (int) ($summary['lifetime_earned'] ?? 0);
    $spent = (int) ($summary['lifetime_spent'] ?? 0);
    $dailyLoginClaimed = (bool) ($summary['daily_login_claimed'] ?? true);
    $dailyLoginAmount = (int) ($summary['daily_login_amount'] ?? 0);
    $pendingTotal = (int) data_get($pendingCollection ?? [], 'total', data_get($summary, 'pending_total', 0));
    $pendingCount = (int) data_get($pendingCollection ?? [], 'count', data_get($summary, 'pending_count', 0));
    $pendingTransactions = data_get($pendingCollection ?? [], 'transactions', collect());
    $visibleRewards = collect($rewardDefinitions ?? [])
        ->filter(fn ($definition, $action) => (int) data_get($definition, 'amount', 0) > 0)
        ->take(10);
@endphp

@section('content')
<div class="crowns-shell">
    <nav class="crowns-action-bar" aria-label="{{ __('ui.preview_crowns_nav_aria') }}">
        <a class="btn-create" href="{{ route('crowns.shop') }}">{{ __('ui.preview_crowns_shop') }}</a>
        <a class="btn-create" href="{{ route('crowns.inventory') }}">{{ __('ui.preview_crowns_inventory') }}</a>
        <a class="btn-create" href="{{ route('crowns.history') }}">{{ __('ui.preview_crowns_history') }}</a>
    </nav>

    @if(session('status'))
        <div class="hnt-crowns-alert success">{{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="hnt-crowns-alert warning">{{ session('error') }}</div>
    @endif

    <section class="crowns-hero">
        <div class="crowns-hero-glow" aria-hidden="true"></div>
        <div class="crowns-hero-copy">
            <span class="crowns-kicker">♛ HNT Bounty Marks</span>
            <h1>{{ number_format($balance, 0, ',', '.') }} Bounty Marks</h1>
            <p>{{ $nonCashNotice ?: __('ui.preview_crowns_default_notice') }}</p>
        </div>

        @if($pendingTotal > 0)
            <form method="POST" action="{{ route('crowns.collect') }}">
                @csrf
                <button class="btn-create crowns-claim" type="submit">+{{ number_format($pendingTotal, 0, ',', '.') }} {{ __('ui.preview_crowns_collect') }}</button>
            </form>
        @else
            <p class="crowns-claim-note">Daily Login Serie ist in der App verfügbar.</p>
        @endif

        <div class="crowns-stats">
            <div class="crowns-stat">
                <span>{{ __('ui.preview_crowns_current') }}</span>
                <strong>{{ number_format($balance, 0, ',', '.') }}</strong>
            </div>
            <div class="crowns-stat">
                <span>{{ __('ui.preview_crowns_earned') }}</span>
                <strong>{{ number_format($earned, 0, ',', '.') }}</strong>
            </div>
            <div class="crowns-stat">
                <span>{{ __('ui.preview_crowns_spent') }}</span>
                <strong>{{ number_format($spent, 0, ',', '.') }}</strong>
            </div>
        </div>
    </section>

    @if($pendingTotal > 0)
        <section class="crowns-section">
            <div class="crowns-section-head">
                <div>
                    <span>{{ __('ui.preview_crowns_ready') }}</span>
                    <h2>{{ __('ui.preview_crowns_waiting', ['count' => number_format($pendingTotal, 0, ',', '.')]) }}</h2>
                </div>
                <p>{{ $pendingCount }} {{ $pendingCount === 1 ? __('ui.preview_crowns_reward_one') : __('ui.preview_crowns_reward_many') }}</p>
            </div>
            <div class="crowns-history-list">
                @foreach($pendingTransactions as $transaction)
                    <div>
                        <span>{{ $transaction->description ?: $transaction->action }}</span>
                        <strong>+{{ number_format((int) $transaction->amount, 0, ',', '.') }}</strong>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="crowns-section">
        <div class="crowns-section-head">
            <div>
                <span>{{ __('ui.preview_crowns_earn') }}</span>
                <h2>{{ __('ui.preview_crowns_how_to_get') }}</h2>
            </div>
            <p>{{ __('ui.preview_crowns_activity_not_cash') }}</p>
        </div>

        <div class="crowns-earning-grid">
            @forelse($visibleRewards as $action => $definition)
                @php
                    $amount = (int) data_get($definition, 'amount', 0);
                    $limit = data_get($definition, 'daily_limit');
                    $label = (string) data_get($definition, 'description', $action);
                @endphp
                <article class="earning-row">
                    <div>
                        <h3>{{ $label }}</h3>
                        <p>{{ $limit === null ? __('ui.preview_crowns_reward_once') : __('ui.preview_crowns_reward_daily', ['limit' => $limit]) }}</p>
                    </div>
                    <strong>+{{ number_format($amount, 0, ',', '.') }}</strong>
                </article>
            @empty
                <article class="earning-row">
                    <div>
                        <h3>{{ __('ui.preview_crowns_no_rewards_title') }}</h3>
                        <p>{{ __('ui.preview_crowns_no_rewards_text') }}</p>
                    </div>
                    <strong>0</strong>
                </article>
            @endforelse
        </div>
    </section>
</div>
@endsection
