@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'crowns-main crowns-shop-main')
@section('title', __('ui.preview_crowns_shop_page_title'))

@php
    $balance = (int) ($summary['balance'] ?? 0);
    $likePreviewText = match (app()->getLocale()) { 'de' => 'Effekt ansehen', 'es' => 'Ver efecto', 'ru' => 'Посмотреть эффект', default => 'Preview effect' };
    $ownedLookup = array_flip($ownedItemIds ?? []);
    $slotLabels = [
        'avatar_frame' => __('ui.preview_crowns_slot_avatar_frame'),
        'profile_banner' => __('ui.preview_crowns_slot_profile_banner'),
        'username_effect' => __('ui.preview_crowns_slot_username_effect'),
        'profile_title' => __('ui.preview_crowns_slot_profile_title'),
        'like_effect' => match (app()->getLocale()) { 'de' => 'Like-Effekt', 'es' => 'Efecto de Me gusta', 'ru' => 'Эффект лайка', default => 'Like effect' },
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
                    @if($item->type === 'like_effect')
 <button type="button" class="hnt-like-preview-button" data-hnt-like-preview="{{ $item->key }}" aria-label="{{ $likePreviewText }}: {{ $item->displayName() }}"><span class="hnt-like-preview-heart" aria-hidden="true">
  @if(str_starts_with($item->key, 'like_effect_thumb_'))
    <svg viewBox="0 0 24 24" width="21" height="21" fill="currentColor"><path d="M8.1 9.6 12.4 3.5c.8-1.1 2.7-.7 2.7 1l-.5 4.6h5.1c1.9 0 2.7 1.4 2.2 3l-2.1 8c-.3 1.1-1.3 1.7-2.3 1.7H8.1Z M2.1 9.7h4.7v12H2.1a1 1 0 0 1-1-1v-10a1 1 0 0 1 1-1Z"/></svg>
  @elseif(str_starts_with($item->key, 'like_effect_heart_'))
    <svg viewBox="0 0 24 24" width="21" height="21" fill="currentColor"><path d="M12 21s-9.4-5.8-9.4-12.2a5.1 5.1 0 0 1 9.4-2.9 5.1 5.1 0 0 1 9.4 2.9C21.4 15.2 12 21 12 21Z"/></svg>
  @else
    &#9825;
  @endif
</span><span>{{ $likePreviewText }}</span><span aria-hidden="true">↗</span></button>
@endif
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

@push('head')
<link rel="stylesheet" href="/build/next/hntv3-js/like-effect-shop.css?v=heart-thumb-20261017"/>
@endpush
@push('scripts')
<script defer src="/build/next/hntv3-js/like-embers.js?v=heart-thumb-20261017"></script>
@endpush
