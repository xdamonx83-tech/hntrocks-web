@extends('layouts.app')

@section('title', __('ui.two_factor_challenge_title') . ' · hnt.rocks')
@section('robots', 'noindex,follow')

@section('content')
<section class="hh-auth-wrap">
    @include('auth.partials.mobile-language-switcher')
    <div class="hh-auth-card">
        <div class="hh-auth-card-head">
            <div>
                <p class="hh-kicker">hnt.rocks</p>
                <h1>{{ __('ui.two_factor_challenge_title') }}</h1>
            </div>
            @include('auth.partials.language-switcher')
        </div>
        <p class="hh-muted">{{ __('ui.two_factor_challenge_intro') }}</p>

        @if ($errors->any())
            <div class="hh-alert hh-alert-danger">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.two-factor.confirm') }}" class="hh-form" novalidate>
            @csrf

            <label for="code">{{ __('ui.two_factor_code_or_recovery') }}</label>
            <input id="code" type="text" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            @error('code') <p class="hh-form-error">{{ $message }}</p> @enderror

            <button type="submit" class="hh-primary-button">{{ __('ui.two_factor_verify_login') }}</button>
        </form>

        <div class="hh-auth-links">
            <a href="{{ route('login') }}">{{ __('ui.back_to_login') }}</a>
        </div>
    </div>
</section>
@endsection
