@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'crowns-main crowns-shop-main')
@section('title', __('ui.preview_crowns_shop_page_title'))

@php
    $balance = (int) ($summary['balance'] ?? 0);
    $ownedLookup = array_flip($ownedItemIds ?? []);
    $slotLabels = [
        'avatar_frame' => __('ui.preview_crowns_slot_avatar_frame'),
        'profile_banner' => __('ui.preview_crowns_slot_profile_banner'),
        'username_effect' => __('ui.preview_crowns_slot_username_effect'),
        'profile_title' => __('ui.preview_crowns_slot_profile_title'),
    ];
    $slotIcons = [
        'avatar_frame' => '◎',
        'profile_banner' => '▭',
        'username_effect' => '✦',
        'profile_title' => '♛',
    ];
@endphp

@section('content')
<div class="crowns-shell crowns-shop-shell">
    <nav class="crowns-action-bar" aria-label="{{ __('ui.preview_crowns_nav_aria') }}">
        <a class="btn-create" href="{{ route('crowns.index') }}">{{ __('ui.preview_crowns_wallet') }}</a>
        <a class="btn-create" href="{{ route('crowns.inventory') }}">{{ __('ui.preview_crowns_inventory') }}</a>
        <a class="btn-create" href="{{ route('crowns.history') }}">{{ __('ui.preview_crowns_history') }}</a>
    </nav>

    @if(session('status'))
        <div class="hnt-crowns-alert success">{{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="hnt-crowns-alert warning">{{ session('error') }}</div>
    @endif

    <section class="crowns-shop-head">
        <div>
            <span class="crowns-shop-kicker">{{ __('ui.preview_crowns_shop_kicker') }}</span>
            <h1>{{ __('ui.preview_crowns_shop_title') }}</h1>
            <p>{{ $nonCashNotice ?: __('ui.preview_crowns_shop_notice') }}</p>
        </div>
        <strong class="crowns-balance-pill">♛ {{ number_format($balance, 0, ',', '.') }}</strong>
    </section>

    <section class="crowns-shop-grid">
        @forelse($items as $item)
            @php
                $owned = isset($ownedLookup[(int) $item->id]);
                $rarity = strtolower((string) $item->rarity ?: 'common');
                $canAfford = $balance >= (int) $item->price;
                $slot = (string) $item->slot;
                $slotLabel = $slotLabels[$slot] ?? ($slot !== '' ? $slot : __('ui.preview_crowns_slot_cosmetic'));
                $slotIcon = $slotIcons[$slot] ?? '✦';
            @endphp
            <article class="shop-item-card {{ $item->preview_class }}">
                <div class="shop-item-meta">
                    <span class="shop-rarity {{ $rarity }}">{{ ucfirst($rarity) }}</span>
                    <span class="shop-item-icon" aria-hidden="true"><span>{{ $slotIcon }}</span></span>
                </div>
                <h2>{{ $item->displayName() }}</h2>
                <p class="shop-item-type">{{ $slotLabel }}</p>
                <p class="shop-item-description">{{ $item->displayDescription() }}</p>
                <div class="shop-item-footer">
                    <strong><span>♛</span>{{ number_format((int) $item->price, 0, ',', '.') }}</strong>
                    @if($owned)
                        <a class="btn-create" href="{{ route('crowns.inventory') }}">{{ __('ui.preview_crowns_owned') }}</a>
                    @else
                        <form method="POST" action="{{ route('crowns.shop.purchase', $item) }}">
                            @csrf
                            <button class="btn-create" type="submit" @if(! $canAfford) disabled @endif>
                                {{ $canAfford ? __('ui.preview_crowns_buy') : __('ui.preview_crowns_not_enough') }}
                            </button>
                        </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="hnt-crowns-empty">{{ __('ui.preview_crowns_shop_empty') }}</div>
        @endforelse
    </section>
</div>
@endsection
