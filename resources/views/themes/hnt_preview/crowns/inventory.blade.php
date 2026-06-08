@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'crowns-main crowns-shop-main')
@section('title', __('ui.preview_crowns_inventory_page_title'))

@php
    $balance = (int) ($summary['balance'] ?? 0);
    $slotLabels = [
        'avatar_frame' => __('ui.preview_crowns_slot_avatar_frame'),
        'profile_banner' => __('ui.preview_crowns_slot_profile_banner'),
        'username_effect' => __('ui.preview_crowns_slot_username_effect'),
        'profile_title' => __('ui.preview_crowns_slot_profile_title'),
        'moment_overlay' => __('ui.preview_crowns_slot_moment_overlay'),
    ];
    $slotIcons = [
        'avatar_frame' => '◎',
        'profile_banner' => '▭',
        'username_effect' => '✦',
        'profile_title' => '♛',
        'moment_overlay' => '✦',
    ];
@endphp

@section('content')
<div class="crowns-shell crowns-shop-shell">
    <nav class="crowns-action-bar crowns-shop-actions" aria-label="{{ __('ui.preview_crowns_nav_aria') }}">
        <a class="btn-create" href="{{ route('crowns.index') }}">{{ __('ui.preview_crowns_wallet') }}</a>
        <a class="btn-create" href="{{ route('crowns.shop') }}">{{ __('ui.preview_crowns_shop') }}</a>
        <a class="btn-create" href="{{ route('crowns.history') }}">{{ __('ui.preview_crowns_history') }}</a>
    </nav>

    @if(session('status'))
        <div class="hnt-crowns-alert success">{{ session('status') }}</div>
    @endif

    @if(session('error'))
        <div class="hnt-crowns-alert warning">{{ session('error') }}</div>
    @endif

    <section class="crowns-shop-head crowns-inventory-head">
        <div>
            <span class="crowns-shop-kicker">{{ __('ui.preview_crowns_inventory') }}</span>
            <h1>{{ __('ui.preview_crowns_inventory_title') }}</h1>
            <p>{{ __('ui.preview_crowns_inventory_text') }}</p>
        </div>
        <strong class="crowns-balance-pill">♛ {{ number_format($balance, 0, ',', '.') }}</strong>
    </section>

    @if($equippedBySlot->isNotEmpty())
        <section class="inventory-active-section">
            <div class="inventory-section-heading">
                <span>{{ __('ui.preview_crowns_active') }}</span>
                <h2>{{ __('ui.preview_crowns_equipped') }}</h2>
            </div>
            <div class="inventory-active-grid">
                @foreach($equippedBySlot as $slot => $equipped)
                    @php
                        $shopItem = $equipped->inventoryItem?->shopItem;
                        $slotLabel = $slotLabels[(string) $slot] ?? (string) $slot;
                    @endphp
                    @if($shopItem)
                        <article class="inventory-slot-row">
                            <div>
                                <span>{{ $slotLabel }}</span>
                                <strong>{{ $shopItem->displayName() }}</strong>
                            </div>
                            <form method="POST" action="{{ route('crowns.inventory.unequip', $slot) }}">
                                @csrf
                                <button class="btn-create danger" type="submit">{{ __('ui.preview_crowns_unequip') }}</button>
                            </form>
                        </article>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section class="crowns-shop-grid inventory-shop-grid">
        @forelse($inventoryItems as $inventoryItem)
            @php
                $item = $inventoryItem->shopItem;
                $rarity = strtolower((string) ($item?->rarity ?? 'common'));
                $isEquipped = $inventoryItem->isEquipped();
                $slot = (string) ($item?->slot ?? '');
                $slotLabel = $slotLabels[$slot] ?? ($slot !== '' ? $slot : __('ui.preview_crowns_slot_cosmetic'));
                $slotIcon = $slotIcons[$slot] ?? '✦';
            @endphp
            @if($item)
                <article class="shop-item-card inventory-shop-card {{ $item->preview_class }} {{ $isEquipped ? 'is-equipped' : '' }} rarity-{{ $rarity }}">
                    <div class="shop-item-meta">
                        <div class="inventory-shop-badges">
                            <span class="shop-rarity {{ $rarity }}">{{ ucfirst($rarity) }}</span>
                            @if($isEquipped)
                                <span class="inventory-active-badge">{{ __('ui.preview_crowns_active') }}</span>
                            @endif
                        </div>
                        <span class="shop-item-icon" aria-hidden="true"><span>{{ $slotIcon }}</span></span>
                    </div>

                    <h2>{{ $item->displayName() }}</h2>
                    <p class="shop-item-type">{{ $slotLabel }}</p>
                    <p class="shop-item-description">{{ $item->displayDescription() }}</p>

                    <div class="shop-item-footer inventory-shop-footer">
                        <strong class="inventory-date">{{ __('ui.preview_crowns_bought') }} {{ optional($inventoryItem->purchased_at)->format('d.m.Y') ?? '—' }}</strong>

                        @if($item->isActivatable())
                            @if($isEquipped)
                                <form method="POST" action="{{ route('crowns.inventory.unequip', $item->slot) }}">
                                    @csrf
                                    <button class="btn-create danger" type="submit">{{ __('ui.preview_crowns_unequip') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('crowns.inventory.equip', $inventoryItem) }}">
                                    @csrf
                                    <button class="btn-create" type="submit">{{ __('ui.preview_crowns_equip') }}</button>
                                </form>
                            @endif
                        @else
                            <span class="inventory-passive-pill">{{ __('ui.preview_crowns_owned_passive') }}</span>
                        @endif
                    </div>
                </article>
            @endif
        @empty
            <div class="hnt-crowns-empty">
                {{ __('ui.preview_crowns_inventory_empty') }}
                <a class="btn-create" href="{{ route('crowns.shop') }}">{{ __('ui.preview_crowns_to_shop') }}</a>
            </div>
        @endforelse
    </section>
</div>
@endsection
