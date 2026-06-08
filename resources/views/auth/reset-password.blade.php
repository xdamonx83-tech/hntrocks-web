@extends('layouts.app')

@section('title', __('ui.reset_password_title'))
@section('robots', 'noindex,follow')

@section('content')
<section class="hh-auth-wrap">
    @include('auth.partials.mobile-language-switcher')
    <div class="hh-auth-card">
        <div class="hh-auth-card-head">
            <div>
                <p class="hh-kicker">{{ __('ui.account_help') }}</p>
                <h1>{{ __('ui.reset_password_title') }}</h1>
            </div>
            @include('auth.partials.language-switcher')
        </div>

        <form method="POST" action="{{ route('password.store') }}" class="hh-form" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <label for="email">{{ __('ui.email') }}</label>
            <input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" autocomplete="email" required autofocus>
            @error('email') <p class="hh-form-error">{{ $message }}</p> @enderror

            <label for="password">{{ __('ui.new_password') }}</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required>
            @error('password') <p class="hh-form-error">{{ $message }}</p> @enderror

            <label for="password_confirmation">{{ __('ui.confirm_new_password') }}</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required>

            <button type="submit" class="hh-primary-button">{{ __('ui.reset_password_submit') }}</button>
        </form>
    </div>
</section>
@endsection
