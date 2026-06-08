@extends('layouts.app')

@section('title', __('ui.forgot_password_title'))
@section('robots', 'noindex,follow')

@section('content')
<section class="hh-auth-wrap">
    @include('auth.partials.mobile-language-switcher')
    <div class="hh-auth-card">
        <div class="hh-auth-card-head">
            <div>
                <p class="hh-kicker">{{ __('ui.account_help') }}</p>
                <h1>{{ __('ui.forgot_password_title') }}</h1>
            </div>
            @include('auth.partials.language-switcher')
        </div>
        <p class="hh-muted">{{ __('ui.forgot_password_intro') }}</p>

        @if (session('status'))
            <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="hh-form" novalidate>
            @csrf

            <label for="email">{{ __('ui.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            @error('email') <p class="hh-form-error">{{ $message }}</p> @enderror

            <button type="submit" class="hh-primary-button">{{ __('ui.forgot_password_submit') }}</button>
        </form>

        <div class="hh-auth-links">
            <a href="{{ route('login') }}">{{ __('ui.back_to_login') }}</a>
        </div>
    </div>
</section>
@endsection
