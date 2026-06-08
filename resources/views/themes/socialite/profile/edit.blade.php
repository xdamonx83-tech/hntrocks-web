@extends('themes.socialite.layouts.app')

@php
    $profile = $user->profile;
    $avatarUrl = $user->avatarUrl();
    $coverUrl = $user->coverUrl();
    $completion = $user->profileCompletionScore();
    $visibility = old('profile_visibility', $profile?->profile_visibility ?? 'public');
    $platform = old('platform', $profile?->platform);
    $playstyle = old('playstyle', $profile?->playstyle);
    $isLfgAvailable = (bool) old('is_lfg_available', $profile?->is_lfg_available);
    $level = max(1, (int) ($user->level ?: 1));
    $mediaUpdateUrl = route('profile.media.update');
@endphp

@section('title', __('ui.edit_profile') . ' · HNT.rocks')
@section('meta_description', __('ui.profile_edit_meta_description'))

@section('content')
            <div class="max-w-3xl mx-auto">
                <form id="socialite-profile-edit-form" method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="box relative rounded-lg shadow-md overflow-hidden" novalidate>
                    @csrf
                    @method('PUT')

                    <div class="flex md:gap-8 gap-4 items-center md:p-8 p-6 md:pb-4">
                        <div class="relative md:w-20 md:h-20 w-12 h-12 shrink-0">
                            <button type="button" class="cursor-pointer block h-full w-full" data-profile-media-edit-trigger="avatar" aria-label="{{ __('ui.change_avatar') }}">
                                <img src="{{ $avatarUrl }}" class="object-cover w-full h-full rounded-full" alt="{{ $user->name }}" data-socialite-edit-avatar-preview>
                            </button>

                            <button type="button" class="md:p-1 p-0.5 rounded-full bg-slate-600 md:border-4 border-white absolute -bottom-2 -right-2 cursor-pointer dark:border-slate-700" data-profile-media-edit-trigger="avatar" aria-label="{{ __('ui.change_avatar') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="md:w-4 md:h-4 w-3 h-3 fill-white">
                                    <path d="M12 9a3.75 3.75 0 100 7.5A3.75 3.75 0 0012 9z" />
                                    <path fill-rule="evenodd" d="M9.344 3.071a49.52 49.52 0 015.312 0c.967.052 1.83.585 2.332 1.39l.821 1.317c.24.383.645.643 1.11.71.386.054.77.113 1.152.177 1.432.239 2.429 1.493 2.429 2.909V18a3 3 0 01-3 3h-15a3 3 0 01-3-3V9.574c0-1.416.997-2.67 2.429-2.909.382-.064.766-.123 1.151-.178a1.56 1.56 0 001.11-.71l.822-1.315a2.942 2.942 0 012.332-1.39zM6.75 12.75a5.25 5.25 0 1110.5 0 5.25 5.25 0 01-10.5 0zm12-1.5a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>

                        <div class="flex-1 min-w-0">
                            <h3 class="md:text-xl text-base font-semibold text-black dark:text-white truncate" data-socialite-edit-name-preview>{{ old('name', $user->name) }}</h3>
                            <p class="text-sm text-blue-600 mt-1 font-normal truncate">{{ '@' . $user->username }}</p>
                            <p class="text-xs text-gray-500 mt-1 dark:text-white/70" data-socialite-edit-headline-preview>{{ old('headline', $profile?->headline) ?: __('ui.profile_no_headline') }}</p>
                        </div>

                        <a href="{{ route('profile.show') }}" class="inline-flex items-center gap-1 py-1 pl-2.5 pr-3 rounded-full bg-slate-50 border-2 border-slate-100 dark:text-white dark:bg-slate-700">
                            <ion-icon name="person-outline" class="text-base"></ion-icon>
                            <span class="font-medium text-sm max-sm:hidden">{{ __('ui.my_profile') }}</span>
                        </a>
                    </div>

                    <div class="px-6 pb-5">
                        <div class="relative overflow-hidden rounded-2xl bg-slate-100 dark:bg-dark3">
                            <img src="{{ $coverUrl }}" alt="{{ $user->name }}" class="h-44 w-full object-cover" data-socialite-edit-cover-preview>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/60 to-transparent p-4 flex justify-end">
                                <button type="button" class="button bg-white/20 text-white flex items-center gap-2 backdrop-blur-small cursor-pointer" data-profile-media-edit-trigger="cover">
                                    <ion-icon name="image-outline" class="text-lg"></ion-icon>
                                    <span>{{ __('ui.change_cover') }}</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="mx-6 mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mx-6 mb-4 rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-700 dark:bg-red-500/10 dark:text-red-300">
                            {{ __('ui.profile_validation_error') }}
                        </div>
                    @endif

                    <div class="relative border-b" tabindex="-1" uk-slider="finite: true">
                        <nav class="uk-slider-container overflow-hidden nav__underline px-6 p-0 border-transparent -mb-px">
                            <ul class="uk-slider-items w-[calc(100%+10px)] !overflow-hidden"
                                uk-switcher="connect: #setting_tab ; animation: uk-animation-slide-right-medium, uk-animation-slide-left-medium">
                                <li class="w-auto pr-2.5"><a href="#">{{ __('ui.profile_info') }}</a></li>
                                <li class="w-auto pr-2.5"><a href="#">{{ __('ui.hunt_details') }}</a></li>
                                <li class="w-auto pr-2.5"><a href="#">{{ __('ui.social_stream') }}</a></li>
                                <li class="w-auto pr-2.5"><a href="#">{{ __('ui.change_avatar') }}</a></li>
                                <li class="w-auto pr-2.5"><a href="#">{{ __('ui.change_cover') }}</a></li>
                                <li class="w-auto pr-2.5"><a href="#">{{ __('ui.save_changes') }}</a></li>
                            </ul>
                        </nav>

                        <a class="absolute -translate-y-1/2 top-1/2 left-0 flex items-center w-20 h-full p-2 py-1 justify-start bg-gradient-to-r from-white via-white dark:from-slate-800 dark:via-slate-800" href="#" uk-slider-item="previous">
                            <ion-icon name="chevron-back" class="text-2xl ml-1"></ion-icon>
                        </a>
                        <a class="absolute right-0 -translate-y-1/2 top-1/2 flex items-center w-20 h-full p-2 py-1 justify-end bg-gradient-to-l from-white via-white dark:from-slate-800 dark:via-slate-800" href="#" uk-slider-item="next">
                            <ion-icon name="chevron-forward" class="text-2xl mr-1"></ion-icon>
                        </a>
                    </div>

                    <div id="setting_tab" class="uk-switcher md:py-12 md:px-20 p-6 overflow-hidden text-black text-sm dark:text-white">
                        <div>
                            <div class="space-y-6">
                                <div class="md:flex items-center gap-10">
                                    <label for="profile-name" class="md:w-32 text-right">{{ __('ui.display_name') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input id="profile-name" name="name" type="text" value="{{ old('name', $user->name) }}" maxlength="80" required class="lg:w-1/2 w-full" data-socialite-edit-live="name">
                                        @error('name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label class="md:w-32 text-right">Username</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input type="text" value="{{ '@' . $user->username }}" class="lg:w-1/2 w-full" disabled>
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label for="profile-headline" class="md:w-32 text-right">{{ __('ui.profile_headline') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input id="profile-headline" name="headline" type="text" value="{{ old('headline', $profile?->headline) }}" maxlength="120" class="w-full" data-socialite-edit-live="headline">
                                        @error('headline')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-start gap-10">
                                    <label for="profile-bio" class="md:w-32 text-right">{{ __('ui.profile_bio') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <textarea id="profile-bio" name="bio" rows="5" maxlength="1200" class="w-full" placeholder="{{ __('ui.profile_bio') }}">{{ old('bio', $profile?->bio) }}</textarea>
                                        @error('bio')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label for="profile-visibility" class="md:w-32 text-right">{{ __('ui.profile_visibility') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <select id="profile-visibility" name="profile_visibility" class="!border-0 !rounded-md lg:w-1/2 w-full" required>
                                            <option value="public" @selected($visibility === 'public')>{{ __('ui.visibility_public') }}</option>
                                            <option value="registered" @selected($visibility === 'registered')>{{ __('ui.visibility_registered') }}</option>
                                            <option value="private" @selected($visibility === 'private')>{{ __('ui.visibility_private') }}</option>
                                        </select>
                                        @error('profile_visibility')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label class="md:w-32 text-right">LFG</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input type="hidden" name="is_lfg_available" value="0">
                                        <label for="profile-is-lfg-available" class="hh-profile-lfg-native-line">
                                            <input id="profile-is-lfg-available" type="checkbox" name="is_lfg_available" value="1" class="hh-profile-lfg-native" @checked($isLfgAvailable)>
                                            <span>{{ __('ui.profile_lfg_available') }}</span>
                                        </label>
                                        @error('is_lfg_available')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="space-y-6">
                                <div class="md:flex items-center gap-10">
                                    <label for="profile-platform" class="md:w-32 text-right">{{ __('ui.platform') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <select id="profile-platform" name="platform" class="!border-0 !rounded-md lg:w-1/2 w-full">
                                            <option value="">{{ __('ui.select_option') }}</option>
                                            <option value="PC" @selected($platform === 'PC')>PC</option>
                                            <option value="Xbox" @selected($platform === 'Xbox')>Xbox</option>
                                            <option value="PlayStation" @selected($platform === 'PlayStation')>PlayStation</option>
                                        </select>
                                        @error('platform')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label for="profile-playstyle" class="md:w-32 text-right">{{ __('ui.playstyle') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <select id="profile-playstyle" name="playstyle" class="!border-0 !rounded-md lg:w-1/2 w-full">
                                            <option value="">{{ __('ui.select_option') }}</option>
                                            <option value="Ruhig / taktisch" @selected($playstyle === 'Ruhig / taktisch')>{{ __('ui.playstyle_tactical') }}</option>
                                            <option value="Aggressiv / PvP" @selected($playstyle === 'Aggressiv / PvP')>{{ __('ui.playstyle_aggressive') }}</option>
                                            <option value="Einsteigerfreundlich" @selected($playstyle === 'Einsteigerfreundlich')>{{ __('ui.playstyle_beginner') }}</option>
                                            <option value="Competitive" @selected($playstyle === 'Competitive')>Competitive</option>
                                            <option value="Casual" @selected($playstyle === 'Casual')>Casual</option>
                                        </select>
                                        @error('playstyle')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label for="profile-region" class="md:w-32 text-right">{{ __('ui.region') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input id="profile-region" name="region" type="text" value="{{ old('region', $profile?->region) }}" maxlength="60" class="lg:w-1/2 w-full">
                                        @error('region')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label for="profile-language" class="md:w-32 text-right">{{ __('ui.language') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input id="profile-language" name="language" type="text" value="{{ old('language', $profile?->language) }}" maxlength="40" class="lg:w-1/2 w-full">
                                        @error('language')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label for="profile-hunt-role" class="md:w-32 text-right">{{ __('ui.hunt_role') }}</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input id="profile-hunt-role" name="hunt_role" type="text" value="{{ old('hunt_role', $profile?->hunt_role) }}" maxlength="60" class="w-full">
                                        @error('hunt_role')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>

                                <div class="md:flex items-center gap-10">
                                    <label for="profile-discord" class="md:w-32 text-right">Discord</label>
                                    <div class="flex-1 max-md:mt-4">
                                        <input id="profile-discord" name="discord_name" type="text" value="{{ old('discord_name', $profile?->discord_name) }}" maxlength="80" class="w-full">
                                        @error('discord_name')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="max-w-md mx-auto">
                                <div>
                                    <h4 class="text-xl font-medium text-black dark:text-white">{{ __('ui.social_stream') }}</h4>
                                    <p class="mt-3 font-normal text-gray-600 dark:text-white/70">Steam, Twitch und YouTube werden auf deinem Profil angezeigt, wenn du sie ausfüllst.</p>
                                </div>

                                <div class="space-y-6 mt-8">
                                    <div class="flex items-center gap-3">
                                        <div class="bg-slate-100 rounded-full p-2 flex dark:bg-dark3"><ion-icon name="logo-steam" class="text-2xl"></ion-icon></div>
                                        <div class="flex-1">
                                            <input id="profile-steam" name="steam_url" type="url" value="{{ old('steam_url', $profile?->steam_url) }}" maxlength="255" class="w-full" placeholder="https://steamcommunity.com/id/...">
                                            @error('steam_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="bg-purple-50 rounded-full p-2 flex dark:bg-dark3"><ion-icon name="logo-twitch" class="text-2xl text-purple-600"></ion-icon></div>
                                        <div class="flex-1">
                                            <input id="profile-twitch" name="twitch_url" type="url" value="{{ old('twitch_url', $profile?->twitch_url) }}" maxlength="255" class="w-full" placeholder="https://www.twitch.tv/...">
                                            @error('twitch_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <div class="bg-red-50 rounded-full p-2 flex dark:bg-dark3"><ion-icon name="logo-youtube" class="text-2xl text-red-600"></ion-icon></div>
                                        <div class="flex-1">
                                            <input id="profile-youtube" name="youtube_url" type="url" value="{{ old('youtube_url', $profile?->youtube_url) }}" maxlength="255" class="w-full" placeholder="https://www.youtube.com/@...">
                                            @error('youtube_url')<p class="mt-1 text-xs text-red-500">{{ $message }}</p>@enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="max-w-md mx-auto text-center">
                                <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="mx-auto h-28 w-28 rounded-full object-cover shadow-lg" data-socialite-edit-avatar-preview-secondary>
                                <h4 class="mt-5 text-xl font-medium text-black dark:text-white">{{ __('ui.change_avatar') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.avatar_upload_hint') }}</p>
                                <button type="button" class="button bg-secondery mt-6 inline-flex cursor-pointer dark:bg-dark3" data-profile-media-edit-trigger="avatar">{{ __('ui.change_avatar') }}</button>
                                @error('avatar')<p class="mt-3 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <div class="max-w-md mx-auto text-center">
                                <img src="{{ $coverUrl }}" alt="{{ $user->name }}" class="mx-auto h-36 w-full rounded-2xl object-cover shadow-lg" data-socialite-edit-cover-preview-secondary>
                                <h4 class="mt-5 text-xl font-medium text-black dark:text-white">{{ __('ui.change_cover') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">{{ __('ui.cover_upload_hint') }}</p>
                                <button type="button" class="button bg-secondery mt-6 inline-flex cursor-pointer dark:bg-dark3" data-profile-media-edit-trigger="cover">{{ __('ui.change_cover') }}</button>
                                @error('cover')<p class="mt-3 text-xs text-red-500">{{ $message }}</p>@enderror
                            </div>
                        </div>

                        <div>
                            <div class="max-w-md mx-auto text-center">
                                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-blue-50 text-blue-600 dark:bg-white/10 dark:text-white">
                                    <ion-icon name="checkmark-done-outline" class="text-4xl"></ion-icon>
                                </div>
                                <h4 class="mt-5 text-xl font-medium text-black dark:text-white">{{ __('ui.save_changes') }}</h4>
                                <p class="mt-3 font-normal text-gray-600 dark:text-white/70">Profilfortschritt: <strong data-socialite-edit-completion>{{ $completion }}%</strong>. Prüfe deine Angaben und speichere die Änderungen.</p>

                                <div class="flex items-center gap-4 mt-10 justify-center">
                                    <a href="{{ route('profile.show') }}" class="button lg:px-6 bg-secondery max-md:flex-1">{{ __('ui.discard_all') }}</a>
                                    <button type="submit" class="button lg:px-10 bg-primary text-white max-md:flex-1">{{ __('ui.save_changes') }}</button>
                                </div>

                                <a href="{{ route('profile.edit', ['classic_profile' => 1]) }}" class="mt-5 inline-block text-sm text-blue-500">{{ __('ui.classic_editor') }}</a>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 border-t px-6 py-5 dark:border-slate-700">
                        <a href="{{ route('profile.show') }}" class="button bg-secondery max-md:flex-1 dark:bg-dark3">{{ __('ui.discard_all') }}</a>
                        <button type="submit" class="button bg-primary text-white max-md:flex-1">{{ __('ui.save_changes') }}</button>
                    </div>
                </form>
            </div>

                <input id="profile-media-crop-input" type="file" accept="image/jpeg,image/png,image/webp" class="hidden">
                <div id="profile-media-crop-toast" class="hidden fixed right-5 top-24 max-w-sm rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white shadow-2xl" style="z-index: 100001;"></div>

                <div id="profile-media-crop-modal" class="hidden fixed inset-0 flex items-start justify-center bg-black/75 px-3 py-8 md:items-center md:px-5" style="z-index: 100000;" aria-hidden="true">
                    <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-dark2" style="max-height: calc(100vh - 64px);">
                        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4 dark:border-slate-700">
                            <div class="min-w-0">
                                <h3 id="profile-media-crop-title" class="text-lg font-bold text-black dark:text-white">{{ __('ui.crop_image') }}</h3>
                                <p id="profile-media-crop-help" class="mt-1 text-sm text-gray-500 dark:text-white/70">{{ __('ui.crop_image_help') }}</p>
                            </div>
                            <button type="button" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-slate-100 text-slate-600 hover:bg-slate-200 dark:bg-dark3 dark:text-white" data-profile-media-crop-close aria-label="{{ __('ui.close_crop_dialog') }}">
                                <ion-icon name="close-outline" class="text-xl"></ion-icon>
                            </button>
                        </div>

                        <div class="overflow-y-auto p-5" style="max-height: calc(100vh - 150px);">
                            <div id="profile-media-crop-stage" class="flex items-center justify-center overflow-hidden rounded-2xl bg-slate-100 p-3 dark:bg-dark3" style="max-height: 48vh;">
                                <canvas id="profile-media-crop-canvas" class="block max-w-full cursor-move rounded-xl bg-black shadow-sm"></canvas>
                            </div>

                            <div class="mt-5 grid gap-4 md:grid-cols-[1fr_auto] md:items-end">
                                <label class="text-sm font-semibold text-black dark:text-white">
                                    Zoom
                                    <input id="profile-media-crop-zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full accent-blue-600">
                                </label>

                                <div class="flex flex-wrap gap-2 md:justify-end">
                                    <button type="button" id="profile-media-crop-rechoose" class="button bg-secondery px-4 py-2 dark:bg-dark3">{{ __('ui.choose_another') }}</button>
                                    <button type="button" data-profile-media-crop-close class="button bg-secondery px-4 py-2 dark:bg-dark3">{{ __('ui.cancel') }}</button>
                                    <button type="button" id="profile-media-crop-save" class="button bg-primary px-4 py-2 text-white">{{ __('ui.save_image') }}</button>
                                </div>
                            </div>

                            <p id="profile-media-crop-error" class="mt-4 hidden rounded-xl bg-red-50 px-4 py-3 text-sm font-semibold text-red-600 dark:bg-red-500/10 dark:text-red-300"></p>
                            <p id="profile-media-crop-status" class="mt-4 hidden rounded-xl bg-blue-50 px-4 py-3 text-sm font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-200"></p>
                        </div>
                    </div>
                </div>
@endsection

@push('scripts')
<script>
(() => {
    const nameInput = document.querySelector('[data-socialite-edit-live="name"]');
    const headlineInput = document.querySelector('[data-socialite-edit-live="headline"]');
    const namePreview = document.querySelector('[data-socialite-edit-name-preview]');
    const headlinePreview = document.querySelector('[data-socialite-edit-headline-preview]');
    const avatarTargets = document.querySelectorAll('[data-socialite-edit-avatar-preview], [data-socialite-edit-avatar-preview-secondary]');
    const coverTargets = document.querySelectorAll('[data-socialite-edit-cover-preview], [data-socialite-edit-cover-preview-secondary]');
    const emptyHeadline = @json(__('ui.profile_no_headline'));

    nameInput?.addEventListener('input', () => {
        if (namePreview) {
            namePreview.textContent = nameInput.value.trim() || @json($user->name);
        }
    });

    headlineInput?.addEventListener('input', () => {
        if (headlinePreview) {
            headlinePreview.textContent = headlineInput.value.trim() || emptyHeadline;
        }
    });

    const updateUrl = @json($mediaUpdateUrl);
    const csrfToken = @json(csrf_token());
    const triggers = document.querySelectorAll('[data-profile-media-edit-trigger]');
    const input = document.getElementById('profile-media-crop-input');
    const modal = document.getElementById('profile-media-crop-modal');
    const canvas = document.getElementById('profile-media-crop-canvas');
    const cropStage = document.getElementById('profile-media-crop-stage');
    const zoomInput = document.getElementById('profile-media-crop-zoom');
    const saveButton = document.getElementById('profile-media-crop-save');
    const rechooseButton = document.getElementById('profile-media-crop-rechoose');
    const title = document.getElementById('profile-media-crop-title');
    const help = document.getElementById('profile-media-crop-help');
    const errorBox = document.getElementById('profile-media-crop-error');
    const statusBox = document.getElementById('profile-media-crop-status');
    const toastBox = document.getElementById('profile-media-crop-toast');
    const closeButtons = document.querySelectorAll('[data-profile-media-crop-close]');
    const ctx = canvas?.getContext('2d');

    if (!input || !modal || !canvas || !cropStage || !ctx || !zoomInput || !saveButton) {
        return;
    }

    const presets = {
        avatar: {
            label: 'Crop avatar',
            help: 'Square crop for the round profile avatar.',
            outputWidth: 900,
            outputHeight: 900,
            fileName: 'hnt-avatar.jpg',
        },
        cover: {
            label: 'Crop cover image',
            help: 'Wide crop for the profile title image.',
            outputWidth: 1800,
            outputHeight: 600,
            fileName: 'hnt-cover.jpg',
        },
    };

    const state = {
        type: 'avatar',
        image: null,
        objectUrl: null,
        zoom: 1,
        offsetX: 0,
        offsetY: 0,
        drag: null,
        busy: false,
    };

    const activePreset = () => presets[state.type] || presets.avatar;

    const setError = (message = '') => {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.toggle('hidden', !message);
        if (message && statusBox) {
            statusBox.textContent = '';
            statusBox.classList.add('hidden');
        }
    };

    const setStatus = (message = '') => {
        if (!statusBox) return;
        statusBox.textContent = message;
        statusBox.classList.toggle('hidden', !message);
        if (message && errorBox) {
            errorBox.textContent = '';
            errorBox.classList.add('hidden');
        }
    };

    const showToast = (message = '') => {
        if (!toastBox || !message) return;
        toastBox.textContent = message;
        toastBox.classList.remove('hidden');
        window.clearTimeout(showToast.timeoutId);
        showToast.timeoutId = window.setTimeout(() => {
            toastBox.classList.add('hidden');
        }, 2400);
    };

    const setBusy = (isBusy) => {
        state.busy = isBusy;
        saveButton.disabled = isBusy;
        saveButton.classList.toggle('opacity-70', isBusy);
        saveButton.classList.toggle('cursor-not-allowed', isBusy);
        saveButton.setAttribute('aria-busy', isBusy ? 'true' : 'false');
        if (rechooseButton) {
            rechooseButton.disabled = isBusy;
            rechooseButton.classList.toggle('opacity-70', isBusy);
            rechooseButton.classList.toggle('cursor-not-allowed', isBusy);
        }
        closeButtons.forEach((button) => {
            button.disabled = isBusy;
            button.classList.toggle('opacity-60', isBusy);
            button.classList.toggle('cursor-not-allowed', isBusy);
        });
    };

    const bustUrl = (url) => {
        if (!url) return url;
        const glue = url.includes('?') ? '&' : '?';
        return `${url}${glue}v=${Date.now()}`;
    };

    const updatePreviews = (type, url) => {
        const targets = type === 'cover' ? coverTargets : avatarTargets;
        const busted = bustUrl(url);
        targets.forEach((target) => {
            if (busted) {
                target.src = busted;
            }
        });
    };

    const configureCropPreview = () => {
        const isAvatar = state.type === 'avatar';
        canvas.classList.toggle('rounded-full', isAvatar);
        canvas.classList.toggle('rounded-xl', !isAvatar);
        canvas.style.width = isAvatar ? '420px' : '100%';
        canvas.style.maxWidth = '100%';
        canvas.style.height = 'auto';
        canvas.style.maxHeight = isAvatar ? '420px' : '340px';
        cropStage.style.maxHeight = isAvatar ? '48vh' : '42vh';
        cropStage.classList.toggle('p-3', isAvatar);
        cropStage.classList.toggle('p-2', !isAvatar);
    };

    const openModal = () => {
        const preset = activePreset();
        title.textContent = preset.label;
        help.textContent = preset.help;
        configureCropPreview();
        setStatus('');
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');
        modal.scrollTop = 0;
        document.documentElement.classList.add('overflow-hidden');
        document.body.classList.add('overflow-hidden');
    };

    const closeModal = () => {
        if (state.busy) return;
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('overflow-hidden');
        document.body.classList.remove('overflow-hidden');
        setError('');
        setStatus('');
    };

    const resetImageState = () => {
        state.zoom = 1;
        state.offsetX = 0;
        state.offsetY = 0;
        zoomInput.value = '1';
        if (state.objectUrl) {
            URL.revokeObjectURL(state.objectUrl);
            state.objectUrl = null;
        }
        state.image = null;
        state.drag = null;
    };

    const render = () => {
        if (!state.image) return;
        const preset = activePreset();
        canvas.width = preset.outputWidth;
        canvas.height = preset.outputHeight;
        configureCropPreview();
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        ctx.fillStyle = '#000000';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const baseScale = Math.max(canvas.width / state.image.naturalWidth, canvas.height / state.image.naturalHeight);
        const scale = baseScale * state.zoom;
        const drawWidth = state.image.naturalWidth * scale;
        const drawHeight = state.image.naturalHeight * scale;
        let x = (canvas.width - drawWidth) / 2 + state.offsetX;
        let y = (canvas.height - drawHeight) / 2 + state.offsetY;
        const minX = canvas.width - drawWidth;
        const minY = canvas.height - drawHeight;
        x = Math.min(0, Math.max(minX, x));
        y = Math.min(0, Math.max(minY, y));
        state.offsetX = x - (canvas.width - drawWidth) / 2;
        state.offsetY = y - (canvas.height - drawHeight) / 2;
        ctx.drawImage(state.image, x, y, drawWidth, drawHeight);
    };

    const chooseFile = (type) => {
        if (state.busy) return;
        state.type = type === 'cover' ? 'cover' : 'avatar';
        input.value = '';
        input.click();
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            chooseFile(trigger.dataset.profileMediaEditTrigger);
        });
    });

    input.addEventListener('change', () => {
        const file = input.files?.[0];
        if (!file) return;
        if (!file.type.startsWith('image/')) {
            setError('Please choose an image file.');
            return;
        }
        resetImageState();
        state.objectUrl = URL.createObjectURL(file);
        state.image = new Image();
        state.image.onload = () => {
            openModal();
            render();
        };
        state.image.onerror = () => setError('The selected image could not be loaded.');
        state.image.src = state.objectUrl;
    });

    zoomInput.addEventListener('input', () => {
        state.zoom = Number.parseFloat(zoomInput.value) || 1;
        render();
    });

    const pointerPosition = (event) => {
        const rect = canvas.getBoundingClientRect();
        return {
            x: (event.clientX - rect.left) * (canvas.width / rect.width),
            y: (event.clientY - rect.top) * (canvas.height / rect.height),
        };
    };

    canvas.addEventListener('pointerdown', (event) => {
        if (!state.image) return;
        canvas.setPointerCapture(event.pointerId);
        const position = pointerPosition(event);
        state.drag = {
            x: position.x,
            y: position.y,
            offsetX: state.offsetX,
            offsetY: state.offsetY,
        };
    });

    canvas.addEventListener('pointermove', (event) => {
        if (!state.drag) return;
        const position = pointerPosition(event);
        state.offsetX = state.drag.offsetX + (position.x - state.drag.x);
        state.offsetY = state.drag.offsetY + (position.y - state.drag.y);
        render();
    });

    const stopDragging = () => {
        state.drag = null;
    };

    canvas.addEventListener('pointerup', stopDragging);
    canvas.addEventListener('pointercancel', stopDragging);
    canvas.addEventListener('pointerleave', stopDragging);
    closeButtons.forEach((button) => button.addEventListener('click', closeModal));
    rechooseButton?.addEventListener('click', () => chooseFile(state.type));

    saveButton.addEventListener('click', async () => {
        if (!state.image || state.busy) return;
        setError('');
        setStatus(@json(__('ui.preparing_image')));
        setBusy(true);
        const originalLabel = saveButton.textContent;
        saveButton.textContent = @json(__('ui.saving'));

        try {
            const preset = activePreset();
            const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', 0.9));
            if (!blob) {
                throw new Error(@json(__('ui.crop_export_failed')));
            }

            const formData = new FormData();
            formData.append('type', state.type);
            formData.append('image', blob, preset.fileName);
            setStatus(@json(__('ui.uploading_image')));

            const response = await fetch(updateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const contentType = response.headers.get('content-type') || '';
            const payload = contentType.includes('application/json')
                ? await response.json().catch(() => ({}))
                : {};

            if (!response.ok) {
                const validationMessage = payload?.errors ? Object.values(payload.errors).flat().join(' ') : null;
                throw new Error(validationMessage || payload?.message || @json(__('ui.image_save_failed')));
            }

            if (state.type === 'avatar' && payload.avatar_url) {
                updatePreviews('avatar', payload.avatar_url);
            }

            if (state.type === 'cover' && payload.cover_url) {
                updatePreviews('cover', payload.cover_url);
            }

            setStatus('Saved. Updating preview...');
            setBusy(false);
            showToast(payload?.message || 'Profile image updated.');
            closeModal();
            resetImageState();
        } catch (error) {
            setError(error?.message || 'The image could not be saved.');
        } finally {
            setBusy(false);
            saveButton.textContent = originalLabel;
        }
    });
})();
</script>
@endpush
