@php
    $displayName = $user->username ?: ($user->name ?: 'Hunter');
@endphp

@component('mail::message')
# {{ __('ui.password_reset_mail_heading') }}

{{ __('ui.password_reset_mail_greeting', ['name' => $displayName]) }}

{{ __('ui.password_reset_mail_intro') }}

@component('mail::button', ['url' => $resetUrl])
{{ __('ui.password_reset_mail_button') }}
@endcomponent

{{ __('ui.password_reset_mail_expiry', ['minutes' => $expireMinutes]) }}

{{ __('ui.password_reset_mail_ignore') }}

{{ __('ui.password_reset_mail_fallback') }}

{{ $resetUrl }}

{{ __('ui.password_reset_mail_regards') }}  
{{ config('app.name', 'hnt.rocks') }}
@endcomponent
