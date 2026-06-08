@extends('layouts.app')

@section('title', __('ui.app_beta_meta_title'))
@section('meta_description', __('ui.app_beta_meta_description'))

@section('content')
<section class="section-header hh-app-beta-header">
    <div class="section-header-info">
        <p class="section-pretitle">{{ __('ui.app_beta_kicker') }}</p>
        <h1 class="section-title">{{ __('ui.app_beta_title') }}</h1>
        <p class="hh-app-beta-lead">{{ __('ui.app_beta_intro') }}</p>
    </div>
</section>

<div class="grid hh-app-beta-grid">
    <article class="widget-box hh-app-beta-card hh-app-beta-main">
        <div class="widget-box-content">
            @if (session('status'))
                <div class="hh-app-beta-alert hh-app-beta-alert--success">
                    {{ session('status') }}
                </div>
            @endif

            <div class="hh-app-beta-badge-callout">
                <span aria-hidden="true">★</span>
                <div>
                    <strong>{{ __('ui.app_beta_badge_title') }}</strong>
                    <p>{{ __('ui.app_beta_badge_text') }}</p>
                </div>
            </div>

            <h2>{{ __('ui.app_beta_form_title') }}</h2>
            <p>{{ __('ui.app_beta_form_text') }}</p>

            <form method="POST" action="{{ route('app-beta.store') }}" class="hh-app-beta-form" novalidate>
                @csrf

                <div class="hh-app-beta-hp" aria-hidden="true">
                    <label for="app-beta-website">Website</label>
                    <input id="app-beta-website" type="text" name="website" tabindex="-1" autocomplete="off">
                </div>

                <label class="hh-app-beta-field" for="app-beta-name">
                    <span>{{ __('ui.app_beta_name_label') }}</span>
                    <input id="app-beta-name" type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="{{ __('ui.app_beta_name_placeholder') }}">
                    @error('name')<small class="hh-app-beta-error">{{ $message }}</small>@enderror
                </label>

                <label class="hh-app-beta-field" for="app-beta-google-email">
                    <span>{{ __('ui.app_beta_email_label') }}</span>
                    <input id="app-beta-google-email" type="email" name="google_email" value="{{ old('google_email') }}" maxlength="190" required placeholder="{{ __('ui.app_beta_email_placeholder') }}">
                    <small>{{ __('ui.app_beta_email_hint') }}</small>
                    @error('google_email')<small class="hh-app-beta-error">{{ $message }}</small>@enderror
                </label>

                <label class="hh-app-beta-field" for="app-beta-discord">
                    <span>{{ __('ui.app_beta_discord_label') }}</span>
                    <input id="app-beta-discord" type="text" name="discord" value="{{ old('discord') }}" maxlength="80" placeholder="{{ __('ui.app_beta_discord_placeholder') }}">
                    @error('discord')<small class="hh-app-beta-error">{{ $message }}</small>@enderror
                </label>

                <label class="hh-app-beta-consent" for="app-beta-consent">
                    <input id="app-beta-consent" type="checkbox" name="consent" value="1" required @checked(old('consent'))>
                    <span>{{ __('ui.app_beta_consent') }}</span>
                </label>
                @error('consent')<small class="hh-app-beta-error">{{ $message }}</small>@enderror

                <button type="submit" class="button primary hh-app-beta-submit">
                    {{ __('ui.app_beta_submit') }}
                </button>
            </form>
        </div>
    </article>

    <aside class="widget-box hh-app-beta-card hh-app-beta-steps">
        <div class="widget-box-content">
            <h2>{{ __('ui.app_beta_steps_title') }}</h2>
            <ol>
                <li>{{ __('ui.app_beta_step_1') }}</li>
                <li>{{ __('ui.app_beta_step_2') }}</li>
                <li>{{ __('ui.app_beta_step_3') }}</li>
                <li>{{ __('ui.app_beta_step_4') }}</li>
                <li>{{ __('ui.app_beta_step_5') }}</li>
            </ol>
            <p class="hh-app-beta-note">{{ __('ui.app_beta_note') }}</p>
        </div>
    </aside>
</div>
@endsection
