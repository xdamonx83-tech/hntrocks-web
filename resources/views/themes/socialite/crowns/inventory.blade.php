@extends('themes.socialite.layouts.app')

@section('title', __('ui.crowns_inventory_meta_title'))
@section('meta_description', __('ui.crowns_inventory_meta_description'))

@section('content')
@php
    $balance = (int) ($summary['balance'] ?? 0);
    $rarityClasses = [
        'common' => 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-white/80',
        'rare' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200',
        'epic' => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-200',
        'legendary' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
        'event' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200',
    ];
@endphp

<div class="2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-crowns-inventory-page">
    <div class="page-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="page-title">{{ __('ui.crowns_inventory_title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/60">{{ __('ui.crowns_inventory_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('crowns.index') }}" class="button bg-secondery text-black dark:text-white">
                    <i class="ph ph-crown" aria-hidden="true"></i>
                    {{ __('ui.crowns_back_to_wallet') }}
                </a>
                <a href="{{ route('crowns.shop') }}" class="button bg-primary text-white">
                    <i class="ph ph-storefront" aria-hidden="true"></i>
                    {{ __('ui.crowns_shop_link') }}
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

    <section class="box p-5 sm:p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-yellow-600 uppercase tracking-wide">{{ __('ui.crowns_inventory_kicker') }}</p>
                <h2 class="mt-1 text-xl font-bold text-black dark:text-white">{{ number_format($balance, 0, ',', '.') }} Bounty Marks</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/60">{{ __('ui.crowns_inventory_notice') }}</p>
            </div>
        </div>
    </section>

    @if($equippedBySlot->isNotEmpty())
        <section class="box p-5 sm:p-6 mb-6">
            <div class="mb-4">
                <p class="text-sm font-semibold text-yellow-600 uppercase tracking-wide">{{ __('ui.crowns_equipped_kicker') }}</p>
                <h2 class="mt-1 text-xl font-bold text-black dark:text-white">{{ __('ui.crowns_equipped_title') }}</h2>
            </div>
            <div class="grid md:grid-cols-2 gap-3">
                @foreach($equippedBySlot as $slot => $equipped)
                    @php
                        $shopItem = $equipped->inventoryItem?->shopItem;
                    @endphp
                    @if($shopItem)
                        <article class="rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-xs font-bold uppercase tracking-wide text-gray-400">{{ __('ui.crowns_shop_slot_' . $slot) }}</p>
                                    <h3 class="truncate font-bold text-black dark:text-white">{{ $shopItem->displayName() }}</h3>
                                </div>
                                <form method="POST" action="{{ route('crowns.inventory.unequip', $slot) }}">
                                    @csrf
                                    <button type="submit" class="button bg-secondery text-black dark:text-white">{{ __('ui.crowns_inventory_unequip') }}</button>
                                </form>
                            </div>
                        </article>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <section class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($inventoryItems as $inventoryItem)
            @php
                $item = $inventoryItem->shopItem;
                $rarity = strtolower((string) ($item?->rarity ?? 'common'));
                $rarityClass = $rarityClasses[$rarity] ?? $rarityClasses['common'];
                $isEquipped = $inventoryItem->isEquipped();
            @endphp
            @if($item)
                <article class="box p-5">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-sm">
                            <i class="ph ph-{{ $item->icon ?: 'sparkle' }} text-3xl" aria-hidden="true"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $rarityClass }}">{{ __('ui.crowns_rarity_' . $rarity) }}</span>
                                @if($isEquipped)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-200">{{ __('ui.crowns_inventory_active') }}</span>
                                @endif
                            </div>
                            <h2 class="mt-3 font-black text-black dark:text-white">{{ $item->displayName() }}</h2>
                            <p class="mt-1 text-xs text-gray-500 dark:text-white/60">{{ __('ui.crowns_shop_slot_' . $item->slot) }}</p>
                        </div>
                    </div>

                    <p class="mt-4 text-sm text-gray-500 dark:text-white/65">{{ $item->displayDescription() }}</p>

                    <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs text-gray-400">{{ __('ui.crowns_inventory_bought_at', ['date' => optional($inventoryItem->purchased_at)->format('d.m.Y') ?? '—']) }}</span>

                        @if($item->isActivatable())
                            @if($isEquipped)
                                <form method="POST" action="{{ route('crowns.inventory.unequip', $item->slot) }}">
                                    @csrf
                                    <button type="submit" class="button bg-secondery text-black dark:text-white">{{ __('ui.crowns_inventory_unequip') }}</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('crowns.inventory.equip', $inventoryItem) }}">
                                    @csrf
                                    <button type="submit" class="button bg-primary text-white">{{ __('ui.crowns_inventory_equip') }}</button>
                                </form>
                            @endif
                        @endif
                    </div>
                </article>
            @endif
        @empty
            <div class="box p-8 text-center text-sm text-gray-500 dark:text-white/60 md:col-span-2 xl:col-span-3">
                {{ __('ui.crowns_inventory_empty') }}
                <div class="mt-4">
                    <a href="{{ route('crowns.shop') }}" class="button bg-primary text-white">{{ __('ui.crowns_shop_link') }}</a>
                </div>
            </div>
        @endforelse
    </section>
</div>
@endsection
