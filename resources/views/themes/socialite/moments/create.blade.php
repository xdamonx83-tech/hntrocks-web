@extends('themes.socialite.layouts.app')

@section('title', __('ui.moment_create_title') . ' · hnt.rocks')

@push('head')
<style>
    .hh-moment-create-drop.has-preview .hh-moment-create-empty { display: none; }
    .hh-moment-create-preview { display: none; }
    .hh-moment-create-drop.has-preview .hh-moment-create-preview { display: block; }
    .hh-moment-create-cover.has-image .hh-moment-create-cover-icon { display: none; }
    .hh-moment-create-cover img { display: none; }
    .hh-moment-create-cover.has-image img { display: block; }
    .hh-moment-create-form.is-submitting .hh-moment-create-submit-label { display: none; }
    .hh-moment-create-submit-state { display: none; }
    .hh-moment-create-form.is-submitting .hh-moment-create-submit-state { display: inline-flex; }

    .hh-moment-create-native-file {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }
    .hh-moment-create-spinner {
        width: 16px;
        height: 16px;
        border-radius: 999px;
        border: 2px solid rgba(255,255,255,.45);
        border-top-color: #fff;
        animation: hhMomentCreateSpin .75s linear infinite;
    }
    .hh-moment-hashtag-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 8px;
    }
    .hh-moment-hashtag-preview[hidden] { display: none !important; }
    .hh-moment-hashtag-preview a {
        display: inline-flex;
        min-height: 24px;
        align-items: center;
        border-radius: 999px;
        padding: 0 10px;
        background: rgba(214,168,79,.14);
        color: #d6a84f;
        font-size: 12px;
        font-weight: 800;
        text-decoration: none;
    }
    @keyframes hhMomentCreateSpin { to { transform: rotate(360deg); } }
</style>
@endpush

@section('content')
@php
    $momentCards = [
        [
            'icon' => 'ph-video-camera',
            'title' => __('ui.moment_create_card_upload_title'),
            'text' => __('ui.moment_create_card_upload_text'),
            'label' => __('ui.step_1'),
            'progress' => 35,
        ],
        [
            'icon' => 'ph-text-aa',
            'title' => __('ui.moment_create_card_context_title'),
            'text' => __('ui.moment_create_card_context_text'),
            'label' => __('ui.step_2'),
            'progress' => 65,
        ],
        [
            'icon' => 'ph-paper-plane-tilt',
            'title' => __('ui.moment_create_card_publish_title'),
            'text' => __('ui.moment_create_card_publish_text'),
            'label' => __('ui.step_3'),
            'progress' => 100,
        ],
        [
            'icon' => 'ph-scissors',
            'title' => __('ui.moment_create_card_trim_title'),
            'text' => __('ui.moment_create_card_trim_text'),
            'label' => __('ui.optional'),
            'progress' => 45,
        ],
    ];

    $tips = [
        ['icon' => 'ph-device-mobile-camera', 'title' => __('ui.moment_tip_vertical_title'), 'text' => __('ui.moment_tip_vertical_text')],
        ['icon' => 'ph-timer', 'title' => __('ui.moment_tip_short_title'), 'text' => __('ui.moment_tip_short_text')],
        ['icon' => 'ph-chat-circle-text', 'title' => __('ui.moment_tip_description_title'), 'text' => __('ui.moment_tip_description_text')],
    ];
@endphp

<form class="hh-moment-create-form" method="post" action="{{ route('moments.store') }}" enctype="multipart/form-data" data-moment-create-form>
    @csrf

    <div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-moment-create-page">
        <div class="flex-1">
            <div class="page-heading">
                <h1 class="page-title">{{ __('ui.moment_create_title') }}</h1>

                <nav class="nav__underline">
                    <ul class="group">
                        <li class="uk-active"><a href="#moment-upload">{{ __('ui.upload') }}</a></li>
                        <li><a href="#moment-details">{{ __('ui.details') }}</a></li>
                    </ul>
                </nav>
            </div>

            @if ($errors->any())
                <div class="box p-4 mb-6 border border-red-100 bg-red-50 text-red-700 dark:bg-red-500/10 dark:border-red-500/20 dark:text-red-200">
                    <div class="flex items-start gap-3">
                        <i class="ph ph-warning-circle flex shrink-0" style="font-size: 24px;"></i>
                        <div>
                            <p class="text-sm font-semibold">{{ __('ui.form_check_inputs') }}</p>
                            <ul class="mt-2 text-xs leading-6 list-disc ml-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="box overflow-hidden mb-6">
                <div class="relative bg-secondery dark:bg-white/5" style="min-height: 260px;">
                    <img src="{{ asset('assets/socialite/images/ad_pattern.png') }}" alt="" class="absolute inset-0 w-full h-full object-cover opacity-30">
                    <div class="absolute inset-0 bg-gradient-to-br from-slate-950/90 via-slate-900/78 to-blue-900/72"></div>
                    <div class="relative p-6 sm:p-8 md:p-10 text-white max-w-3xl">
                        <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 text-xs font-semibold uppercase tracking-wide">
                            <i class="ph ph-video-camera" style="font-size: 18px;"></i>
                            HNT.rocks Moments
                        </div>
                        <h2 class="mt-5 text-3xl md:text-4xl font-bold leading-tight">{{ __('ui.moment_create_hero_title') }}</h2>
                        <p class="mt-4 text-sm md:text-base text-white/80 leading-7">{{ __('ui.moment_create_hero_text') }}</p>
                        <div class="flex flex-wrap items-center gap-3 mt-6">
                            <a href="#moment-upload" class="button bg-primary text-white !w-auto px-5">{{ __('ui.moment_select_clip') }}</a>
                            <a href="{{ route('feed.index') }}" class="button bg-white/10 text-white !w-auto px-5">{{ __('ui.back_to_feed') }}</a>
                        </div>
                    </div>
                </div>
            </div>

            <div tabindex="-1" uk-slider="finite:true">
                <div class="uk-slider-container pb-1">
                    <ul class="uk-slider-items grid-small">
                        @foreach ($momentCards as $card)
                            <li class="sm:w-1/3 w-1/2">
                                <div class="card">
                                    <div class="card-media h-32 bg-secondery flex items-center justify-center dark:bg-white/5">
                                        <i class="ph {{ $card['icon'] }} text-blue-600" style="font-size: 46px;"></i>
                                    </div>
                                    <div class="card-body">
                                        <h4 class="card-title text-sm line-clamp-2">{{ $card['title'] }}</h4>
                                        <p class="mt-2 text-xs text-gray-500 line-clamp-2 dark:text-white/70">{{ $card['text'] }}</p>
                                        <div class="text-blue-500 font-medium text-xs mt-3">{{ $card['label'] }}</div>
                                    </div>
                                    <div class="bg-secondery rounded-2xl h-1 w-full relative overflow-hidden">
                                        <div class="bg-blue-600 h-full" style="width: {{ $card['progress'] }}%"></div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <a class="nav-prev !top-24" href="#" uk-slider-item="previous"><i class="ph ph-caret-left" style="font-size: 22px;"></i></a>
                <a class="nav-next !top-24" href="#" uk-slider-item="next"><i class="ph ph-caret-right" style="font-size: 22px;"></i></a>
            </div>

            <div id="moment-upload" class="flex items-center justify-between text-black dark:text-white py-3 mt-10">
                <h3 class="text-xl font-semibold">{{ __('ui.moment_video_upload') }}</h3>
                <span class="text-sm text-blue-500">{{ __('ui.up_to_about_mb', ['mb' => $limits['videoMb']]) }}</span>
            </div>

            <div class="box p-5 mt-3">
                <label class="hh-moment-create-drop block relative overflow-hidden rounded-2xl bg-secondery dark:bg-white/5 cursor-pointer" style="min-height: 520px;" data-moment-video-drop>
                    <input class="hh-moment-create-native-file" id="video" name="video" type="file" accept="video/mp4,video/webm,video/quicktime" required data-moment-video-input>

                    <div class="hh-moment-create-empty absolute inset-0 flex flex-col items-center justify-center text-center px-6">
                        <div class="flex items-center justify-center rounded-3xl bg-white shadow-sm dark:bg-white/10" style="width: 86px; height: 86px;">
                            <i class="ph ph-upload-simple text-blue-600" style="font-size: 42px;"></i>
                        </div>
                        <h3 class="mt-5 text-2xl font-bold text-black dark:text-white">{{ __('ui.moment_drop_video') }}</h3>
                        <p class="mt-2 max-w-md text-sm leading-7 text-gray-500 dark:text-white/70">{{ __('ui.moment_drop_video_text') }}</p>
                        <span class="button bg-primary text-white !w-auto px-5 mt-5">{{ __('ui.moment_select_video') }}</span>
                    </div>

                    <div class="hh-moment-create-preview absolute inset-0 bg-black">
                        <video class="w-full h-full object-contain bg-black" muted playsinline loop data-moment-video-preview></video>
                        <div class="absolute inset-x-0 bottom-0 p-5 bg-gradient-to-t from-black/80 to-transparent text-white">
                            <div class="flex items-end justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-bold truncate" data-moment-video-name>{{ __('ui.moment_selected_video') }}</p>
                                    <p class="text-xs text-white/70 mt-1" data-moment-video-meta>Vorschau bereit</p>
                                </div>
                                <span class="rounded-full bg-white/15 border border-white/20 px-4 py-2 text-xs font-bold">Ändern</span>
                            </div>
                        </div>
                    </div>
                </label>
            </div>

            <div class="box p-5 mt-6">
                @foreach ($tips as $tip)
                    <div class="card-list">
                        <div class="card-list-media bg-secondery flex items-center justify-center dark:bg-white/5" style="width: 92px; min-width: 92px; height: 86px;">
                            <i class="ph {{ $tip['icon'] }} text-blue-600" style="font-size: 32px;"></i>
                        </div>
                        <div class="card-list-body">
                            <h3 class="card-list-title">{{ $tip['title'] }}</h3>
                            <div class="card-list-text">
                                <p>{{ $tip['text'] }}</p>
                            </div>
                        </div>
                    </div>

                    @if (! $loop->last)
                        <hr class="card-list-divider">
                    @endif
                @endforeach
            </div>
        </div>

        <aside class="2xl:w-[380px] lg:w-[330px] w-full">
            <div class="lg:space-y-6 space-y-4 lg:pb-8 max-lg:grid sm:grid-cols-2 max-lg:gap-6" uk-sticky="media: 1024; end: #js-moment-create-page; offset: 80; bottom:true">
                <div id="moment-details" class="box p-5">
                    <div class="flex items-center gap-3 mb-4">
                        <i class="ph ph-sparkle flex shrink-0 p-2 rounded-full bg-blue-100 text-blue-600 dark:bg-blue-500/20" style="font-size: 24px;"></i>
                        <div>
                            <h2 class="font-bold text-base text-black dark:text-white">{{ __('ui.moment_details') }}</h2>
                            <p class="text-xs text-gray-500 mt-0.5 dark:text-white/70">{{ __('ui.moment_details_text') }}</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div>
                            <label for="moment-caption" class="flex items-center justify-between text-xs font-semibold text-gray-500 dark:text-white/70">
                                <span>{{ __('ui.caption') }}</span>
                                <span><span data-moment-count="caption">{{ mb_strlen(old('caption', '')) }}</span>/220</span>
                            </label>
                            <input id="moment-caption" type="text" name="caption" value="{{ old('caption') }}" maxlength="220" placeholder="{{ __('ui.moment_caption_placeholder') }}" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm dark:bg-white/5" data-moment-count-input="caption" data-hnt-hashtag-input>
                            <div class="hh-moment-hashtag-preview" data-hnt-hashtag-preview hidden></div>
                            @error('caption')<small class="block mt-1 text-xs text-red-500">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="moment-description" class="flex items-center justify-between text-xs font-semibold text-gray-500 dark:text-white/70">
                                <span>{{ __('ui.description') }}</span>
                                <span><span data-moment-count="description">{{ mb_strlen(old('description', '')) }}</span>/2000</span>
                            </label>
                            <textarea id="moment-description" name="description" maxlength="2000" rows="5" placeholder="{{ __('ui.moment_description_placeholder') }}" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm leading-6 resize-y dark:bg-white/5" data-moment-count-input="description" data-hnt-hashtag-input>{{ old('description') }}</textarea>
                            <div class="hh-moment-hashtag-preview" data-hnt-hashtag-preview hidden></div>
                            @error('description')<small class="block mt-1 text-xs text-red-500">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-gray-500 dark:text-white/70">{{ __('ui.cover_optional') }}</label>
                            <div class="flex items-center gap-3 mt-1 rounded-xl bg-secondery p-3 dark:bg-white/5">
                                <div class="hh-moment-create-cover flex items-center justify-center rounded-xl bg-white overflow-hidden dark:bg-white/10" style="width: 72px; min-width: 72px; height: 72px;" data-moment-cover-preview>
                                    <i class="ph ph-image hh-moment-create-cover-icon text-gray-400" style="font-size: 28px;"></i>
                                    <img alt="{{ __('ui.cover_preview') }}" class="w-full h-full object-cover" data-moment-cover-img>
                                </div>
                                <div class="min-w-0">
                                    <label class="button bg-white text-black !w-auto px-4 dark:bg-white/10 dark:text-white" for="cover">{{ __('ui.choose_cover') }}</label>
                                    <input class="hh-moment-create-native-file" id="cover" name="cover" type="file" accept="image/*" data-moment-cover-input>
                                    <p class="text-xs text-gray-500 mt-2 dark:text-white/60">{{ __('ui.optional_up_to_mb', ['mb' => $limits['coverMb']]) }}</p>
                                </div>
                            </div>
                            @error('cover')<small class="block mt-1 text-xs text-red-500">{{ $message }}</small>@enderror
                        </div>

                        <div>
                            <label for="moment-visibility" class="text-xs font-semibold text-gray-500 dark:text-white/70">{{ __('ui.visibility') }}</label>
                            <select id="moment-visibility" name="visibility" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm dark:bg-white/5">
                                <option value="public" @selected(old('visibility', 'public') === 'public')>{{ __('ui.public') }}</option>
                                <option value="registered" @selected(old('visibility') === 'registered')>{{ __('ui.visibility_registered_users') }}</option>
                                <option value="private" @selected(old('visibility') === 'private')>{{ __('ui.private_only_me') }}</option>
                            </select>
                            @error('visibility')<small class="block mt-1 text-xs text-red-500">{{ $message }}</small>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="moment-trim-start" class="text-xs font-semibold text-gray-500 dark:text-white/70">{{ __('ui.trim_start_seconds') }}</label>
                                <input id="moment-trim-start" name="trim_start_seconds" type="number" min="0" max="86400" value="{{ old('trim_start_seconds') }}" placeholder="0" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm dark:bg-white/5">
                            </div>
                            <div>
                                <label for="moment-trim-end" class="text-xs font-semibold text-gray-500 dark:text-white/70">{{ __('ui.trim_end_seconds') }}</label>
                                <input id="moment-trim-end" name="trim_end_seconds" type="number" min="1" max="86400" value="{{ old('trim_end_seconds') }}" placeholder="28" class="w-full mt-1 rounded-lg bg-secondery border-0 text-sm dark:bg-white/5">
                            </div>
                        </div>
                        @error('trim_start_seconds')<small class="block text-xs text-red-500">{{ $message }}</small>@enderror
                        @error('trim_end_seconds')<small class="block text-xs text-red-500">{{ $message }}</small>@enderror

                        <button type="submit" class="button bg-primary text-white w-full">
                            <span class="hh-moment-create-submit-label inline-flex items-center gap-2">
                                <i class="ph ph-paper-plane-tilt" style="font-size: 18px;"></i>
                                {{ __('ui.moment_publish') }}
                            </span>
                            <span class="hh-moment-create-submit-state items-center gap-2">
                                <span class="hh-moment-create-spinner"></span>
                                {{ __('ui.upload_running') }}
                            </span>
                        </button>
                    </div>
                </div>

                <div class="box p-5">
                    <h3 class="font-semibold text-base text-black dark:text-white">{{ __('ui.moment_how_it_works') }}</h3>
                    <p class="text-sm mt-1 text-gray-500 dark:text-white/70">{{ __('ui.moment_how_it_works_text') }}</p>

                    <ul class="relative space-y-2.5 text-sm mt-4" uk-accordion="active: 0">
                        <li class="rounded-md bg-secondery dark:bg-white/5">
                            <a class="flex items-center justify-between p-3 py-2 font-semibold group text-black dark:text-white uk-accordion-title" href="#">
                                {{ __('ui.moment_select_video') }}
                                <i class="ph ph-caret-down duration-200 -mr-0.5 group-aria-expanded:rotate-180" style="font-size: 16px;"></i>
                            </a>
                            <div class="p-3 pt-0 dark:text-white/80 uk-accordion-content">
                                <p>{{ __('ui.moment_step_select_text') }}</p>
                            </div>
                        </li>
                        <li class="rounded-md bg-secondery dark:bg-white/5">
                            <a class="flex items-center justify-between p-3 py-2 font-semibold group text-black dark:text-white uk-accordion-title" href="#">
                                {{ __('ui.moment_set_details') }}
                                <i class="ph ph-caret-down duration-200 -mr-0.5 group-aria-expanded:rotate-180" style="font-size: 16px;"></i>
                            </a>
                            <div class="p-3 pt-0 dark:text-white/80 uk-accordion-content">
                                <p>{{ __('ui.moment_step_details_text') }}</p>
                            </div>
                        </li>
                        <li class="rounded-md bg-secondery dark:bg-white/5">
                            <a class="flex items-center justify-between p-3 py-2 font-semibold group text-black dark:text-white uk-accordion-title" href="#">
                                {{ __('ui.moment_post') }}
                                <i class="ph ph-caret-down duration-200 -mr-0.5 group-aria-expanded:rotate-180" style="font-size: 16px;"></i>
                            </a>
                            <div class="p-3 pt-0 dark:text-white/80 uk-accordion-content">
                                <p>{{ __('ui.moment_step_publish_text') }}</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </aside>
    </div>
</form>
@endsection

@push('scripts')
<script src="/assets/socialite/js/hnt-socialite-moment-create.js?v=486"></script>

<script>
(() => {
    const normalize = (value) => String(value || '')
        .replace(/^#+/, '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9_-]/g, '')
        .replace(/^[-_]+|[-_]+$/g, '')
        .slice(0, 50);

    const extract = (value) => {
        let matches = [];
        try {
            matches = String(value || '').match(/(?<![\p{L}\p{N}_])#[\p{L}\p{N}_][\p{L}\p{N}_-]{0,49}/gu) || [];
        } catch (error) {
            matches = String(value || '').match(/(^|\s)#[A-Za-z0-9_][A-Za-z0-9_-]{0,49}/g) || [];
        }
        const seen = new Set();
        return matches.map((item) => normalize(item.trim())).filter((tag) => {
            if (!tag || seen.has(tag)) return false;
            seen.add(tag);
            return true;
        }).slice(0, 8);
    };

    document.querySelectorAll('[data-hnt-hashtag-input]').forEach((input) => {
        const preview = input.parentElement ? input.parentElement.querySelector('[data-hnt-hashtag-preview]') : null;
        if (!preview) return;
        const sync = () => {
            const tags = extract(input.value);
            preview.hidden = tags.length === 0;
            preview.innerHTML = tags.map((tag) => `<a href="/hashtags/${encodeURIComponent(tag)}">#${tag}</a>`).join('');
        };
        input.addEventListener('input', sync);
        sync();
    });
})();
</script>
@endpush
