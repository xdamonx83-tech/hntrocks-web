@extends('themes.socialite.layouts.app')

@php
    $activeSettingsTab = 'blocks';
    $classicUrl = route('settings.privacy.blocks', ['classic_settings' => 1]);
@endphp

@section('title', __('ui.account_blocked_users') . ' · HNT.rocks')
@section('meta_description', __('ui.blocked_users_privacy_text'))

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
                        <div class="max-w-xl mx-auto">
                            <div>
                                <h4 class="text-xl font-medium text-black dark:text-white">{{ __('ui.account_blocked_users') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.blocked_users_privacy_text') }}</p>
                            </div>

                            <form class="space-y-6 mt-8" method="POST" action="{{ route('settings.privacy.blocks.store') }}">
                                @csrf

                                <div class="grid md:grid-cols-[8.5rem_1fr] gap-4 md:gap-8 items-center">
                                    <label for="username" class="font-semibold md:text-right text-black dark:text-white">{{ __('ui.username') }}</label>
                                    <div>
                                        <input id="username" type="text" name="username" value="{{ old('username') }}" placeholder="{{ __('ui.block_username_placeholder') }}" class="w-full">
                                        @error('username') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="grid md:grid-cols-[8.5rem_1fr] gap-4 md:gap-8 items-center">
                                    <label for="reason" class="font-semibold md:text-right text-black dark:text-white">{{ __('ui.block_note') }}</label>
                                    <div>
                                        <input id="reason" type="text" name="reason" value="{{ old('reason') }}" maxlength="120" placeholder="{{ __('ui.block_note_placeholder') }}" class="w-full">
                                        @error('reason') <p class="text-red-600 text-xs mt-2">{{ $message }}</p> @enderror
                                    </div>
                                </div>

                                <div class="flex items-center gap-4 mt-10 lg:pl-[10.5rem]">
                                    <a href="{{ route('settings.privacy.edit') }}" class="button lg:px-6 bg-secondery max-md:flex-1 dark:bg-white/10 dark:text-white">{{ __('ui.privacy') }}</a>
                                    <button class="button lg:px-10 bg-primary text-white max-md:flex-1" type="submit">{{ __('ui.block_user') }}</button>
                                </div>
                            </form>

                            <div class="mt-12 border-t pt-10 dark:border-slate-700">
                                <h5 class="font-semibold text-black dark:text-white">{{ __('ui.current_blocks') }}</h5>
                                <div class="space-y-3 mt-5">
                                    @forelse ($blocks as $block)
                                        @php
                                            $blockedUser = $block->blockedUser;
                                            $blockedAvatar = $blockedUser?->avatarUrl() ?? asset('assets/images/avatars/avatar-3.jpg');
                                        @endphp

                                        <div class="flex items-center justify-between gap-4 rounded-2xl border border-slate-100 bg-slate-50/80 p-3 dark:border-white/10 dark:bg-white/5">
                                            <div class="flex items-center gap-3 min-w-0">
                                                <img src="{{ $blockedAvatar }}" alt="{{ $blockedUser?->name ?? __('ui.deleted_user') }}" class="h-11 w-11 rounded-full object-cover shrink-0">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-black dark:text-white truncate">{{ $blockedUser?->name ?? __('ui.deleted_user') }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-white/60">&#64;{{ $blockedUser?->username ?? __('ui.unknown') }}</p>
                                                    @if ($block->reason)
                                                        <p class="text-xs text-gray-500 mt-1 dark:text-white/60">{{ $block->reason }}</p>
                                                    @endif
                                                </div>
                                            </div>

                                            <form method="POST" action="{{ route('settings.privacy.blocks.destroy', $block) }}" class="shrink-0">
                                                @csrf
                                                @method('DELETE')
                                                <button class="button bg-white text-black dark:bg-slate-700 dark:text-white" type="submit">{{ __('ui.unblock') }}</button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-white/70">{{ __('ui.no_blocked_users') }}</p>
                                    @endforelse
                                </div>
                            </div>

                            <div class="mt-6">
                                {{ $blocks->links() }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
@endsection
