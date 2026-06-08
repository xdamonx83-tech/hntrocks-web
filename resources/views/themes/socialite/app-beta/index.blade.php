@extends('themes.socialite.layouts.app')

@section('title', __('ui.app_beta_meta_title'))
@section('meta_description', __('ui.app_beta_meta_description'))

@section('content')
@php
    $campaignCards = [
        [
            'icon' => 'logo-android',
            'title' => __('ui.app_beta_socialite_card_closed_title'),
            'text' => __('ui.app_beta_socialite_card_closed_text'),
            'label' => __('ui.app_beta_socialite_card_closed_label'),
            'progress' => 74,
        ],
        [
            'icon' => 'storefront-outline',
            'title' => __('ui.app_beta_socialite_card_play_title'),
            'text' => __('ui.app_beta_socialite_card_play_text'),
            'label' => __('ui.app_beta_socialite_card_play_label'),
            'progress' => 58,
        ],
        [
            'icon' => 'ribbon-outline',
            'title' => __('ui.app_beta_socialite_card_badge_title'),
            'text' => __('ui.app_beta_socialite_card_badge_text'),
            'label' => __('ui.app_beta_socialite_card_badge_label'),
            'progress' => 42,
        ],
        [
            'icon' => 'chatbubbles-outline',
            'title' => __('ui.app_beta_socialite_card_feedback_title'),
            'text' => __('ui.app_beta_socialite_card_feedback_text'),
            'label' => __('ui.app_beta_socialite_card_feedback_label'),
            'progress' => 66,
        ],
    ];

    $testAreas = [
        ['icon' => 'newspaper-outline', 'title' => __('ui.app_beta_area_feed_title'), 'text' => __('ui.app_beta_area_feed_text')],
        ['icon' => 'people-outline', 'title' => __('ui.app_beta_area_social_title'), 'text' => __('ui.app_beta_area_social_text')],
        ['icon' => 'trophy-outline', 'title' => __('ui.app_beta_area_cups_title'), 'text' => __('ui.app_beta_area_cups_text')],
    ];
@endphp

<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-app-beta-page">
    <div class="flex-1">
        <div class="page-heading">
            <h1 class="page-title">{{ __('ui.app_beta_title') }}</h1>

            <nav class="nav__underline">
                <ul class="group">
                    <li class="uk-active"><a href="#app-beta-signup">{{ __('ui.app_beta_socialite_tab_signup') }}</a></li>
                    <li><a href="#app-beta-testareas">{{ __('ui.app_beta_socialite_tab_testareas') }}</a></li>
                </ul>
            </nav>
        </div>

        @if (session('status'))
            <div class="box p-4 mb-6 border border-green-100 bg-green-50 text-green-700 dark:bg-green-500/10 dark:border-green-500/20 dark:text-green-200">
                <div class="flex items-center gap-3">
                    <ion-icon name="checkmark-circle" class="text-2xl"></ion-icon>
                    <p class="text-sm font-semibold">{{ session('status') }}</p>
                </div>
            </div>
        @endif

        <div class="box overflow-hidden mb-6">
            <div class="relative bg-secondery dark:bg-white/5" style="min-height: 260px;">
                <img src="{{ asset('assets/socialite/images/ad_pattern.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30">
                <div class="absolute inset-0 bg-gradient-to-br from-slate-950/85 via-slate-900/70 to-blue-900/70"></div>
                <div class="relative p-6 sm:p-8 md:p-10 text-white max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide">
                        <ion-icon name="logo-google-playstore" class="text-lg"></ion-icon>
                        {{ __('ui.app_beta_kicker') }}
                    </div>
                    <h2 class="mt-5 text-3xl md:text-4xl font-bold leading-tight">{{ __('ui.app_beta_socialite_hero_title') }}</h2>
                    <p class="mt-4 text-sm md:text-base text-white/80 leading-7">{{ __('ui.app_beta_intro') }}</p>
                    <div class="flex flex-wrap items-center gap-3 mt-6">
                        <a href="#app-beta-signup" class="button bg-primary text-white !w-auto px-5">{{ __('ui.app_beta_submit') }}</a>
                        <span class="text-xs text-white/70">{{ __('ui.app_beta_socialite_hero_note') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div tabindex="-1" uk-slider="finite:true">
            <div class="uk-slider-container pb-1">
                <ul class="uk-slider-items grid-small">
                    @foreach ($campaignCards as $card)
                        <li class="sm:w-1/3 w-1/2">
                            <div class="card">
                                <div class="card-media h-32 bg-secondery flex items-center justify-center dark:bg-white/5">
                                    <ion-icon name="{{ $card['icon'] }}" class="text-5xl text-blue-600"></ion-icon>
                                </div>
                                <div class="card-body">
                                    <h4 class="card-title text-sm line-clamp-2">{{ $card['title'] }}</h4>
                                    <p class="mt-2 text-xs text-gray-500 line-clamp-2 dark:text-white/70">{{ $card['text'] }}</p>
                                    <div class="text-blue-500 font-medium text-xs mt-3">{{ $card['label'] }}</div>
                                </div>
                                <div class="bg-secondery rounded-2xl h-1 w-full relative overflow-hidden">
                                    <div class="bg-blue-600 h-full" style="width: {{ $card['progress'] }}%"></div>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <a class="nav-prev !top-24" href="#" uk-slider-item="previous"><ion-icon name="chevron-back" class="text-2xl"></ion-icon></a>
            <a class="nav-next !top-24" href="#" uk-slider-item="next"><ion-icon name="chevron-forward" class="text-2xl"></ion-icon></a>
        </div>

        <div id="app-beta-testareas" class="flex items-center justify-between text-black dark:text-white py-3 mt-10">
            <h3 class="text-xl font-semibold">{{ __('ui.app_beta_socialite_testareas_title') }}</h3>
            <a href="#app-beta-signup" class="text-sm text-blue-500">{{ __('ui.app_beta_socialite_testareas_cta') }}</a>
        </div>

        <div class="box p-5 mt-3">
            @foreach ($testAreas as $area)
                <div class="card-list">
                    <div class="card-list-media bg-secondery flex items-center justify-center dark:bg-white/5" style="width: 92px; min-width: 92px; height: 86px;">
                        <ion-icon name="{{ $area['icon'] }}" class="text-3xl text-blue-600"></ion-icon>
                    </div>
                    <div class="card-list-body">
                        <h3 class="card-list-title">{{ $area['title'] }}</h3>
                        <div class="card-list-text">
                            <p>{{ $area['text'] }}</p>
                        </div>
                    </div>
                </div>

                @if (! $loop->last)
                    <hr class="card-list-divider">
                @endif
            @endforeach
        </div>
    </div>

    <aside class="2xl:w-[380px] lg:w-[330px] w-full">
        <div class="lg:space-y-6 space-y-4 lg:pb-8 max-lg:grid sm:grid-cols-2 max-lg:gap-6" uk-sticky="media: 1024; end: #js-app-beta-page; offset: 80; bottom:true">
            <div id="app-beta-signup" class="box p-5">
                <div class="flex items-center gap-3 mb-4">
                    <ion-icon name="logo-android" class="flex shrink-0 p-2 text-2xl rounded-full bg-green-100 text-green-600 dark:bg-green-500/20"></ion-icon>
                    <div>
                        <h2 class="font-bold text-base text-black dark:text-white">{{ __('ui.app_beta_form_title') }}</h2>
                        <p class="text-xs text-gray-500 mt-0.5 dark:text-white/70">{{ __('ui.app_beta_form_text') }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('app-beta.store') }}" class="space-y-3" novalidate>
                    @csrf

                    <div style="position:absolute; left:-10000px; top:auto; width:1px; height:1px; overflow:hidden;" aria-hidden="true">
                        <label for="app-beta-website">{{ __('ui.website') }}</label>
                        <input id="app-beta-website" type="text" name="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div>
                        <label for="app-beta-name" class="text-xs font-semibold text-gray-500 dark:text-white/70">{{ __('ui.app_beta_name_label') }}</label>
                        <input id="app-beta-name" type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="{{ __('ui.app_beta_name_placeholder') }}" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm dark:bg-white/5">
                        @error('name')<small class="block mt-1 text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>

                    <div>
                        <label for="app-beta-google-email" class="text-xs font-semibold text-gray-500 dark:text-white/70">{{ __('ui.app_beta_email_label') }}</label>
                        <input id="app-beta-google-email" type="email" name="google_email" value="{{ old('google_email') }}" maxlength="190" required placeholder="{{ __('ui.app_beta_email_placeholder') }}" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm dark:bg-white/5">
                        <small class="block mt-1 text-xs text-gray-500 dark:text-white/60">{{ __('ui.app_beta_email_hint') }}</small>
                        @error('google_email')<small class="block mt-1 text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>

                    <div>
                        <label for="app-beta-discord" class="text-xs font-semibold text-gray-500 dark:text-white/70">{{ __('ui.app_beta_discord_label') }}</label>
                        <input id="app-beta-discord" type="text" name="discord" value="{{ old('discord') }}" maxlength="80" placeholder="{{ __('ui.app_beta_discord_placeholder') }}" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm dark:bg-white/5">
                        @error('discord')<small class="block mt-1 text-xs text-red-500">{{ $message }}</small>@enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-xl bg-secondery p-3 text-xs text-gray-600 dark:bg-white/5 dark:text-white/70" for="app-beta-consent">
                        <input id="app-beta-consent" type="checkbox" name="consent" value="1" required @checked(old('consent')) class="mt-1">
                        <span>{{ __('ui.app_beta_consent') }}</span>
                    </label>
                    @error('consent')<small class="block text-xs text-red-500">{{ $message }}</small>@enderror

                    <button type="submit" class="button bg-primary text-white w-full">{{ __('ui.app_beta_submit') }}</button>
                </form>
            </div>

            <div class="box p-5">
                <h3 class="font-semibold text-base text-black dark:text-white">{{ __('ui.app_beta_steps_title') }}</h3>
                <p class="text-sm mt-1 text-gray-500 dark:text-white/70">{{ __('ui.app_beta_note') }}</p>

                <ul class="relative space-y-2.5 text-sm mt-4" uk-accordion="active: 0">
                    @foreach ([1, 2, 3, 4, 5] as $step)
                        <li class="rounded-md bg-secondery dark:bg-white/5">
                            <a class="flex items-center justify-between p-3 py-2 font-semibold group text-black dark:text-white uk-accordion-title" href="#">
                                {{ __('ui.app_beta_socialite_step_title_' . $step) }}
                                <ion-icon name="chevron-down-outline" class="duration-200 -mr-0.5 text-base group-aria-expanded:rotate-180"></ion-icon>
                            </a>
                            <div class="p-3 pt-0 dark:text-white/80 uk-accordion-content">
                                <p>{{ __('ui.app_beta_step_' . $step) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="box p-5">
                <h3 class="font-semibold text-base text-black dark:text-white">{{ __('ui.app_beta_badge_title') }}</h3>
                <p class="text-sm mt-2 text-gray-500 dark:text-white/70">{{ __('ui.app_beta_badge_text') }}</p>
                <div class="flex items-center gap-3 mt-4 rounded-xl bg-secondery p-3 dark:bg-white/5">
                    <ion-icon name="ribbon-outline" class="text-3xl text-amber-500"></ion-icon>
                    <div>
                        <p class="font-semibold text-black dark:text-white">{{ __('ui.app_beta_socialite_badge_name') }}</p>
                        <p class="text-xs text-gray-500 dark:text-white/70">{{ __('ui.app_beta_socialite_badge_text') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </aside>
</div>
@endsection
