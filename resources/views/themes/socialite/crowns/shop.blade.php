@extends('themes.socialite.layouts.app')

@section('title', __('ui.crowns_shop_meta_title'))
@section('meta_description', __('ui.crowns_shop_meta_description'))

@section('content')
@php
    $balance = (int) ($summary['balance'] ?? 0);
    $ownedLookup = array_flip($ownedItemIds ?? []);
    $rarityClasses = [
        'common' => 'bg-slate-100 text-slate-700 dark:bg-white/10 dark:text-white/80',
        'rare' => 'bg-sky-100 text-sky-700 dark:bg-sky-500/15 dark:text-sky-200',
        'epic' => 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-500/15 dark:text-fuchsia-200',
        'legendary' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-200',
        'event' => 'bg-red-100 text-red-700 dark:bg-red-500/15 dark:text-red-200',
    ];
@endphp

<div class="2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-crowns-shop-page">
    <div class="page-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="page-title">{{ __('ui.crowns_shop_title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/60">{{ __('ui.crowns_shop_subtitle') }}</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('crowns.index') }}" class="button bg-secondery text-black dark:text-white">
                    <i class="ph ph-crown" aria-hidden="true"></i>
                    {{ __('ui.crowns_back_to_wallet') }}
                </a>
                <a href="{{ route('crowns.inventory') }}" class="button bg-primary text-white">
                    <i class="ph ph-backpack" aria-hidden="true"></i>
                    {{ __('ui.crowns_inventory_link') }}
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
                <p class="text-sm font-semibold text-yellow-600 uppercase tracking-wide">{{ __('ui.crowns_shop_kicker') }}</p>
                <h2 class="mt-1 text-xl font-bold text-black dark:text-white">{{ number_format($balance, 0, ',', '.') }} Bounty Marks</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/60">{{ __('ui.crowns_shop_notice') }}</p>
            </div>
            <span class="inline-flex items-center gap-2 rounded-full bg-yellow-100 px-4 py-2 text-sm font-bold text-yellow-800 dark:bg-yellow-500/15 dark:text-yellow-200">
                <i class="ph ph-coins" aria-hidden="true"></i>
                {{ number_format($balance, 0, ',', '.') }}
            </span>
        </div>
    </section>

    <section class="grid md:grid-cols-2 xl:grid-cols-3 gap-4">
        @forelse($items as $item)
            @php
                $owned = isset($ownedLookup[(int) $item->id]);
                $rarity = strtolower((string) $item->rarity);
                $rarityClass = $rarityClasses[$rarity] ?? $rarityClasses['common'];
                $canAfford = $balance >= (int) $item->price;
            @endphp
            <article class="box overflow-hidden">
                <div class="relative min-h-[150px] bg-slate-950 p-5 text-white">
                    <div class="absolute inset-0 bg-[radial-gradient(circle_at_75%_25%,rgba(245,158,11,.22),transparent_30%),linear-gradient(135deg,rgba(127,29,29,.42),rgba(2,6,23,.96))]"></div>
                    <div class="absolute inset-x-0 bottom-0 h-16 bg-gradient-to-t from-black/65 to-transparent"></div>
                    <div class="relative flex items-start justify-between gap-4">
                        <div>
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold uppercase tracking-wide {{ $rarityClass }}">{{ __('ui.crowns_rarity_' . $rarity) }}</span>
                            <h2 class="mt-4 text-lg font-black leading-tight">{{ $item->displayName() }}</h2>
                            <p class="mt-2 text-xs text-white/65">{{ __('ui.crowns_shop_slot_' . $item->slot) }}</p>
                        </div>
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-black/35 shadow-lg backdrop-blur-sm">
                            <i class="ph ph-{{ $item->icon ?: 'sparkle' }} text-3xl" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>

                <div class="p-5">
                    <p class="min-h-[48px] text-sm text-gray-500 dark:text-white/65">{{ $item->displayDescription() }}</p>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <strong class="inline-flex items-center gap-2 text-lg text-black dark:text-white">
                            <i class="ph ph-crown text-yellow-600" aria-hidden="true"></i>
                            {{ number_format((int) $item->price, 0, ',', '.') }}
                        </strong>

                        @if($owned)
                            <a href="{{ route('crowns.inventory') }}" class="button bg-secondery text-black dark:text-white">
                                {{ __('ui.crowns_shop_owned') }}
                            </a>
                        @else
                            <form method="POST" action="{{ route('crowns.shop.purchase', $item) }}">
                                @csrf
                                <button type="submit" class="button {{ $canAfford ? 'bg-primary text-white' : 'bg-secondery text-black dark:text-white' }}" @disabled(! $canAfford)>
                                    {{ $canAfford ? __('ui.crowns_shop_buy') : __('ui.crowns_shop_not_enough') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <div class="box p-8 text-center text-sm text-gray-500 dark:text-white/60 md:col-span-2 xl:col-span-3">
                {{ __('ui.crowns_shop_empty') }}
            </div>
        @endforelse
    </section>
</div>
@endsection
