@extends('themes.socialite.layouts.app')

@php
    $activeSettingsTab = 'privacy';
    $classicUrl = route('settings.privacy.edit', ['classic_settings' => 1]);
    $privacyToggles = [
        'allow_team_invites' => ['title' => __('ui.allow_team_invites'), 'text' => __('ui.allow_team_invites_text')],
        'allow_lfg_invites' => ['title' => __('ui.allow_lfg_invites'), 'text' => __('ui.allow_lfg_invites_text')],
        'show_online_status' => ['title' => __('ui.show_online_status'), 'text' => __('ui.show_online_status_text')],
        'show_activity_feed' => ['title' => __('ui.show_activity_feed'), 'text' => __('ui.show_activity_feed_text')],
        'show_gamification' => ['title' => __('ui.show_gamification'), 'text' => __('ui.show_gamification_text')],
        'data_usage_consent' => ['title' => __('ui.data_usage_consent'), 'text' => __('ui.data_usage_consent_text')],
    ];
@endphp

@section('title', __('ui.privacy') . ' · HNT.rocks')
@section('meta_description', __('ui.privacy_settings_intro'))

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

                    <form id="privacy-settings-form" method="POST" action="{{ route('settings.privacy.update') }}" class="md:py-12 md:px-20 p-6 overflow-hidden text-black text-sm dark:text-white">
                        @csrf
                        @method('PUT')

                        <div class="max-w-xl mx-auto">
                            <div>
                                <h4 class="text-xl font-medium text-black dark:text-white">{{ __('ui.privacy_settings') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.privacy_settings_intro') }}</p>
                            </div>

                            <div class="space-y-6 mt-8">
                                <div class="md:flex items-center gap-16 justify-between">
                                    <label for="profile_visibility" class="md:w-40 text-right font-semibold">{{ __('ui.profile_visibility') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <select id="profile_visibility" name="profile_visibility" class="w-full !border-0 !rounded-md">
                                            <option value="public" @selected(old('profile_visibility', $settings->profile_visibility) === 'public')>{{ __('ui.visibility_public') }}</option>
                                            <option value="registered" @selected(old('profile_visibility', $settings->profile_visibility) === 'registered')>{{ __('ui.visibility_registered') }}</option>
                                            <option value="private" @selected(old('profile_visibility', $settings->profile_visibility) === 'private')>{{ __('ui.visibility_private') }}</option>
                                        </select>
                                        @error('profile_visibility') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-16 justify-between">
                                    <label for="allow_messages_from" class="md:w-40 text-right font-semibold">{{ __('ui.allow_messages_from') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <select id="allow_messages_from" name="allow_messages_from" class="w-full !border-0 !rounded-md">
                                            <option value="everyone" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'everyone')>{{ __('ui.allow_messages_everyone') }}</option>
                                            <option value="registered" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'registered')>{{ __('ui.allow_messages_registered') }}</option>
                                            <option value="following" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'following')>{{ __('ui.allow_messages_following') }}</option>
                                            <option value="nobody" @selected(old('allow_messages_from', $settings->allow_messages_from) === 'nobody')>{{ __('ui.allow_messages_nobody') }}</option>
                                        </select>
                                        @error('allow_messages_from') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                @foreach ($privacyToggles as $field => $meta)
                                    @php
                                        $checked = (bool) old($field, $settings->{$field});
                                    @endphp
                                    <div class="grid md:grid-cols-[11rem_1fr] gap-4 md:gap-8 items-center">
                                        <label for="privacy-{{ $field }}" class="font-semibold md:text-right text-black dark:text-white">
                                            {{ $meta['title'] }}
                                        </label>

                                        <div class="flex items-center justify-between gap-5 rounded-2xl border border-slate-100 bg-slate-50/80 px-5 py-4 dark:border-white/10 dark:bg-white/5">
                                            <p class="min-w-0 text-sm leading-6 text-gray-600 dark:text-white/70">
                                                {{ $meta['text'] }}
                                            </p>

                                            <input type="hidden" name="{{ $field }}" value="0">
                                            <label for="privacy-{{ $field }}" class="hnt-settings-toggle" aria-label="{{ $meta['title'] }}">
                                                <input id="privacy-{{ $field }}" type="checkbox" name="{{ $field }}" value="1" @checked($checked)>
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
