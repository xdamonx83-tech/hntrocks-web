@extends('themes.hnt_preview.layouts.app')

@php
    $viewer = auth()->user();
    $studioProState = $studioProFeatures ?? ['enabled' => false, 'balance' => 0, 'features' => []];
    $studioProData = $studioProState['features'] ?? [];
    $studioProOwned = fn (string $feature): bool => (bool) data_get($studioProData, $feature . '.owned', false);
    $studioProPrice = fn (string $feature): int => (int) data_get($studioProData, $feature . '.price', 0);
    $studioProUnlockUrl = fn (string $feature): string => (string) data_get($studioProData, $feature . '.unlock_url', '#');
    $studioProItemName = fn (string $feature): string => (string) data_get($studioProData, $feature . '.name', '');
    $studioProItemDescription = fn (string $feature, string $fallback): string => (string) (data_get($studioProData, $feature . '.description') ?: $fallback);
    $videoLimitMb = $limits['videoMb'] ?? round(config('hunthub.upload_limits.moment_video_kb', 204800) / 1024);
    $coverLimitMb = $limits['coverMb'] ?? round(config('hunthub.upload_limits.moment_cover_kb', 8192) / 1024);
    $studioLeftTools = [
        ['key' => 'media', 'label' => __('ui.moment_studio_media'), 'icon' => 'grid', 'active' => true, 'pro' => false],
        ['key' => 'text', 'label' => __('ui.moment_studio_text'), 'icon' => 'text', 'active' => false, 'pro' => false],
        ['key' => 'transitions', 'feature' => 'transitions', 'label' => __('ui.moment_studio_transitions'), 'icon' => 'transition', 'active' => false, 'pro' => true, 'owned' => $studioProOwned('transitions'), 'price' => $studioProPrice('transitions'), 'unlock_url' => $studioProUnlockUrl('transitions'), 'description' => $studioProItemDescription('transitions', __('ui.moment_studio_transition_help'))],
    ];
    $studioRightTools = [
        ['key' => 'fade', 'feature' => 'fade', 'label' => __('ui.moment_studio_fade'), 'icon' => 'fade', 'description' => $studioProItemDescription('fade', __('ui.moment_studio_fade_text')), 'owned' => $studioProOwned('fade'), 'price' => $studioProPrice('fade'), 'unlock_url' => $studioProUnlockUrl('fade'), 'action' => 'fade'],
        ['key' => 'filter', 'feature' => 'filter', 'label' => __('ui.moment_studio_filter'), 'icon' => 'filter', 'description' => $studioProItemDescription('filter', __('ui.moment_studio_filter_text')), 'owned' => $studioProOwned('filter'), 'price' => $studioProPrice('filter'), 'unlock_url' => $studioProUnlockUrl('filter'), 'action' => 'filter'],
        ['key' => 'effects', 'feature' => 'effects', 'label' => __('ui.moment_studio_effects'), 'icon' => 'sparkle', 'description' => $studioProItemDescription('effects', __('ui.moment_studio_effects_text')), 'owned' => $studioProOwned('effects'), 'price' => $studioProPrice('effects'), 'unlock_url' => $studioProUnlockUrl('effects'), 'action' => 'effects'],
        ['key' => 'colors', 'feature' => 'colors', 'label' => __('ui.moment_studio_colors'), 'icon' => 'palette', 'description' => $studioProItemDescription('colors', __('ui.moment_studio_colors_text')), 'owned' => $studioProOwned('colors'), 'price' => $studioProPrice('colors'), 'unlock_url' => $studioProUnlockUrl('colors'), 'action' => 'colors'],
    ];
    $studioFilters = [
        ['key' => 'none', 'label' => __('ui.moment_studio_filter_none')],
        ['key' => 'orange_teal', 'label' => __('ui.moment_studio_filter_orange_teal')],
        ['key' => 'bold_blue', 'label' => __('ui.moment_studio_filter_bold_blue')],
        ['key' => 'golden_hour', 'label' => __('ui.moment_studio_filter_golden_hour')],
        ['key' => 'vivid_vlogger', 'label' => __('ui.moment_studio_filter_vivid_vlogger')],
        ['key' => 'purple_undertone', 'label' => __('ui.moment_studio_filter_purple_undertone')],
        ['key' => 'winter_sunset_35', 'label' => __('ui.moment_studio_filter_winter_sunset_35')],
        ['key' => 'contrast', 'label' => __('ui.moment_studio_filter_contrast')],
        ['key' => 'autumn', 'label' => __('ui.moment_studio_filter_autumn')],
        ['key' => 'winter', 'label' => __('ui.moment_studio_filter_winter')],
        ['key' => 'old_western', 'label' => __('ui.moment_studio_filter_old_western')],
        ['key' => 'warm_coast', 'label' => __('ui.moment_studio_filter_warm_coast')],
        ['key' => 'cool_coast', 'label' => __('ui.moment_studio_filter_cool_coast')],
        ['key' => 'warm_landscape', 'label' => __('ui.moment_studio_filter_warm_landscape')],
        ['key' => 'cool_landscape', 'label' => __('ui.moment_studio_filter_cool_landscape')],
        ['key' => 'golden', 'label' => __('ui.moment_studio_filter_golden')],
        ['key' => 'dreamscape', 'label' => __('ui.moment_studio_filter_dreamscape')],
    ];
    $studioTransitions = [
        ['key' => 'none', 'label' => __('ui.moment_studio_transition_none'), 'description' => __('ui.moment_studio_transition_none_text')],
        ['key' => 'crossfade', 'label' => __('ui.moment_studio_transition_crossfade'), 'description' => __('ui.moment_studio_transition_crossfade_text')],
        ['key' => 'fadeblack', 'label' => __('ui.moment_studio_transition_fadeblack'), 'description' => __('ui.moment_studio_transition_fadeblack_text')],
        ['key' => 'fadewhite', 'label' => __('ui.moment_studio_transition_fadewhite'), 'description' => __('ui.moment_studio_transition_fadewhite_text')],
        ['key' => 'slideleft', 'label' => __('ui.moment_studio_transition_slideleft'), 'description' => __('ui.moment_studio_transition_slideleft_text')],
        ['key' => 'slideright', 'label' => __('ui.moment_studio_transition_slideright'), 'description' => __('ui.moment_studio_transition_slideright_text')],
        ['key' => 'smoothleft', 'label' => __('ui.moment_studio_transition_zoomcut'), 'description' => __('ui.moment_studio_transition_zoomcut_text')],
    ];

    $studioIconClass = static fn (string $icon): string => match ($icon) {
        'grid' => 'ph-squares-four',
        'text' => 'ph-text-t',
        'transition' => 'ph-arrows-left-right',
        'fade' => 'ph-circle-half',
        'filter' => 'ph-funnel',
        'sparkle' => 'ph-sparkle',
        'palette' => 'ph-palette',
        default => 'ph-circle',
    };

    $studioEffects = [
        ['key' => 'none', 'label' => __('ui.moment_studio_effect_none'), 'description' => __('ui.moment_studio_effect_none_text')],
        ['key' => 'flash', 'label' => __('ui.moment_studio_effect_flash'), 'description' => __('ui.moment_studio_effect_flash_text')],
        ['key' => 'impulse', 'label' => __('ui.moment_studio_effect_impulse'), 'description' => __('ui.moment_studio_effect_impulse_text')],
        ['key' => 'rotate', 'label' => __('ui.moment_studio_effect_rotate'), 'description' => __('ui.moment_studio_effect_rotate_text')],
        ['key' => 'vhs', 'label' => __('ui.moment_studio_effect_vhs'), 'description' => __('ui.moment_studio_effect_vhs_text')],
        ['key' => 'vaporwave', 'label' => __('ui.moment_studio_effect_vaporwave'), 'description' => __('ui.moment_studio_effect_vaporwave_text')],
        ['key' => 'chromatic', 'label' => __('ui.moment_studio_effect_chromatic'), 'description' => __('ui.moment_studio_effect_chromatic_text')],
        ['key' => 'fast_zoom', 'label' => __('ui.moment_studio_effect_fast_zoom'), 'description' => __('ui.moment_studio_effect_fast_zoom_text')],
        ['key' => 'slow_zoom', 'label' => __('ui.moment_studio_effect_slow_zoom'), 'description' => __('ui.moment_studio_effect_slow_zoom_text')],
        ['key' => 'random_zoom', 'label' => __('ui.moment_studio_effect_random_zoom'), 'description' => __('ui.moment_studio_effect_random_zoom_text')],
        ['key' => 'blur', 'label' => __('ui.moment_studio_effect_blur'), 'description' => __('ui.moment_studio_effect_blur_text')],
        ['key' => 'filmic', 'label' => __('ui.moment_studio_effect_filmic'), 'description' => __('ui.moment_studio_effect_filmic_text')],
        ['key' => 'glitch', 'label' => __('ui.moment_studio_effect_glitch'), 'description' => __('ui.moment_studio_effect_glitch_text')],
        ['key' => 'disco', 'label' => __('ui.moment_studio_effect_disco'), 'description' => __('ui.moment_studio_effect_disco_text')],
        ['key' => 'comic', 'label' => __('ui.moment_studio_effect_comic'), 'description' => __('ui.moment_studio_effect_comic_text')],
        ['key' => 'retro', 'label' => __('ui.moment_studio_effect_retro'), 'description' => __('ui.moment_studio_effect_retro_text')],
        ['key' => 'smoke', 'label' => __('ui.moment_studio_effect_smoke'), 'description' => __('ui.moment_studio_effect_smoke_text')],
        ['key' => 'shine', 'label' => __('ui.moment_studio_effect_shine'), 'description' => __('ui.moment_studio_effect_shine_text')],
        ['key' => 'spread', 'label' => __('ui.moment_studio_effect_spread'), 'description' => __('ui.moment_studio_effect_spread_text')],
    ];
@endphp

@section('title', __('ui.moment_studio_title') . ' · hnt.rocks')
@section('app_window_class', 'hnt-studio-window')
@section('main_class', 'hnt-studio-main')
@section('right_sidebar')
    <aside class="hnt-studio-right-placeholder" hidden></aside>
@endsection

@section('content')
<form class="hnt-studio" method="post" action="{{ route('moments.store') }}" enctype="multipart/form-data" data-hnt-moment-studio data-max-duration="60" data-max-media="5" data-label-ready="{{ __('ui.moment_studio_ready') }}" data-label-over-duration="{{ __('ui.moment_studio_over_duration') }}" data-label-loading="{{ __('ui.moment_studio_loading_video') }}" data-label-pick-first="{{ __('ui.moment_studio_pick_video_first') }}" data-label-uploading="{{ __('ui.upload_running') }}" data-label-media-limit="{{ __('ui.moment_studio_media_limit_reached') }}" data-label-multi-publish="{{ __('ui.moment_studio_multi_publish_pending') }}" data-label-active-publish="{{ __('ui.moment_studio_active_clip_publish') }}" data-label-reordered="{{ __('ui.moment_studio_reordered') }}" data-label-trimmed="{{ __('ui.moment_studio_trimmed') }}" data-label-render="{{ __('ui.moment_studio_render_started') }}" data-label-text-added="{{ __('ui.moment_studio_text_added') }}" data-label-text-empty="{{ __('ui.moment_studio_text_empty') }}" data-label-text-removed="{{ __('ui.moment_studio_text_removed') }}" data-label-text-positioned="{{ __('ui.moment_studio_text_positioned') }}" data-label-text-timed="{{ __('ui.moment_studio_text_timed') }}" data-label-pro-locked="{{ __('ui.moment_studio_pro_locked_status') }}" data-label-fade-updated="{{ __('ui.moment_studio_fade_updated') }}" data-label-fade-pick-first="{{ __('ui.moment_studio_fade_pick_first') }}" data-label-filter-updated="{{ __('ui.moment_studio_filter_updated') }}" data-label-filter-pick-first="{{ __('ui.moment_studio_filter_pick_first') }}" data-label-effect-updated="{{ __('ui.moment_studio_effect_updated') }}" data-label-effect-pick-first="{{ __('ui.moment_studio_effect_pick_first') }}" data-label-colors-updated="{{ __('ui.moment_studio_colors_updated') }}" data-label-colors-pick-first="{{ __('ui.moment_studio_colors_pick_first') }}" data-label-transition-updated="{{ __('ui.moment_studio_transition_updated') }}" data-label-transition-pick-first="{{ __('ui.moment_studio_transition_pick_first') }}" data-label-transition-need-next="{{ __('ui.moment_studio_transition_need_next') }}" data-feed-url="{{ route('moments.index', ['upload_shell' => 1]) }}" data-label-background-upload-title="{{ __('ui.moment_studio_background_upload_title') }}" data-label-background-upload-processing="{{ __('ui.moment_studio_background_upload_processing') }}" data-label-background-upload-error="{{ __('ui.moment_studio_background_upload_error') }}" data-label-background-upload-back="{{ __('ui.moment_studio_background_upload_back') }}" data-label-background-upload-open-feed="{{ __('ui.moment_studio_background_upload_open_feed') }}" data-crowns-balance="{{ (int) ($studioProState['balance'] ?? 0) }}" data-label-pro-buy="{{ __('ui.moment_studio_crowns_unlock_button') }}" data-label-pro-owned="{{ __('ui.moment_studio_unlocked') }}" data-label-pro-unlock-success="{{ __('ui.moment_studio_crowns_unlock_success_generic') }}">
    @csrf
    <input id="hntStudioVideoInput" class="hnt-studio-native-file" name="video" type="file" accept="video/mp4,video/webm,video/quicktime" required multiple data-hnt-studio-video-input>
    <input class="hnt-studio-native-file" name="studio_videos[]" type="file" accept="video/mp4,video/webm,video/quicktime" multiple data-hnt-studio-batch-input tabindex="-1" aria-hidden="true">
    <input type="hidden" name="studio_payload" value="" data-hnt-studio-payload>
    <input type="hidden" name="visibility" value="public">
    <input type="hidden" name="trim_start_seconds" value="">
    <input type="hidden" name="trim_end_seconds" value="">

    <header class="hnt-studio-topbar">
        <div class="hnt-studio-project-meta">
            <a class="hnt-studio-back" href="{{ route('moments.index') }}" aria-label="{{ __('ui.back') }}">
                <i class="ph ph-caret-left" aria-hidden="true"></i>
            </a>
            <div>
                <span>{{ __('ui.moment_studio_kicker') }}</span>
                <strong>{{ __('ui.moment_studio_title') }}</strong>
            </div>
        </div>

        <div class="hnt-studio-top-actions">
            <div class="hnt-studio-save-state" data-hnt-studio-status>{{ __('ui.moment_studio_select_first') }}</div>
            <button class="hnt-studio-publish" type="button" data-hnt-studio-publish disabled>
                {{ __('ui.moment_publish') }}
            </button>
        </div>
    </header>

    @if ($errors->any())
        <div class="hnt-studio-errors" role="alert">
            <strong>{{ __('ui.form_check_inputs') }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="hnt-studio-shell">
        <aside class="hnt-studio-left">
            <nav class="hnt-studio-toolrail" aria-label="{{ __('ui.moment_studio_tools') }}">
                @foreach ($studioLeftTools as $tool)
                    <button class="hnt-studio-tool {{ $tool['active'] ? 'is-active' : '' }} {{ !empty($tool['pro']) && empty($tool['owned']) ? 'is-locked' : '' }}" type="button" aria-label="{{ $tool['label'] }}" data-hnt-studio-tool="{{ $tool['key'] }}" @if(!empty($tool['pro'])) data-pro-feature="{{ $tool['feature'] ?? $tool['key'] }}" data-pro-title="{{ $tool['label'] }}" data-pro-description="{{ $tool['description'] ?? __('ui.moment_studio_pro_default_text') }}" data-pro-price="{{ (int) ($tool['price'] ?? 0) }}" data-pro-unlock-url="{{ $tool['unlock_url'] ?? '#' }}" @if(empty($tool['owned'])) data-hnt-studio-pro-lock @endif @endif>
                        <span class="hnt-studio-tool-icon has-phosphor" data-icon="{{ $tool['icon'] }}"><i class="ph {{ $studioIconClass($tool['icon']) }}" aria-hidden="true"></i></span>
                        <small>{{ $tool['label'] }}</small>
                        @if (!empty($tool['pro']))
                            <em>{{ !empty($tool['owned']) ? __('ui.moment_studio_unlocked') : __('ui.moment_studio_pro') }}</em>
                        @endif
                    </button>
                @endforeach
            </nav>

            <section class="hnt-studio-media-panel" data-hnt-studio-panel="media" aria-label="{{ __('ui.moment_studio_media') }}">
                <div class="hnt-studio-panel-head">
                    <h2>{{ __('ui.moment_studio_media') }}</h2>
                    <span>{{ __('ui.moment_studio_max_videos') }}</span>
                </div>

                <label class="hnt-studio-import" for="hntStudioVideoInput">
                    <span>{{ __('ui.moment_studio_import_media') }}</span>
                    <i class="ph ph-plus" aria-hidden="true"></i>
                </label>

                <div class="hnt-studio-media-list" data-hnt-studio-media-list></div>
            </section>

            <section class="hnt-studio-text-panel" data-hnt-studio-panel="text" hidden aria-label="{{ __('ui.moment_studio_text') }}">
                <div class="hnt-studio-panel-head">
                    <h2>{{ __('ui.moment_studio_text') }}</h2>
                    <span>{{ __('ui.moment_studio_free_feature') }}</span>
                </div>

                <label class="hnt-studio-text-field">
                    <span>{{ __('ui.moment_studio_text_label') }}</span>
                    <textarea rows="4" maxlength="90" placeholder="{{ __('ui.moment_studio_text_input_placeholder') }}" data-hnt-studio-text-input></textarea>
                </label>

                <div class="hnt-studio-text-actions">
                    <button type="button" class="hnt-studio-text-add" data-hnt-studio-text-add>{{ __('ui.moment_studio_text_add') }}</button>
                    <button type="button" class="hnt-studio-text-clear" data-hnt-studio-text-clear disabled>{{ __('ui.moment_studio_text_clear') }}</button>
                </div>

                <p class="hnt-studio-import-note">{{ __('ui.moment_studio_text_help') }}</p>
            </section>

            <section class="hnt-studio-transitions-panel" data-hnt-studio-panel="transitions" hidden aria-label="{{ __('ui.moment_studio_transitions') }}">
                <div class="hnt-studio-panel-head">
                    <h2>{{ __('ui.moment_studio_transitions') }}</h2>
                    <span>{{ __('ui.moment_studio_pro') }}</span>
                </div>

                <div class="hnt-studio-transition-active" data-hnt-studio-transition-active>{{ __('ui.moment_studio_transition_pick_first') }}</div>

                <div class="hnt-studio-transition-grid">
                    @foreach ($studioTransitions as $transition)
                        <button class="hnt-studio-transition-card {{ $transition['key'] === 'none' ? 'is-active' : '' }}" type="button" data-hnt-studio-transition-choice="{{ $transition['key'] }}" data-transition-name="{{ mb_strtolower($transition['label']) }}">
                            <span class="hnt-studio-transition-preview" data-transition="{{ $transition['key'] }}"></span>
                            <strong>{{ $transition['label'] }}</strong>
                            <em>{{ $transition['description'] }}</em>
                        </button>
                    @endforeach
                </div>

                <p class="hnt-studio-import-note">{{ __('ui.moment_studio_transition_help') }}</p>
            </section>
        </aside>

        <main class="hnt-studio-center">
            <div class="hnt-studio-canvas-toolbar" aria-label="{{ __('ui.moment_studio_preview_controls') }}">
                <button type="button" disabled aria-label="{{ __('ui.moment_studio_undo') }}">
                    <i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i>
                </button>
                <button type="button" disabled aria-label="{{ __('ui.moment_studio_redo') }}">
                    <i class="ph ph-arrow-clockwise" aria-hidden="true"></i>
                </button>
            </div>

            <section class="hnt-studio-preview" aria-label="{{ __('ui.moment_studio_preview_label') }}">
                <div class="hnt-studio-phone-frame">
                    <video class="hnt-studio-video-preview" muted playsinline data-hnt-studio-preview></video>
                    <div class="hnt-studio-text-overlay" data-hnt-studio-text-overlay hidden></div>
                    <div class="hnt-studio-preview-empty" data-hnt-studio-preview-empty>
                        <span>{{ __('ui.moment_studio_preview_label') }}</span>
                        <strong>{{ __('ui.moment_studio_preview_empty_title') }}</strong>
                        <p>{{ __('ui.moment_studio_preview_empty_text') }}</p>
                    </div>
                    <div class="hnt-studio-preview-safe" aria-hidden="true"></div>
                </div>
            </section>

            <div class="hnt-studio-playbar">
                <button class="hnt-studio-play" type="button" data-hnt-studio-play disabled aria-label="{{ __('ui.moment_play_video') }}">
                    <i class="ph ph-play" aria-hidden="true"></i>
                </button>
                <span data-hnt-studio-current-time>0:00.00</span>
                <span>/</span>
                <span data-hnt-studio-duration>0:00.00</span>
            </div>
        </main>

        <aside class="hnt-studio-right">
            <section class="hnt-studio-pro-panel" aria-label="{{ __('ui.moment_studio_pro_features') }}">
                @foreach ($studioRightTools as $tool)
                    @php($owned = !empty($tool['owned']))
                    @if ($owned && ($tool['action'] ?? '') === 'fade')
                        <button class="hnt-studio-side-feature is-enabled" type="button" data-hnt-studio-fade-open data-pro-feature="{{ $tool['feature'] }}" data-pro-key="{{ $tool['key'] }}" data-pro-title="{{ $tool['label'] }}" data-pro-description="{{ $tool['description'] }}" data-pro-price="{{ (int) ($tool['price'] ?? 0) }}" data-pro-unlock-url="{{ $tool['unlock_url'] ?? '#' }}">
                            <span class="hnt-studio-tool-icon has-phosphor" data-icon="{{ $tool['icon'] }}"><i class="ph {{ $studioIconClass($tool['icon']) }}" aria-hidden="true"></i></span>
                            <strong>{{ $tool['label'] }}</strong>
                            <em>{{ __('ui.moment_studio_unlocked') }}</em>
                        </button>
                    @elseif ($owned && ($tool['action'] ?? '') === 'filter')
                        <button class="hnt-studio-side-feature is-enabled" type="button" data-hnt-studio-filter-open data-pro-feature="{{ $tool['feature'] }}" data-pro-key="{{ $tool['key'] }}" data-pro-title="{{ $tool['label'] }}" data-pro-description="{{ $tool['description'] }}" data-pro-price="{{ (int) ($tool['price'] ?? 0) }}" data-pro-unlock-url="{{ $tool['unlock_url'] ?? '#' }}">
                            <span class="hnt-studio-tool-icon has-phosphor" data-icon="{{ $tool['icon'] }}"><i class="ph {{ $studioIconClass($tool['icon']) }}" aria-hidden="true"></i></span>
                            <strong>{{ $tool['label'] }}</strong>
                            <em>{{ __('ui.moment_studio_unlocked') }}</em>
                        </button>
                    @elseif ($owned && ($tool['action'] ?? '') === 'effects')
                        <button class="hnt-studio-side-feature is-enabled" type="button" data-hnt-studio-effect-open data-pro-feature="{{ $tool['feature'] }}" data-pro-key="{{ $tool['key'] }}" data-pro-title="{{ $tool['label'] }}" data-pro-description="{{ $tool['description'] }}" data-pro-price="{{ (int) ($tool['price'] ?? 0) }}" data-pro-unlock-url="{{ $tool['unlock_url'] ?? '#' }}">
                            <span class="hnt-studio-tool-icon has-phosphor" data-icon="{{ $tool['icon'] }}"><i class="ph {{ $studioIconClass($tool['icon']) }}" aria-hidden="true"></i></span>
                            <strong>{{ $tool['label'] }}</strong>
                            <em>{{ __('ui.moment_studio_unlocked') }}</em>
                        </button>
                    @elseif ($owned && ($tool['action'] ?? '') === 'colors')
                        <button class="hnt-studio-side-feature is-enabled" type="button" data-hnt-studio-colors-open data-pro-feature="{{ $tool['feature'] }}" data-pro-key="{{ $tool['key'] }}" data-pro-title="{{ $tool['label'] }}" data-pro-description="{{ $tool['description'] }}" data-pro-price="{{ (int) ($tool['price'] ?? 0) }}" data-pro-unlock-url="{{ $tool['unlock_url'] ?? '#' }}">
                            <span class="hnt-studio-tool-icon has-phosphor" data-icon="{{ $tool['icon'] }}"><i class="ph {{ $studioIconClass($tool['icon']) }}" aria-hidden="true"></i></span>
                            <strong>{{ $tool['label'] }}</strong>
                            <em>{{ __('ui.moment_studio_unlocked') }}</em>
                        </button>
                    @else
                        <button class="hnt-studio-side-feature is-locked" type="button" data-hnt-studio-pro-lock data-pro-feature="{{ $tool['feature'] ?? $tool['key'] }}" data-pro-key="{{ $tool['key'] }}" data-pro-title="{{ $tool['label'] }}" data-pro-description="{{ $tool['description'] }}" data-pro-price="{{ (int) ($tool['price'] ?? 0) }}" data-pro-unlock-url="{{ $tool['unlock_url'] ?? '#' }}">
                            <span class="hnt-studio-tool-icon has-phosphor" data-icon="{{ $tool['icon'] }}"><i class="ph {{ $studioIconClass($tool['icon']) }}" aria-hidden="true"></i></span>
                            <strong>{{ $tool['label'] }}</strong>
                            <em>{{ __('ui.moment_studio_pro') }}</em>
                        </button>
                    @endif
                @endforeach
            </section>

        </aside>
    </div>


    <button class="hnt-studio-publish-backdrop" type="button" hidden data-hnt-studio-publish-close aria-label="{{ __('ui.close') }}"></button>

    <aside class="hnt-studio-publish-drawer" hidden data-hnt-studio-publish-drawer aria-hidden="true" aria-label="{{ __('ui.moment_studio_publish_drawer_title') }}">
        <div class="hnt-studio-publish-drawer-head">
            <div>
                <span>{{ __('ui.moment_studio_publish_drawer_kicker') }}</span>
                <h2>{{ __('ui.moment_studio_publish_drawer_title') }}</h2>
                <p>{{ __('ui.moment_studio_publish_drawer_text') }}</p>
            </div>
            <button class="hnt-studio-drawer-close" type="button" data-hnt-studio-publish-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>

        <div class="hnt-studio-publish-summary">
            <strong>{{ __('ui.moment_studio_publish_summary') }}</strong>
            <span data-hnt-studio-publish-summary>0:00 / 1:00</span>
        </div>

        <label class="hnt-studio-publish-field">
            <span>{{ __('ui.caption') }}</span>
            <input type="text" name="caption" maxlength="220" placeholder="{{ __('ui.moment_caption_placeholder') }}">
        </label>

        <label class="hnt-studio-publish-field">
            <span>{{ __('ui.description') }}</span>
            <textarea name="description" rows="5" maxlength="2000" placeholder="{{ __('ui.moment_description_placeholder') }}" data-hnt-hashtag-input></textarea>
            <div class="hh-moment-hashtag-preview" hidden data-hnt-hashtag-preview></div>
        </label>

        <p class="hnt-studio-publish-help">{{ __('ui.moment_studio_publish_help') }}</p>

        <div class="hnt-studio-publish-drawer-actions">
            <button class="hnt-studio-drawer-secondary" type="button" data-hnt-studio-publish-close>{{ __('ui.moment_studio_publish_back') }}</button>
            <button class="hnt-studio-drawer-primary" type="submit" data-hnt-studio-publish-final>{{ __('ui.moment_studio_publish_confirm') }}</button>
        </div>
    </aside>



    <button class="hnt-studio-fade-backdrop" type="button" hidden data-hnt-studio-fade-close aria-label="{{ __('ui.close') }}"></button>

    <aside class="hnt-studio-fade-drawer" hidden data-hnt-studio-fade-drawer aria-hidden="true" aria-label="{{ __('ui.moment_studio_fade') }}">
        <div class="hnt-studio-fade-drawer-head">
            <div>
                <span>{{ __('ui.moment_studio_pro') }}</span>
                <h2>{{ __('ui.moment_studio_fade') }}</h2>
                <p>{{ __('ui.moment_studio_fade_drawer_text') }}</p>
            </div>
            <button class="hnt-studio-drawer-close" type="button" data-hnt-studio-fade-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>

        <div class="hnt-studio-fade-active" data-hnt-studio-fade-active>{{ __('ui.moment_studio_fade_pick_first') }}</div>

        <label class="hnt-studio-fade-toggle">
            <input type="checkbox" data-hnt-studio-fade-in>
            <span>
                <strong>{{ __('ui.moment_studio_fade_in') }}</strong>
                <em>{{ __('ui.moment_studio_fade_in_text') }}</em>
            </span>
        </label>

        <label class="hnt-studio-fade-toggle">
            <input type="checkbox" data-hnt-studio-fade-out>
            <span>
                <strong>{{ __('ui.moment_studio_fade_out') }}</strong>
                <em>{{ __('ui.moment_studio_fade_out_text') }}</em>
            </span>
        </label>

        <p class="hnt-studio-fade-help">{{ __('ui.moment_studio_fade_help') }}</p>

        <div class="hnt-studio-pro-actions">
            <button class="hnt-studio-drawer-secondary" type="button" data-hnt-studio-fade-close>{{ __('ui.close') }}</button>
        </div>
    </aside>

    <button class="hnt-studio-filter-backdrop" type="button" hidden data-hnt-studio-filter-close aria-label="{{ __('ui.close') }}"></button>

    <aside class="hnt-studio-filter-drawer" hidden data-hnt-studio-filter-drawer aria-hidden="true" aria-label="{{ __('ui.moment_studio_filter') }}">
        <div class="hnt-studio-filter-drawer-head">
            <div>
                <span>{{ __('ui.moment_studio_pro') }}</span>
                <h2>{{ __('ui.moment_studio_filter') }}</h2>
                <p>{{ __('ui.moment_studio_filter_drawer_text') }}</p>
            </div>
            <button class="hnt-studio-drawer-close" type="button" data-hnt-studio-filter-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>

        <div class="hnt-studio-filter-active" data-hnt-studio-filter-active>{{ __('ui.moment_studio_filter_pick_first') }}</div>

        <label class="hnt-studio-filter-search">
            <span class="sr-only">{{ __('ui.search') }}</span>
            <input type="search" placeholder="{{ __('ui.moment_studio_filter_search_placeholder') }}" data-hnt-studio-filter-search>
        </label>

        <div class="hnt-studio-filter-grid" data-hnt-studio-filter-grid>
            @foreach ($studioFilters as $filter)
                <button class="hnt-studio-filter-card {{ $filter['key'] === 'none' ? 'is-active' : '' }}" type="button" data-hnt-studio-filter-choice="{{ $filter['key'] }}" data-filter-name="{{ \Illuminate\Support\Str::lower($filter['label']) }}">
                    <span class="hnt-studio-filter-preview" data-filter="{{ $filter['key'] }}">
                        <video muted playsinline preload="metadata" data-hnt-studio-filter-preview></video>
                    </span>
                    <strong>{{ $filter['label'] }}</strong>
                </button>
            @endforeach
        </div>

        <p class="hnt-studio-filter-help">{{ __('ui.moment_studio_filter_help') }}</p>

        <div class="hnt-studio-pro-actions">
            <button class="hnt-studio-drawer-secondary" type="button" data-hnt-studio-filter-close>{{ __('ui.close') }}</button>
        </div>
    </aside>




    <button class="hnt-studio-effect-backdrop" type="button" hidden data-hnt-studio-effect-close aria-label="{{ __('ui.close') }}"></button>

    <aside class="hnt-studio-effect-drawer" hidden data-hnt-studio-effect-drawer aria-hidden="true" aria-label="{{ __('ui.moment_studio_effects') }}">
        <div class="hnt-studio-effect-drawer-head">
            <div>
                <span>{{ __('ui.moment_studio_pro') }}</span>
                <h2>{{ __('ui.moment_studio_effects') }}</h2>
                <p>{{ __('ui.moment_studio_effect_drawer_text') }}</p>
            </div>
            <button class="hnt-studio-drawer-close" type="button" data-hnt-studio-effect-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>

        <div class="hnt-studio-effect-active" data-hnt-studio-effect-active>{{ __('ui.moment_studio_effect_pick_first') }}</div>

        <label class="hnt-studio-effect-search">
            <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
            <input type="search" placeholder="{{ __('ui.moment_studio_effect_search') }}" data-hnt-studio-effect-search>
        </label>

        <div class="hnt-studio-effect-grid">
            @foreach ($studioEffects as $effect)
                <button class="hnt-studio-effect-card {{ $effect['key'] === 'none' ? 'is-active' : '' }}" type="button" data-hnt-studio-effect-choice="{{ $effect['key'] }}" data-effect="{{ $effect['key'] }}" data-effect-name="{{ mb_strtolower($effect['label']) }}">
                    <span class="hnt-studio-effect-preview" data-effect="{{ $effect['key'] }}"></span>
                    <strong>{{ $effect['label'] }}</strong>
                    <em>{{ $effect['description'] }}</em>
                </button>
            @endforeach
        </div>

        <p class="hnt-studio-effect-help">{{ __('ui.moment_studio_effect_help') }}</p>

        <div class="hnt-studio-pro-actions">
            <button class="hnt-studio-drawer-secondary" type="button" data-hnt-studio-effect-close>{{ __('ui.close') }}</button>
        </div>
    </aside>

    <button class="hnt-studio-colors-backdrop" type="button" hidden data-hnt-studio-colors-close aria-label="{{ __('ui.close') }}"></button>

    <aside class="hnt-studio-colors-drawer" hidden data-hnt-studio-colors-drawer aria-hidden="true" aria-label="{{ __('ui.moment_studio_colors') }}">
        <div class="hnt-studio-colors-drawer-head">
            <div>
                <span>{{ __('ui.moment_studio_pro') }}</span>
                <h2>{{ __('ui.moment_studio_colors') }}</h2>
                <p>{{ __('ui.moment_studio_colors_drawer_text') }}</p>
            </div>
            <button class="hnt-studio-drawer-close" type="button" data-hnt-studio-colors-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>

        <div class="hnt-studio-colors-active" data-hnt-studio-colors-active>{{ __('ui.moment_studio_colors_pick_first') }}</div>

        <div class="hnt-studio-color-controls">
            <label class="hnt-studio-color-control">
                <span>{{ __('ui.moment_studio_color_exposure') }}</span>
                <input type="range" min="-50" max="50" step="1" value="0" data-hnt-studio-color-control="exposure">
            </label>
            <label class="hnt-studio-color-control">
                <span>{{ __('ui.moment_studio_color_contrast') }}</span>
                <input type="range" min="-50" max="50" step="1" value="0" data-hnt-studio-color-control="contrast">
            </label>
            <label class="hnt-studio-color-control">
                <span>{{ __('ui.moment_studio_color_saturation') }}</span>
                <input type="range" min="-50" max="50" step="1" value="0" data-hnt-studio-color-control="saturation">
            </label>
            <label class="hnt-studio-color-control is-temperature">
                <span>{{ __('ui.moment_studio_color_temperature') }}</span>
                <input type="range" min="-50" max="50" step="1" value="0" data-hnt-studio-color-control="temperature">
            </label>
            <label class="hnt-studio-color-control">
                <span>{{ __('ui.moment_studio_color_transparency') }}</span>
                <input type="range" min="0" max="70" step="1" value="0" data-hnt-studio-color-control="transparency">
            </label>
        </div>

        <p class="hnt-studio-colors-help">{{ __('ui.moment_studio_colors_help') }}</p>

        <div class="hnt-studio-pro-actions">
            <button class="hnt-studio-drawer-secondary" type="button" data-hnt-studio-colors-reset>{{ __('ui.reset') }}</button>
            <button class="hnt-studio-drawer-secondary" type="button" data-hnt-studio-colors-close>{{ __('ui.close') }}</button>
        </div>
    </aside>

    <button class="hnt-studio-pro-backdrop" type="button" hidden data-hnt-studio-pro-close aria-label="{{ __('ui.close') }}"></button>

    <aside class="hnt-studio-pro-drawer" hidden data-hnt-studio-pro-drawer aria-hidden="true" aria-label="{{ __('ui.moment_studio_pro_drawer_title') }}">
        <div class="hnt-studio-pro-drawer-head">
            <div>
                <span>{{ __('ui.moment_studio_pro') }}</span>
                <h2 data-hnt-studio-pro-title>{{ __('ui.moment_studio_pro_drawer_title') }}</h2>
                <p data-hnt-studio-pro-description>{{ __('ui.moment_studio_pro_default_text') }}</p>
            </div>
            <button class="hnt-studio-drawer-close" type="button" data-hnt-studio-pro-close aria-label="{{ __('ui.close') }}">
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </div>

        <div class="hnt-studio-pro-note">
            <strong>{{ __('ui.moment_studio_pro_crowns_title') }}</strong>
            <span>{{ __('ui.moment_studio_pro_crowns_text') }}</span>
        </div>

        <div class="hnt-studio-pro-purchase">
            <div>
                <span>{{ __('ui.moment_studio_crowns_price') }}</span>
                <strong><b data-hnt-studio-pro-price>0</b> {{ __('ui.crowns_label') }}</strong>
            </div>
            <div>
                <span>{{ __('ui.moment_studio_crowns_balance') }}</span>
                <strong><b data-hnt-studio-pro-balance>{{ (int) ($studioProState['balance'] ?? 0) }}</b> {{ __('ui.crowns_label') }}</strong>
            </div>
        </div>

        <div class="hnt-studio-pro-actions">
            <button class="hnt-studio-drawer-secondary" type="button" data-hnt-studio-pro-close>{{ __('ui.close') }}</button>
            <button class="hnt-studio-drawer-primary" type="button" data-hnt-studio-pro-buy>{{ __('ui.moment_studio_crowns_unlock_button') }}</button>
        </div>
    </aside>


    <section class="hnt-studio-upload-shell" hidden data-hnt-studio-upload-shell aria-live="polite" aria-label="{{ __('ui.moment_studio_background_upload_title') }}">
        <iframe class="hnt-studio-upload-feed" title="{{ __('ui.moment_studio_background_upload_open_feed') }}" data-hnt-studio-upload-feed loading="lazy"></iframe>
        <div class="hnt-studio-upload-card" data-hnt-studio-upload-card>
            <div class="hnt-studio-upload-preview">
                <video muted playsinline preload="metadata" data-hnt-studio-upload-preview></video>
                <span data-hnt-studio-upload-preview-fallback>{{ __('ui.moment_studio_kicker') }}</span>
            </div>
            <div class="hnt-studio-upload-copy">
                <span>{{ __('ui.moment_studio_kicker') }}</span>
                <strong data-hnt-studio-upload-title>{{ __('ui.moment_studio_background_upload_title') }}</strong>
                <em data-hnt-studio-upload-text>{{ __('ui.moment_studio_background_upload_open_feed') }}</em>
                <div class="hnt-studio-upload-progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" data-hnt-studio-upload-progress>
                    <i data-hnt-studio-upload-progress-bar></i>
                </div>
            </div>
            <button class="hnt-studio-upload-back" type="button" data-hnt-studio-upload-back hidden>{{ __('ui.moment_studio_background_upload_back') }}</button>
        </div>
    </section>


    <section class="hnt-studio-timeline" aria-label="{{ __('ui.moment_studio_timeline') }}">
        <div class="hnt-studio-timeline-head">
            <div>
                <strong>{{ __('ui.moment_studio_timeline') }}</strong>
                <span>{{ __('ui.moment_studio_max_duration') }}</span>
            </div>
            <div class="hnt-studio-duration-pill">
                <span data-hnt-studio-total>0:00</span>
                <em>/ 1:00</em>
            </div>
        </div>

        <div class="hnt-studio-time-ruler" aria-hidden="true">
            <span>0:00</span><span>0:10</span><span>0:20</span><span>0:30</span><span>0:40</span><span>0:50</span><span>1:00</span>
        </div>

        <div class="hnt-studio-track hnt-studio-text-track">
            <span>{{ __('ui.moment_studio_text_track') }}</span>
            <div class="hnt-studio-text-lane" data-hnt-studio-text-track>
                <div class="hnt-studio-text-empty" data-hnt-studio-text-empty>{{ __('ui.moment_studio_text_placeholder') }}</div>
            </div>
        </div>

        <div class="hnt-studio-track hnt-studio-video-track">
            <span>{{ __('ui.moment_studio_video_track') }}</span>
            <div class="hnt-studio-timeline-lane" data-hnt-studio-timeline-track>
                <div class="hnt-studio-timeline-empty" data-hnt-studio-timeline-empty>{{ __('ui.moment_studio_timeline_empty') }}</div>
            </div>
        </div>
    </section>
</form>
@endsection

@push('scripts')
<script src="{{ asset('assets/themes/hnt_preview/moment-studio.js') }}?v=725" defer></script>
@endpush
