@extends('themes.rework.layouts.app')

@section('title', 'HNT.rocks Inventar / Inventory')
@section('body_class', 'shop-inventory-page')
@section('left_col_class', 'shop-left')

@section('content')
@php
    $isEnglish = app()->getLocale() === 'en';
    $inventoryUi = [
        'eyebrow' => 'Bounty Marks',
        'title' => $isEnglish ? 'Inventory' : 'Inventar',
        'intro' => $isEnglish
            ? 'Your purchased items, equipped cosmetics and unlocked rewards.'
            : 'Deine gekauften Items, ausgerüsteten Cosmetics und freigeschalteten Belohnungen.',
        'balance' => $isEnglish ? 'Your balance' : 'Dein Guthaben',
        'walletHint' => $isEnglish
            ? 'Manage your owned cosmetics and switch active profile upgrades.'
            : 'Verwalte deine gekauften Cosmetics und wechsle aktive Profil-Upgrades.',
        'shop' => 'Shop',
        'history' => $isEnglish ? 'History' : 'Verlauf',
        'wallet' => 'Wallet',
        'equipped' => $isEnglish ? 'Currently equipped' : 'Aktiv ausgerüstet',
        'owned' => $isEnglish ? 'Owned' : 'Im Besitz',
        'equip' => $isEnglish ? 'Equip' : 'Ausrüsten',
        'unequip' => $isEnglish ? 'Unequip' : 'Ablegen',
        'active' => $isEnglish ? 'Active' : 'Aktiv',
        'bought' => $isEnglish ? 'Bought' : 'Gekauft',
        'emptyTitle' => $isEnglish ? 'Your inventory is still empty' : 'Dein Inventar ist noch leer',
        'emptyText' => $isEnglish ? 'Purchased cosmetics and unlocked items will appear here.' : 'Gekaufte Cosmetics und freigeschaltete Items erscheinen hier.',
        'toShop' => $isEnglish ? 'Go to shop' : 'Zum Shop',
    ];

    $inventoryLabelFor = function (?string $value) use ($isEnglish): string {
        $key = strtolower((string) $value);

        return match ($key) {
            'avatar_frame', 'avatar-frame', 'avatarrahmen', 'frame' => $isEnglish ? 'Avatar frame' : 'Avatarrahmen',
            'username_glow', 'username-glow', 'username_color', 'username-color', 'username_colour', 'username-colour', 'username_effect', 'username-effect' => $isEnglish ? 'Username color' : 'Username-Farbe',
            'profile_card', 'profile-card', 'profilkarte' => $isEnglish ? 'Profile card' : 'Profilkarte',
            'profile_banner', 'profile-banner', 'banner', 'cover' => $isEnglish ? 'Profile banner' : 'Profilbanner',
            'title', 'titel', 'profile_title', 'profile-title' => $isEnglish ? 'Title' : 'Titel',
            'boost', 'booster' => 'Boost',
            'badge', 'cup', 'collectible' => $isEnglish ? 'Collectible' : 'Sammleritem',
            default => filled($value) ? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', (string) $value)) : ($isEnglish ? 'Item' : 'Item'),
        };
    };

    $inventoryCategoryKeyFor = function (?string $value): string {
        $key = strtolower(trim((string) $value));
        $key = str_replace([' ', '_'], '-', $key);

        if (str_contains($key, 'avatar') || str_contains($key, 'frame')) {
            return 'avatar-frame';
        }

        if (str_contains($key, 'profile-banner') || str_contains($key, 'profilbanner') || str_contains($key, 'banner') || str_contains($key, 'cover')) {
            return 'profile-banner';
        }

        if (str_contains($key, 'username') || str_contains($key, 'glow') || str_contains($key, 'color') || str_contains($key, 'colour')) {
            return 'username-effect';
        }

        if (str_contains($key, 'profile-title') || str_contains($key, 'title') || str_contains($key, 'titel')) {
            return 'profile-title';
        }

        if (str_contains($key, 'moment') || str_contains($key, 'overlay') || str_contains($key, 'studio')) {
            return str_contains($key, 'studio') ? 'moment-studio-feature' : 'moment-overlay';
        }

        if (str_contains($key, 'boost')) {
            return 'boost';
        }

        if (str_contains($key, 'cup') || str_contains($key, 'badge') || str_contains($key, 'collectible')) {
            return 'collectible';
        }

        return $key !== '' ? $key : 'misc';
    };

    $inventoryIconFor = function ($item): string {
        $key = strtolower((string) ($item->slot ?: $item->type ?: $item->key));

        if (str_contains($key, 'avatar') || str_contains($key, 'frame')) {
            return 'ph-frame-corners';
        }

        if (str_contains($key, 'username') || str_contains($key, 'glow') || str_contains($key, 'color') || str_contains($key, 'colour')) {
            return 'ph-palette';
        }

        if (str_contains($key, 'profile_card') || str_contains($key, 'profile-card') || str_contains($key, 'card')) {
            return 'ph-identification-card';
        }

        if (str_contains($key, 'banner') || str_contains($key, 'cover')) {
            return 'ph-image-square';
        }

        if (str_contains($key, 'title') || str_contains($key, 'titel')) {
            return 'ph-text-aa';
        }

        if (str_contains($key, 'boost')) {
            return 'ph-rocket-launch';
        }

        if (str_contains($key, 'cup') || str_contains($key, 'badge')) {
            return 'ph-trophy';
        }

        return filled($item->icon) ? (string) $item->icon : 'ph-backpack';
    };

    $balance = (int) ($summary['balance'] ?? 0);
    $ownedInventoryItems = collect($inventoryItems)->filter(fn ($inventoryItem) => $inventoryItem->shopItem !== null);
    $equippedCards = collect($equippedBySlot)
        ->filter(fn ($equipped) => $equipped?->inventoryItem?->shopItem !== null);
@endphp

<section class="members-head shop-head inventory-head">
    <div>
        <span class="members-eyebrow">{{ $inventoryUi['eyebrow'] }}</span>
        <h1>{{ $inventoryUi['title'] }}</h1>
        <p>{{ $inventoryUi['intro'] }}</p>
    </div>
</section>

@if(session('status'))
    <div class="inventory-alert success">{{ session('status') }}</div>
@endif

@if(session('error'))
    <div class="inventory-alert warning">{{ session('error') }}</div>
@endif

<section class="shop-wallet card inventory-wallet">
    <div class="shop-wallet-main">
        <img alt="Bounty Marks" src="{{ \App\Support\HntTheme::asset('images/shop-coin.webp', 'rework') }}"/>
        <div>
            <span>{{ $inventoryUi['balance'] }}</span>
            <strong>{{ number_format($balance, 0, ',', '.') }} Bounty Marks</strong>
            <p>{{ $inventoryUi['walletHint'] }}</p>
        </div>
    </div>
    <div class="shop-wallet-actions">
        <a class="btn" href="{{ route('crowns.shop') }}">{{ $inventoryUi['shop'] }}</a>
        <a class="btn secondary" href="{{ route('crowns.history') }}">{{ $inventoryUi['history'] }}</a>
        <a class="btn secondary" href="{{ route('crowns.index') }}">{{ $inventoryUi['wallet'] }}</a>
    </div>
</section>

@if($equippedCards->isNotEmpty())
    <section class="inventory-equipped-section card">
        <div class="inventory-section-head">
            <span>{{ $inventoryUi['active'] }}</span>
            <h2>{{ $inventoryUi['equipped'] }}</h2>
        </div>
        <div class="inventory-equipped-grid">
            @foreach($equippedCards as $slot => $equipped)
                @php
                    $shopItem = $equipped->inventoryItem?->shopItem;
                    $slotLabel = $inventoryLabelFor((string) $slot);
                    $iconClass = $inventoryIconFor($shopItem);
                @endphp
                <article class="inventory-equipped-card">
                    <div class="inventory-equipped-icon"><i aria-hidden="true" class="ph {{ $iconClass }} ph-icon"></i></div>
                    <div>
                        <span>{{ $slotLabel }}</span>
                        <strong>{{ $shopItem->displayName() }}</strong>
                    </div>
                    <form method="post" action="{{ route('crowns.inventory.unequip', $slot) }}">
                        @csrf
                        <button type="submit">{{ $inventoryUi['unequip'] }}</button>
                    </form>
                </article>
            @endforeach
        </div>
    </section>
@endif

<section aria-label="{{ $inventoryUi['title'] }}" class="shop-grid inventory-grid">
    @forelse($ownedInventoryItems as $inventoryItem)
        @php
            $item = $inventoryItem->shopItem;
            $isEquipped = $inventoryItem->isEquipped();
            $rawCategory = $item->slot ?: $item->type ?: $item->key;
            $typeLabel = $inventoryLabelFor($rawCategory);
            $iconClass = $inventoryIconFor($item);
            $rarity = strtolower((string) ($item->rarity ?? 'common'));
            $boughtAt = optional($inventoryItem->purchased_at)->format('d.m.Y') ?? '-';
        @endphp

        <article class="shop-item-card card inventory-item-card {{ $isEquipped ? 'is-equipped' : '' }}" data-inventory-category="{{ $inventoryCategoryKeyFor($rawCategory) }}">
            <div class="shop-item-top">
                <div class="inventory-card-badges">
                    <span class="shop-rarity {{ $rarity }}">{{ $typeLabel }}</span>
                    @if($isEquipped)
                        <span class="inventory-active-badge">{{ $inventoryUi['active'] }}</span>
                    @endif
                </div>
                <div class="shop-item-icon"><i aria-hidden="true" class="ph {{ $iconClass }} ph-icon"></i></div>
            </div>

            <h2>{{ $item->displayName() }}</h2>
            <p class="shop-slot">{{ $typeLabel }}</p>
            <p class="shop-desc">{{ $item->displayDescription() }}</p>

            <div class="shop-stats inventory-stats inventory-stats-compact">
                <span>{{ $inventoryUi['bought'] }}</span>
                <strong>{{ $boughtAt }}</strong>
            </div>

            <div class="shop-item-footer inventory-item-footer">
                @if($item->isActivatable())
                    @if($isEquipped)
                        <form method="post" action="{{ route('crowns.inventory.unequip', $item->slot) }}">
                            @csrf
                            <button type="submit">{{ $inventoryUi['unequip'] }}</button>
                        </form>
                    @else
                        <form method="post" action="{{ route('crowns.inventory.equip', $inventoryItem) }}">
                            @csrf
                            <button type="submit">{{ $inventoryUi['equip'] }}</button>
                        </form>
                    @endif
                @else
                    <span class="inventory-passive-pill">{{ $inventoryUi['owned'] }}</span>
                @endif
            </div>
        </article>
    @empty
        <article class="shop-item-card card inventory-empty-card">
            <div class="shop-item-top">
                <span class="shop-rarity">{{ $inventoryUi['title'] }}</span>
                <div class="shop-item-icon"><i aria-hidden="true" class="ph ph-backpack ph-icon"></i></div>
            </div>
            <h2>{{ $inventoryUi['emptyTitle'] }}</h2>
            <p class="shop-slot">{{ $inventoryUi['eyebrow'] }}</p>
            <p class="shop-desc">{{ $inventoryUi['emptyText'] }}</p>
            <div class="shop-item-footer">
                <b>{{ $inventoryUi['owned'] }}</b>
                <a href="{{ route('crowns.shop') }}">{{ $inventoryUi['toShop'] }}</a>
            </div>
        </article>
    @endforelse
</section>
@endsection
