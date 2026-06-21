@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.app_beta_meta_title'))
@section('meta_description', __('ui.app_beta_meta_description'))
@section('robots', 'index,follow')
@section('main_class', 'app-beta-main')

@php
    $campaignCards = [
        [
            'icon' => 'android',
            'title' => __('ui.app_beta_socialite_card_closed_title'),
            'text' => __('ui.app_beta_socialite_card_closed_text'),
            'label' => __('ui.app_beta_socialite_card_closed_label'),
        ],
        [
            'icon' => 'play',
            'title' => __('ui.app_beta_socialite_card_play_title'),
            'text' => __('ui.app_beta_socialite_card_play_text'),
            'label' => __('ui.app_beta_socialite_card_play_label'),
        ],
        [
            'icon' => 'badge',
            'title' => __('ui.app_beta_socialite_card_badge_title'),
            'text' => __('ui.app_beta_socialite_card_badge_text'),
            'label' => __('ui.app_beta_socialite_card_badge_label'),
        ],
        [
            'icon' => 'feedback',
            'title' => __('ui.app_beta_socialite_card_feedback_title'),
            'text' => __('ui.app_beta_socialite_card_feedback_text'),
            'label' => __('ui.app_beta_socialite_card_feedback_label'),
        ],
    ];

    $testAreas = [
        ['icon' => 'feed', 'title' => __('ui.app_beta_area_feed_title'), 'text' => __('ui.app_beta_area_feed_text')],
        ['icon' => 'social', 'title' => __('ui.app_beta_area_social_title'), 'text' => __('ui.app_beta_area_social_text')],
        ['icon' => 'cups', 'title' => __('ui.app_beta_area_cups_title'), 'text' => __('ui.app_beta_area_cups_text')],
    ];

    $icon = static function (string $name): string {
        return match ($name) {
            'android' => '<i class="ph ph-android-logo" aria-hidden="true"></i>',
            'play' => '<i class="ph ph-play-circle" aria-hidden="true"></i>',
            'badge' => '<i class="ph ph-medal" aria-hidden="true"></i>',
            'feedback' => '<i class="ph ph-chat-circle-text" aria-hidden="true"></i>',
            'feed' => '<i class="ph ph-list" aria-hidden="true"></i>',
            'social' => '<i class="ph ph-users-three" aria-hidden="true"></i>',
            'cups' => '<i class="ph ph-trophy" aria-hidden="true"></i>',
            default => '<i class="ph ph-star" aria-hidden="true"></i>',
        };
    };
@endphp

@section('content')
<div class="app-beta-shell" id="app-beta-signup">
    @if (session('status'))
        <div class="hnt-crowns-alert success app-beta-alert">{{ session('status') }}</div>
    @endif

    <section class="app-beta-hero">
        <div class="app-beta-hero-glow" aria-hidden="true"></div>
        <div class="app-beta-hero-copy">
            <span class="app-beta-kicker">{{ __('ui.app_beta_kicker') }}</span>
            <h1>{{ __('ui.app_beta_socialite_hero_title') }}</h1>
            <p>{{ __('ui.app_beta_intro') }}</p>
            <div class="app-beta-hero-actions">
                <a class="btn-create" href="#app-beta-form">{{ __('ui.app_beta_submit') }}</a>
                <span>{{ __('ui.app_beta_socialite_hero_note') }}</span>
            </div>
        </div>
        <div class="app-beta-device-card" aria-hidden="true">
            <div class="app-beta-device-notch"></div>
            <div class="app-beta-device-screen">
                <span class="app-beta-device-logo">HNT</span>
                <span class="app-beta-device-line wide"></span>
                <span class="app-beta-device-line"></span>
                <span class="app-beta-device-pill">Beta</span>
            </div>
        </div>
    </section>

    <section class="app-beta-card-grid" aria-label="{{ __('ui.app_beta_socialite_tab_testareas') }}">
        @foreach($campaignCards as $card)
            <article class="app-beta-info-card">
                <div class="app-beta-info-icon">{!! $icon($card['icon']) !!}</div>
                <div>
                    <span>{{ $card['label'] }}</span>
                    <h2>{{ $card['title'] }}</h2>
                    <p>{{ $card['text'] }}</p>
                </div>
            </article>
        @endforeach
    </section>

    <section class="app-beta-section" id="app-beta-testareas">
        <div class="app-beta-section-head">
            <div>
                <span>{{ __('ui.app_beta_socialite_tab_testareas') }}</span>
                <h2>{{ __('ui.app_beta_socialite_testareas_title') }}</h2>
            </div>
            <a href="#app-beta-form">{{ __('ui.app_beta_socialite_testareas_cta') }}</a>
        </div>

        <div class="app-beta-test-grid">
            @foreach($testAreas as $area)
                <article class="app-beta-test-card">
                    <div class="app-beta-test-icon">{!! $icon($area['icon']) !!}</div>
                    <h3>{{ $area['title'] }}</h3>
                    <p>{{ $area['text'] }}</p>
                </article>
            @endforeach
        </div>
    </section>

    <section class="app-beta-form-panel" id="app-beta-form">
        <div class="app-beta-form-copy">
            <span>{{ __('ui.app_beta_socialite_tab_signup') }}</span>
            <h2>{{ __('ui.app_beta_form_title') }}</h2>
            <p>{{ __('ui.app_beta_form_text') }}</p>
        </div>

        <form method="POST" action="{{ route('app-beta.store') }}" class="app-beta-form" novalidate>
            @csrf

            <div class="app-beta-hp" aria-hidden="true">
                <label for="app-beta-website">{{ __('ui.website') }}</label>
                <input id="app-beta-website" type="text" name="website" tabindex="-1" autocomplete="off">
            </div>

            <label class="app-beta-field" for="app-beta-name">
                <span>{{ __('ui.app_beta_name_label') }}</span>
                <input id="app-beta-name" type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="{{ __('ui.app_beta_name_placeholder') }}">
                @error('name')<small>{{ $message }}</small>@enderror
            </label>

            <label class="app-beta-field" for="app-beta-google-email">
                <span>{{ __('ui.app_beta_email_label') }}</span>
                <input id="app-beta-google-email" type="email" name="google_email" value="{{ old('google_email') }}" maxlength="190" required placeholder="{{ __('ui.app_beta_email_placeholder') }}">
                <em>{{ __('ui.app_beta_email_hint') }}</em>
                @error('google_email')<small>{{ $message }}</small>@enderror
            </label>

            <label class="app-beta-field" for="app-beta-discord">
                <span>{{ __('ui.app_beta_discord_label') }}</span>
                <input id="app-beta-discord" type="text" name="discord" value="{{ old('discord') }}" maxlength="80" placeholder="{{ __('ui.app_beta_discord_placeholder') }}">
                @error('discord')<small>{{ $message }}</small>@enderror
            </label>

            <label class="app-beta-consent" for="app-beta-consent">
                <input id="app-beta-consent" type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                <span>{{ __('ui.app_beta_consent') }}</span>
            </label>
            @error('consent')<small class="app-beta-error-block">{{ $message }}</small>@enderror

            <button type="submit" class="btn-create app-beta-submit">{{ __('ui.app_beta_submit') }}</button>
        </form>
    </section>
</div>
@endsection

@section('right_sidebar')
<aside class="sidebar-right app-beta-sidebar" aria-label="{{ __('ui.app_beta_steps_title') }}">
    <section class="widget app-beta-side-widget">
        <div class="widget-header">
            <h2>{{ __('ui.app_beta_steps_title') }}</h2>
        </div>
        <ol class="app-beta-steps">
            @foreach([1, 2, 3, 4, 5] as $step)
                <li>
                    <span>{{ $step }}</span>
                    <div>
                        <strong>{{ __('ui.app_beta_socialite_step_title_' . $step) }}</strong>
                        <p>{{ __('ui.app_beta_step_' . $step) }}</p>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="widget app-beta-side-widget app-beta-badge-widget">
        <div class="app-beta-badge-icon">{!! $icon('badge') !!}</div>
        <h2>{{ __('ui.app_beta_badge_title') }}</h2>
        <p>{{ __('ui.app_beta_badge_text') }}</p>
        <div class="app-beta-badge-row">
            <strong>{{ __('ui.app_beta_socialite_badge_name') }}</strong>
            <span>{{ __('ui.app_beta_socialite_badge_text') }}</span>
        </div>
    </section>

    <section class="widget app-beta-side-widget">
        <h2>{{ __('ui.app_beta_note') }}</h2>
    </section>
</aside>
@endsection
