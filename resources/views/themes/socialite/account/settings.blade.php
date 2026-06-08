@extends('themes.socialite.layouts.app')

@php
    $activeSettingsTab = 'notifications';
    $classicUrl = route('account.settings.edit', ['classic_settings' => 1]);
@endphp

@section('title', __('ui.account_settings') . ' · HNT.rocks')
@section('meta_description', __('ui.notification_settings_intro'))

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

                    <form id="socialite-notification-settings-form" method="POST" action="{{ route('account.settings.update') }}" class="md:py-12 md:px-20 p-6 overflow-hidden text-black text-sm dark:text-white">
                        @csrf
                        @method('PUT')

                        <div class="max-w-xl mx-auto">
                            <div>
                                <h4 class="text-xl font-medium text-black dark:text-white">{{ __('ui.notification_settings') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.notification_settings_intro') }}</p>
                            </div>

                            <div class="space-y-6 mt-8">
                                @foreach ($notificationGroups as $field => $meta)
                                    @php
                                        $checked = (bool) old($field, $settings->{$field});
                                    @endphp
                                    <div class="grid md:grid-cols-[11rem_1fr] gap-4 md:gap-8 items-center">
                                        <label for="notification-{{ $field }}" class="font-semibold md:text-right text-black dark:text-white">
                                            {{ $meta['title'] }}
                                        </label>

                                        <div class="flex items-center justify-between gap-5 rounded-2xl border border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-white/10 dark:bg-white/5">
                                            <p class="min-w-0 text-sm leading-6 text-gray-600 dark:text-white/70">
                                                {{ $meta['text'] }}
                                            </p>

                                            <input type="hidden" name="{{ $field }}" value="0">
                                            <label for="notification-{{ $field }}" class="hnt-settings-toggle" aria-label="{{ $meta['title'] }}">
                                                <input id="notification-{{ $field }}" type="checkbox" name="{{ $field }}" value="1" @checked($checked)>
                                                <span class="hnt-settings-toggle-track">
                                                    <span class="hnt-settings-toggle-dot"></span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="flex items-center gap-4 mt-16 lg:pl-[12.5rem]">
                                <a href="{{ route('account.index') }}" class="button lg:px-6 bg-secondery max-md:flex-1 dark:bg-white/10 dark:text-white">{{ __('ui.discard_all') }}</a>
                                <button type="submit" class="button lg:px-10 bg-primary text-white max-md:flex-1">{{ __('ui.save_changes') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
@endsection
