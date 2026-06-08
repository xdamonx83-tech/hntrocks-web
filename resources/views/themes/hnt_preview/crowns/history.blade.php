@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'crowns-main')
@section('title', __('ui.preview_crowns_history_page_title'))

@php
    $balance = (int) ($summary['balance'] ?? 0);
@endphp

@section('content')
<div class="crowns-shell">
    <nav class="crowns-action-bar" aria-label="{{ __('ui.preview_crowns_nav_aria') }}">
        <a class="btn-create" href="{{ route('crowns.index') }}">{{ __('ui.preview_crowns_wallet') }}</a>
        <a class="btn-create" href="{{ route('crowns.shop') }}">{{ __('ui.preview_crowns_shop') }}</a>
        <a class="btn-create" href="{{ route('crowns.inventory') }}">{{ __('ui.preview_crowns_inventory') }}</a>
    </nav>

    <section class="crowns-shop-head crowns-inventory-head">
        <div>
            <span class="crowns-shop-kicker">{{ __('ui.preview_crowns_history') }}</span>
            <h1>{{ __('ui.preview_crowns_history_title') }}</h1>
            <p>{{ __('ui.preview_crowns_history_text') }}</p>
        </div>
        <strong class="crowns-balance-pill">♛ {{ number_format($balance, 0, ',', '.') }}</strong>
    </section>

    <section class="crowns-section crowns-history">
        <div class="crowns-section-head">
            <div>
                <span>{{ __('ui.preview_crowns_account') }}</span>
                <h2>{{ __('ui.preview_crowns_transactions') }}</h2>
            </div>
            <p>{{ __('ui.preview_crowns_newest_first') }}</p>
        </div>

        <div class="crowns-history-list">
            @forelse($transactions ?? [] as $transaction)
                @php
                    $amount = (int) $transaction->amount;
                @endphp
                <div>
                    <span>
                        {{ $transaction->description ?: $transaction->action }}
                        <small>{{ optional($transaction->created_at)->format('d.m.Y H:i') }} · {{ __('ui.preview_crowns_balance_after') }} {{ number_format((int) $transaction->balance_after, 0, ',', '.') }}</small>
                    </span>
                    <strong class="{{ $amount < 0 ? 'negative' : '' }}">{{ $amount >= 0 ? '+' : '' }}{{ number_format($amount, 0, ',', '.') }}</strong>
                </div>
            @empty
                <div>
                    <span>{{ __('ui.preview_crowns_history_empty') }}</span>
                    <strong>0</strong>
                </div>
            @endforelse
        </div>

        @if($transactions && $transactions->hasPages())
            <div class="hnt-crowns-pagination">
                {{ $transactions->links() }}
            </div>
        @endif
    </section>
</div>
@endsection
