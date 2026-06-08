@extends('layouts.auth')

@section('title', __('ui.landing_meta_title'))
@section('meta_description', __('ui.landing_meta_description'))
@section('canonical_url', route('home'))
@section('robots', 'index,follow')
@section('og_type', 'website')

@push('head')
    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebPage',
        '@id' => route('home') . '#webpage',
        'url' => route('home'),
        'name' => __('ui.landing_meta_title'),
        'description' => __('ui.landing_meta_description'),
        'isPartOf' => [
            '@type' => 'WebSite',
            '@id' => rtrim((string) config('app.url', 'https://hnt.rocks'), '/') . '/#website',
        ],
        'about' => [
            '@type' => 'VideoGame',
            'name' => 'Hunt: Showdown',
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
@endpush

@section('content')
@php
    $hhLogo = asset('assets/vikinger/img/brand/hunthub-logo.svg');
    $hhHeroImage = asset('assets/socialite/images/post/img-3.jpg');

    $hhStats = [
        ['value' => $stats['members'] ?? 0, 'label' => __('ui.landing_stat_members')],
        ['value' => $stats['cups'] ?? 0, 'label' => __('ui.landing_stat_cups')],
        ['value' => $stats['loadouts'] ?? 0, 'label' => __('ui.landing_stat_loadouts')],
        ['value' => $stats['moments'] ?? 0, 'label' => __('ui.landing_stat_moments')],
    ];

    $hhLinks = [
        ['icon' => 'ph ph-trophy', 'title' => __('ui.landing_feature_cups_title'), 'url' => route('cups.index')],
        ['icon' => 'ph ph-crosshair', 'title' => __('ui.landing_feature_loadouts_title'), 'url' => route('loadout-challenges.index')],
        ['icon' => 'ph ph-video-camera', 'title' => __('ui.landing_feature_moments_title'), 'url' => route('moment-of-week.index')],
        ['icon' => 'ph ph-device-mobile', 'title' => __('ui.android_app'), 'url' => route('app-beta.index')],
    ];
@endphp

<div class="sm:flex">
    <div class="relative lg:w-[580px] md:w-96 w-full p-10 min-h-screen bg-white shadow-xl flex items-center pt-10 dark:bg-slate-900 z-10">
        <div class="w-full lg:max-w-sm mx-auto space-y-8" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 100 ;repeat: false">
            <div class="absolute top-10 left-10 right-10 flex items-start justify-between gap-4">
                <a href="{{ route('home') }}" class="inline-flex items-center min-w-0">
                    <img src="{{ $hhLogo }}" class="h-14 w-auto max-w-[9rem] object-contain" alt="hnt.rocks">
                </a>

                <nav class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white/90 p-1 text-xs font-bold uppercase shadow-sm dark:border-slate-800 dark:bg-slate-900/90" aria-label="{{ __('ui.auth_language_switch_label') }}">
                    <a href="{{ route('locale.switch', ['locale' => 'de']) }}" lang="de" hreflang="de" class="rounded-full px-2.5 py-1 transition {{ app()->getLocale() === 'de' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10' }}" @if (app()->getLocale() === 'de') aria-current="true" @endif>DE</a>
                    <a href="{{ route('locale.switch', ['locale' => 'en']) }}" lang="en" hreflang="en" class="rounded-full px-2.5 py-1 transition {{ app()->getLocale() === 'en' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10' }}" @if (app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
                </nav>
            </div>

            <div class="space-y-4 pt-20">
                <div class="inline-flex items-center gap-2 rounded-full border border-red-900/30 bg-red-950/20 px-3 py-1 text-xs font-bold uppercase tracking-wide text-red-200">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                    {{ __('ui.landing_kicker') }}
                </div>

                <div>
                    <h1 class="text-3xl font-semibold leading-tight mb-3">{{ __('ui.landing_title') }}</h1>
                    <p class="text-sm leading-7 text-gray-700 font-normal dark:text-gray-300">{{ __('ui.auth_landing_text') }}</p>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <a href="{{ route('login') }}" class="button bg-primary text-white w-full !m-0">{{ __('ui.login_submit') }}</a>
                <a href="{{ route('register') }}" class="button bg-white text-slate-900 border border-slate-200 hover:bg-slate-50 dark:bg-white/5 dark:text-white dark:border-slate-800 w-full !m-0">{{ __('ui.register_now') }}</a>
            </div>

            <div class="grid grid-cols-2 gap-3">
                @foreach ($hhStats as $stat)
                    <div class="rounded-xl border border-slate-200 bg-white/70 p-4 shadow-sm dark:border-slate-800 dark:bg-white/5">
                        <strong class="block text-xl text-slate-950 dark:text-white">{{ number_format((int) $stat['value'], 0, ',', '.') }}</strong>
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $stat['label'] }}</span>
                    </div>
                @endforeach
            </div>

            <div class="space-y-2">
                @foreach ($hhLinks as $link)
                    <a href="{{ $link['url'] }}" class="flex items-center justify-between rounded-xl border border-slate-200 bg-white/70 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-800 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">
                        <span class="inline-flex items-center gap-3"><i class="{{ $link['icon'] }} text-lg" aria-hidden="true"></i>{{ $link['title'] }}</span>
                        <i class="ph ph-arrow-right text-base" aria-hidden="true"></i>
                    </a>
                @endforeach
            </div>

            <div class="flex flex-wrap gap-3 text-xs font-semibold text-slate-500 dark:text-slate-400">
                <a href="{{ route('legal.impressum') }}" class="hover:text-primary">{{ __('ui.legal_impressum') }}</a>
                <a href="{{ route('legal.datenschutz') }}" class="hover:text-primary">{{ __('ui.legal_datenschutz') }}</a>
                <a href="{{ route('legal.nutzungsbedingungen') }}" class="hover:text-primary">{{ __('ui.legal_nutzungsbedingungen') }}</a>
            </div>
        </div>
    </div>

    <div class="flex-1 relative bg-primary max-md:hidden">
        <div class="relative w-full h-full min-h-screen overflow-hidden">
            <img src="{{ $hhHeroImage }}" alt="" class="w-full h-full object-cover uk-animation-kenburns uk-animation-reverse uk-transform-origin-center-left">
            <div class="absolute inset-0" style="background:linear-gradient(90deg,rgba(7,6,5,.45),rgba(7,6,5,.15)),linear-gradient(0deg,rgba(7,6,5,.92),rgba(7,6,5,.05));"></div>
            <div class="absolute bottom-0 w-full z-10">
                <div class="max-w-xl w-full mx-auto pb-32 px-5 z-30 relative" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 100 ;repeat: false">
                    <img class="w-12" src="{{ $hhLogo }}" alt="hnt.rocks">
                    <h2 class="!text-white text-2xl font-semibold mt-7">{{ __('ui.landing_features_title') }}</h2>
                    <p class="!text-white/90 text-lg mt-7 leading-8">{{ __('ui.landing_intro') }}</p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <a href="{{ route('cups.index') }}" class="button bg-primary text-white !m-0">{{ __('ui.landing_secondary_cta') }}</a>
                        <a href="{{ route('loadout-challenges.index') }}" class="button bg-white/10 text-white border border-white/20 !m-0">{{ __('ui.landing_feature_loadouts_cta') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
