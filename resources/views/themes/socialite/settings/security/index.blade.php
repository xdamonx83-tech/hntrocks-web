@extends('themes.socialite.layouts.app')

@php
    $activeSettingsTab = 'security';
    $classicUrl = route('settings.security.index', ['classic_settings' => 1]);
@endphp

@section('title', __('ui.security') . ' · HNT.rocks')
@section('meta_description', __('ui.account_security_banner_text'))

@section('content')
            <div class="max-w-3xl mx-auto">
                @if (session('status'))
                    <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
                        {{ __('ui.profile_validation_error') }}
                    </div>
                @endif

                <div class="box relative rounded-lg shadow-md overflow-hidden">
                    @include('themes.socialite.settings.partials.nav', [
                        'activeSettingsTab' => $activeSettingsTab,
                        'classicUrl' => $classicUrl,
                    ])

                    <div class="md:py-12 md:px-20 p-6 overflow-hidden text-black text-sm dark:text-white">
                        <div class="max-w-xl mx-auto space-y-12">
                            <section>
                                <h4 class="text-xl font-medium text-black dark:text-white">{{ __('ui.change_password') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.account_security_banner_text') }}</p>

                                <form class="space-y-6 mt-8" method="POST" action="{{ route('settings.security.password') }}">
                                    @csrf
                                    <div class="grid md:grid-cols-[10rem_1fr] gap-4 md:gap-8 items-center">
                                        <label for="current_password" class="font-semibold md:text-right text-black dark:text-white">{{ __('ui.current_password') }}</label>
                                        <div>
                                            <input id="current_password" type="password" name="current_password" autocomplete="current-password" class="w-full">
                                            @error('current_password') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div class="grid md:grid-cols-[10rem_1fr] gap-4 md:gap-8 items-center">
                                        <label for="password" class="font-semibold md:text-right text-black dark:text-white">{{ __('ui.new_password') }}</label>
                                        <div>
                                            <input id="password" type="password" name="password" autocomplete="new-password" class="w-full">
                                            @error('password') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                        </div>
                                    </div>
                                    <div class="grid md:grid-cols-[10rem_1fr] gap-4 md:gap-8 items-center">
                                        <label for="password_confirmation" class="font-semibold md:text-right text-black dark:text-white">{{ __('ui.confirm_new_password') }}</label>
                                        <div>
                                            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="w-full">
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-4 mt-10 lg:pl-[12rem]">
                                        <a href="{{ route('password.request') }}" class="button lg:px-6 bg-secondery max-md:flex-1 dark:bg-white/10 dark:text-white">{{ __('ui.forgot_password') }}</a>
                                        <button class="button lg:px-10 bg-primary text-white max-md:flex-1" type="submit">{{ __('ui.change_password_now') }}</button>
                                    </div>
                                </form>
                            </section>

                            <section class="border-t pt-10 dark:border-slate-700">
                                <h4 class="text-xl font-medium text-black dark:text-white">{{ __('ui.two_factor_authentication') }}</h4>
                                @if (! empty($twoFactorRecoveryCodes))
                                    <div class="mt-6 rounded-2xl border border-amber-100 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-200">
                                        <strong>{{ __('ui.two_factor_recovery_codes_title') }}</strong>
                                        <p class="mt-2">{{ __('ui.two_factor_recovery_codes_intro') }}</p>
                                        <div class="mt-3 grid gap-2">
                                            @foreach ($twoFactorRecoveryCodes as $recoveryCode)
                                                <code class="rounded bg-white px-3 py-2 dark:bg-slate-800">{{ $recoveryCode }}</code>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if ($twoFactorEnabled)
                                    <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.two_factor_enabled_intro', ['count' => $twoFactorRecoveryCount]) }}</p>
                                    <div class="grid sm:grid-cols-2 gap-4 mt-8">
                                        <form class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5" method="POST" action="{{ route('settings.security.two-factor.recovery-codes') }}">
                                            @csrf
                                            <input type="password" name="current_password" placeholder="{{ __('ui.current_password') }}" class="w-full mb-3">
                                            <input type="text" name="code" placeholder="{{ __('ui.two_factor_code_or_recovery') }}" class="w-full mb-3">
                                            <button class="button bg-white text-black w-full dark:bg-slate-700 dark:text-white" type="submit">{{ __('ui.two_factor_regenerate_recovery_codes') }}</button>
                                        </form>
                                        <form class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5" method="POST" action="{{ route('settings.security.two-factor.disable') }}">
                                            @csrf
                                            <input type="password" name="current_password" placeholder="{{ __('ui.current_password') }}" class="w-full mb-3">
                                            <input type="text" name="code" placeholder="{{ __('ui.two_factor_code_or_recovery') }}" class="w-full mb-3">
                                            <button class="button bg-white text-black w-full dark:bg-slate-700 dark:text-white" type="submit">{{ __('ui.two_factor_disable') }}</button>
                                        </form>
                                    </div>
                                @elseif ($twoFactorSetupSecret)
                                    <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.two_factor_setup_manual_intro') }}</p>
                                    <div class="mt-6 rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5">
                                        <p class="font-semibold">{{ __('ui.two_factor_manual_key') }}</p>
                                        <p class="mt-2 break-all font-mono">{{ $twoFactorSetupSecret }}</p>
                                        <p class="mt-3 text-xs text-gray-500 break-all dark:text-white/60">{{ $twoFactorSetupUri }}</p>
                                    </div>
                                    <form class="space-y-4 mt-6" method="POST" action="{{ route('settings.security.two-factor.confirm') }}">
                                        @csrf
                                        <input type="text" name="code" placeholder="{{ __('ui.two_factor_code') }}" inputmode="numeric" autocomplete="one-time-code" class="w-full">
                                        @error('code') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                        <button class="button bg-primary text-white" type="submit">{{ __('ui.two_factor_confirm_enable') }}</button>
                                    </form>
                                @else
                                    <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.two_factor_disabled_intro') }}</p>
                                    <form class="space-y-4 mt-6" method="POST" action="{{ route('settings.security.two-factor.setup') }}">
                                        @csrf
                                        <input type="password" name="current_password" placeholder="{{ __('ui.current_password') }}" autocomplete="current-password" class="w-full">
                                        @error('current_password') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                        <button class="button bg-primary text-white" type="submit">{{ __('ui.two_factor_start_setup') }}</button>
                                    </form>
                                @endif
                            </section>

                            <section class="border-t pt-10 dark:border-slate-700">
                                <div class="grid sm:grid-cols-2 gap-4">
                                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5">
                                        <h5 class="font-semibold text-black dark:text-white">{{ __('ui.data_export') }}</h5>
                                        <p class="mt-2 text-sm text-gray-600 dark:text-white/70">{{ __('ui.data_export_intro') }}</p>
                                        <a class="button bg-white text-black w-full mt-4 dark:bg-slate-700 dark:text-white" href="{{ route('settings.security.export') }}">{{ __('ui.download_data_export') }}</a>
                                    </div>
                                    <div class="rounded-2xl border border-red-100 bg-red-50 p-4 dark:border-red-400/20 dark:bg-red-500/10">
                                        <h5 class="font-semibold text-red-700 dark:text-red-200">{{ __('ui.account_deletion') }}</h5>
                                        @if ($deletionRequest && $deletionRequest->isPending())
                                            <p class="mt-2 text-sm text-red-700 dark:text-red-200">{{ __('ui.account_deletion_pending', ['date' => $deletionRequest->scheduled_for?->format('d.m.Y H:i')]) }}</p>
                                            <form method="POST" action="{{ route('settings.security.deletion.cancel') }}" class="mt-4">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button bg-white text-black w-full dark:bg-slate-700 dark:text-white" type="submit">{{ __('ui.cancel_account_deletion') }}</button>
                                            </form>
                                        @else
                                            <p class="mt-2 text-sm text-red-700 dark:text-red-200">{{ __('ui.account_deletion_intro') }}</p>
                                            <form method="POST" action="{{ route('settings.security.deletion.request') }}" class="mt-4 space-y-3">
                                                @csrf
                                                <input type="text" name="delete_confirmation" value="{{ old('delete_confirmation') }}" placeholder="{{ __('ui.account_deletion_confirm_username', ['username' => $user->username]) }}" class="w-full">
                                                <input type="password" name="password" placeholder="{{ __('ui.account_deletion_password_optional') }}" class="w-full">
                                                <textarea name="reason" rows="3" placeholder="{{ __('ui.reason_optional') }}" class="w-full">{{ old('reason') }}</textarea>
                                                <button class="button bg-white text-black w-full dark:bg-slate-700 dark:text-white" type="submit">{{ __('ui.request_account_deletion') }}</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </section>

                            <section class="border-t pt-10 dark:border-slate-700">
                                <h4 class="text-xl font-medium text-black dark:text-white">{{ __('ui.security_log') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.security_log_intro') }}</p>
                                <div class="space-y-3 mt-6">
                                    @forelse ($events as $event)
                                        <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 dark:border-white/10 dark:bg-white/5">
                                            <p class="font-semibold text-black dark:text-white">{{ \Illuminate\Support\Str::headline($event->event) }}</p>
                                            <p class="text-xs text-gray-500 mt-1 dark:text-white/60">{{ $event->created_at->format('d.m.Y H:i') }} · IP: {{ $event->ip_address ?: __('ui.unknown') }}</p>
                                            <p class="text-xs text-gray-500 mt-1 break-all dark:text-white/60">{{ $event->user_agent ?: __('ui.no_user_agent_saved') }}</p>
                                        </div>
                                    @empty
                                        <p class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-white/70">{{ __('ui.no_security_events') }}</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
@endsection
