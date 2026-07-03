@extends('themes.rework.layouts.app')

@section('title', 'HNT.rocks Shop')
@section('body_class', 'shop-page')
@section('left_col_class', 'shop-left')

@section('content')
@php
    $isEnglish = app()->getLocale() === 'en';
    $shopUi = [
        'eyebrow' => 'Bounty Marks',
        'title' => 'Shop',
        'intro' => $isEnglish
            ? 'Cosmetics, profile upgrades and small extras for your HNT.rocks identity.'
            : 'Cosmetics, Profil-Upgrades und kleine Extras für deine HNT.rocks-Identität.',
        'balance' => $isEnglish ? 'Your balance' : 'Dein Guthaben',
        'walletHint' => $isEnglish
            ? 'Earn Marks through activity, quests, feed posts and cup participation.'
            : 'Verdiene Marks durch Aktivität, Quests, Feed-Beiträge und Cup-Teilnahmen.',
        'inventory' => $isEnglish ? 'Inventory' : 'Inventar',
        'history' => $isEnglish ? 'History' : 'Verlauf',
        'all' => $isEnglish ? 'All' : 'Alle',
        'owned' => $isEnglish ? 'Owned' : 'Im Besitz',
        'active' => $isEnglish ? 'Active' : 'Aktiv',
        'yes' => $isEnglish ? 'Yes' : 'Ja',
        'no' => $isEnglish ? 'No' : 'Nein',
        'buy' => $isEnglish ? 'Buy' : 'Kaufen',
        'equip' => $isEnglish ? 'Equip' : 'Ausrüsten',
        'equipped' => $isEnglish ? 'Active' : 'Aktiv',
        'notEnough' => $isEnglish ? 'Not enough Marks' : 'Zu wenig Marks',
        'availableKicker' => 'Bounty Marks',
        'availableTitle' => $isEnglish ? 'Available in shop' : 'Verfügbar im Shop',
        'allOwnedText' => $isEnglish ? 'You currently own all available shop items.' : 'Du besitzt aktuell alle verfügbaren Shop-Items.',
        'ownedKicker' => $isEnglish ? 'Inventory' : 'Inventar',
        'ownedTitle' => $isEnglish ? 'Already in inventory' : 'Bereits im Inventar',
        'ownedText' => $isEnglish
            ? 'You already own these items. Activatable items can be equipped here or in your inventory.'
            : 'Diese Items besitzt du bereits. Du kannst aktivierbare Items hier oder im Inventar ausrüsten.',
        'ownedBadge' => $isEnglish ? 'Owned' : 'Bereits im Inventar',
        'viewInventory' => $isEnglish ? 'View inventory' : 'Im Inventar ansehen',
        'emptyTitle' => $isEnglish ? 'No shop items available' : 'Keine Shop-Items verfügbar',
        'emptyText' => $isEnglish ? 'New cosmetics will appear here later.' : 'Neue Cosmetics erscheinen später hier.',
    ];

    $shopLabelFor = function (?string $value) use ($isEnglish): string {
        $key = strtolower((string) $value);

        return match ($key) {
            'avatar_frame', 'avatar-frame', 'avatarrahmen', 'frame' => $isEnglish ? 'Avatar frame' : 'Avatarrahmen',
            'username_glow', 'username-glow', 'username_color', 'username-color', 'username_colour', 'username-colour' => $isEnglish ? 'Username color' : 'Username-Farbe',
            'profile_card', 'profile-card', 'profilkarte' => $isEnglish ? 'Profile card' : 'Profilkarte',
            'profile_banner', 'profile-banner', 'banner', 'cover' => $isEnglish ? 'Profile banner' : 'Profilbanner',
            'title', 'titel' => $isEnglish ? 'Title' : 'Titel',
            'boost', 'booster' => 'Boost',
            'badge', 'cup', 'collectible' => $isEnglish ? 'Collectible' : 'Sammleritem',
            default => filled($value) ? \Illuminate\Support\Str::headline(str_replace(['_', '-'], ' ', (string) $value)) : ($isEnglish ? 'Item' : 'Item'),
        };
    };

    $shopCategoryKeyFor = function (?string $value): string {
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

        if (str_contains($key, 'profile-title') || str_contains($key, 'profile-title') || str_contains($key, 'title') || str_contains($key, 'titel')) {
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

    $shopIconFor = function ($item): string {
        $key = strtolower((string) ($item->slot ?: $item->type ?: $item->key));

        if (str_contains($key, 'avatar') || str_contains($key, 'frame')) {
            return 'ph-frame-corners';
        }

        if (str_contains($key, 'username') || str_contains($key, 'glow') || str_contains($key, 'color') || str_contains($key, 'colour')) {
            return 'ph-palette';
        }

        if (str_contains($key, 'profile_card') || str_contains($key, 'card')) {
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

        return filled($item->icon) ? (string) $item->icon : 'ph-tag';
    };

    $balance = (int) ($summary['balance'] ?? 0);
    $shopCategories = collect($items)
        ->mapWithKeys(function ($item) use ($shopLabelFor, $shopCategoryKeyFor): array {
            $rawCategory = $item->slot ?: $item->type ?: $item->key;
            return [$shopCategoryKeyFor($rawCategory) => $shopLabelFor($rawCategory)];
        })
        ->filter()
        ->unique()
        ->sort()
        ->all();

    $availableShopItems = collect($items)->reject(function ($item) use ($inventoryByItemId, $ownedItemIds): bool {
        return $inventoryByItemId->has($item->id) || in_array((int) $item->id, $ownedItemIds ?? [], true);
    });

    $ownedShopItems = collect($items)->filter(function ($item) use ($inventoryByItemId, $ownedItemIds): bool {
        return $inventoryByItemId->has($item->id) || in_array((int) $item->id, $ownedItemIds ?? [], true);
    });
@endphp

<section class="members-head shop-head">
    <div>
        <span class="members-eyebrow">{{ $shopUi['eyebrow'] }}</span>
        <h1>{{ $shopUi['title'] }}</h1>
        <p>{{ $shopUi['intro'] }}</p>
    </div>
</section>

<section class="shop-wallet card">
    <div class="shop-wallet-main">
        <img alt="Bounty Marks" src="{{ \App\Support\HntTheme::asset('images/shop-coin.webp', 'rework') }}"/>
        <div>
            <span>{{ $shopUi['balance'] }}</span>
            <strong>{{ number_format($balance, 0, ',', '.') }} Bounty Marks</strong>
            <p>{{ $shopUi['walletHint'] }}</p>
        </div>
    </div>
    <div class="shop-wallet-actions">
        <a class="btn" href="{{ route('crowns.inventory') }}">{{ $shopUi['inventory'] }}</a>
        <a class="btn secondary" href="{{ route('crowns.history') }}">{{ $shopUi['history'] }}</a>
    </div>
</section>

<section aria-label="Shop Kategorien" class="shop-tabs" data-shop-tabs>
    <a class="active" href="#" data-shop-filter="all">{{ $shopUi['all'] }}</a>
    @foreach($shopCategories as $categoryKey => $categoryLabel)
        <a href="#" data-shop-filter="{{ $categoryKey }}">{{ $categoryLabel }}</a>
    @endforeach
</section>

<section class="shop-section shop-available-section">
    <div class="shop-section-head">
        <span>{{ $shopUi['availableKicker'] }}</span>
        <h2>{{ $shopUi['availableTitle'] }}</h2>
    </div>

    <section aria-label="Shop Items" class="shop-grid">
        @forelse($availableShopItems as $item)
            @php
                $inventoryItem = $inventoryByItemId->get($item->id);
                $isOwned = $inventoryItem !== null || in_array((int) $item->id, $ownedItemIds ?? [], true);
                $isEquipped = $inventoryItem?->isEquipped() ?? false;
                $isAffordable = $balance >= (int) $item->price;
                $typeLabel = $shopLabelFor($item->slot ?: $item->type);
                $iconClass = $shopIconFor($item);
            @endphp

            <article class="shop-item-card card" data-shop-item data-shop-category="{{ $shopCategoryKeyFor($item->slot ?: $item->type ?: $item->key) }}">
                <div class="shop-item-top">
                    <span class="shop-rarity">{{ $typeLabel }}</span>
                    <div class="shop-item-icon"><i aria-hidden="true" class="ph {{ $iconClass }} ph-icon"></i></div>
                </div>

                <h2>{{ $item->displayName() }}</h2>
                <p class="shop-slot">{{ $typeLabel }}</p>
                <p class="shop-desc">{{ $item->displayDescription() }}</p>

                <div class="shop-stats">
                    <span>{{ $shopUi['owned'] }}</span>
                    <strong>{{ $isOwned ? $shopUi['yes'] : $shopUi['no'] }}</strong>
                    <span>{{ $shopUi['active'] }}</span>
                    <strong>{{ $isEquipped ? $shopUi['yes'] : $shopUi['no'] }}</strong>
                </div>

                <div class="shop-item-footer">
                    <b><img alt="" src="{{ \App\Support\HntTheme::asset('images/shop-coin.webp', 'rework') }}"/>{{ number_format((int) $item->price, 0, ',', '.') }}</b>

                    <form method="post" action="{{ route('crowns.shop.purchase', $item) }}">
                        @csrf
                        <button type="submit" @disabled(! $isAffordable)>{{ $isAffordable ? $shopUi['buy'] : $shopUi['notEnough'] }}</button>
                    </form>
                </div>
            </article>
        @empty
            @if(collect($items)->isEmpty())
                <article class="shop-item-card card">
                    <div class="shop-item-top">
                        <span class="shop-rarity">{{ $shopUi['title'] }}</span>
                        <div class="shop-item-icon"><i aria-hidden="true" class="ph ph-shopping-bag-open ph-icon"></i></div>
                    </div>
                    <h2>{{ $shopUi['emptyTitle'] }}</h2>
                    <p class="shop-slot">{{ $shopUi['eyebrow'] }}</p>
                    <p class="shop-desc">{{ $shopUi['emptyText'] }}</p>
                </article>
            @else
                <article class="shop-owned-empty card">
                    <p>{{ $shopUi['allOwnedText'] }}</p>
                </article>
            @endif
        @endforelse
    </section>
</section>

@if($ownedShopItems->isNotEmpty())
    <section class="shop-section shop-owned-section">
        <div class="shop-section-head">
            <span>{{ $shopUi['ownedKicker'] }}</span>
            <h2>{{ $shopUi['ownedTitle'] }}</h2>
            <p>{{ $shopUi['ownedText'] }}</p>
        </div>

        <section aria-label="{{ $shopUi['ownedTitle'] }}" class="shop-grid">
            @foreach($ownedShopItems as $item)
                @php
                    $inventoryItem = $inventoryByItemId->get($item->id);
                    $isEquipped = $inventoryItem?->isEquipped() ?? false;
                    $typeLabel = $shopLabelFor($item->slot ?: $item->type);
                    $iconClass = $shopIconFor($item);
                @endphp

                <article class="shop-item-card card is-owned shop-owned-card" data-shop-item data-shop-category="{{ $shopCategoryKeyFor($item->slot ?: $item->type ?: $item->key) }}">
                    <div class="shop-item-top">
                        <span class="shop-rarity">{{ $typeLabel }}</span>
                        <div class="shop-owned-badges">
                            <span class="shop-owned-badge">{{ $shopUi['ownedBadge'] }}</span>
                            @if($isEquipped)
                                <span class="shop-owned-badge is-active">{{ $shopUi['equipped'] }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="shop-item-icon"><i aria-hidden="true" class="ph {{ $iconClass }} ph-icon"></i></div>

                    <h2>{{ $item->displayName() }}</h2>
                    <p class="shop-slot">{{ $typeLabel }}</p>
                    <p class="shop-desc">{{ $item->displayDescription() }}</p>

                    <div class="shop-stats">
                        <span>{{ $shopUi['owned'] }}</span>
                        <strong>{{ $shopUi['yes'] }}</strong>
                        <span>{{ $shopUi['active'] }}</span>
                        <strong>{{ $isEquipped ? $shopUi['yes'] : $shopUi['no'] }}</strong>
                    </div>

                    <div class="shop-item-footer">
                        <span class="shop-owned-price"><img alt="" src="{{ \App\Support\HntTheme::asset('images/shop-coin.webp', 'rework') }}"/>{{ number_format((int) $item->price, 0, ',', '.') }}</span>

                        @if($item->isActivatable() && ! $isEquipped && $inventoryItem)
                            <form method="post" action="{{ route('crowns.inventory.equip', $inventoryItem) }}">
                                @csrf
                                <button type="submit">{{ $shopUi['equip'] }}</button>
                            </form>
                        @elseif($isEquipped)
                            <button type="button" disabled>{{ $shopUi['equipped'] }}</button>
                        @else
                            <a href="{{ route('crowns.inventory') }}">{{ $shopUi['viewInventory'] }}</a>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>
    </section>
@endif
@endsection
