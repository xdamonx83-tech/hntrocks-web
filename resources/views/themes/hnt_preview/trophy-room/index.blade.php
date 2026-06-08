@extends('themes.hnt_preview.layouts.app')

@section('main_class', 'trophy-room-main')
@section('app_window_class', 'trophy-room-window')
@section('title', __('ui.trophy_room_page_title'))

@section('right_sidebar')
    <aside class="trophy-room-side" aria-label="{{ __('ui.trophy_room_panel_aria') }}">
        <div class="trophy-room-side-card" data-trophy-detail-panel>
            <span class="trophy-room-side-kicker">{{ __('ui.trophy_room_panel_kicker') }}</span>
            <h2 data-trophy-detail-title>{{ __('ui.trophy_room_panel_title') }}</h2>
            <p data-trophy-detail-description>{{ $usesRealTrophies ? __('ui.trophy_room_panel_text_real') : __('ui.trophy_room_panel_text') }}</p>
            <dl class="trophy-room-detail-list">
                <div>
                    <dt>{{ __('ui.trophy_room_panel_type') }}</dt>
                    <dd data-trophy-detail-subtitle>{{ __('ui.trophy_room_panel_type_empty') }}</dd>
                </div>
                <div>
                    <dt>{{ __('ui.trophy_room_panel_rarity') }}</dt>
                    <dd data-trophy-detail-rarity>{{ __('ui.trophy_room_panel_rarity_empty') }}</dd>
                </div>
                <div>
                    <dt>{{ __('ui.trophy_room_panel_meta') }}</dt>
                    <dd data-trophy-detail-meta>{{ $usesRealTrophies ? __('ui.trophy_room_panel_meta_empty_real') : __('ui.trophy_room_panel_meta_empty') }}</dd>
                </div>
            </dl>
        </div>

        <div class="trophy-room-side-card compact">
            <span class="trophy-room-side-kicker">{{ __('ui.trophy_room_controls_kicker') }}</span>
            <ul class="trophy-room-control-list">
                <li><i class="ph ph-mouse" aria-hidden="true"></i>{{ __('ui.trophy_room_control_pointer_lock') }}</li>
                <li><i class="ph ph-keyboard" aria-hidden="true"></i>{{ __('ui.trophy_room_control_wasd') }}</li>
                <li><i class="ph ph-person-simple-run" aria-hidden="true"></i>{{ __('ui.trophy_room_control_shift') }}</li>
                <li><i class="ph ph-crosshair" aria-hidden="true"></i>{{ __('ui.trophy_room_control_interact') }}</li>
                <li><i class="ph ph-arrow-u-up-left" aria-hidden="true"></i>{{ __('ui.trophy_room_control_escape') }}</li>
            </ul>
        </div>
    </aside>
@endsection

@push('head')
    <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/trophy-room.css') }}?v=769">
    <script type="importmap">
        {
            "imports": {
                "three": "https://cdn.jsdelivr.net/npm/three@0.164.1/build/three.module.js",
                "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.164.1/examples/jsm/"
            }
        }
    </script>
@endpush

@section('content')
<div
    class="trophy-room-shell"
    data-trophy-room
    data-trophies='@json($demoTrophies)'
    data-empty-title="{{ __('ui.trophy_room_panel_title') }}"
    data-empty-text="{{ __('ui.trophy_room_panel_text') }}"
    data-webgl-error="{{ __('ui.trophy_room_webgl_error') }}"
    data-ready-label="{{ $usesRealTrophies ? __('ui.trophy_room_ready_label_real') : __('ui.trophy_room_ready_label') }}"
    data-ready-empty="{{ __('ui.trophy_room_ready_empty') }}"
    data-locked-label="{{ __('ui.trophy_room_locked_label') }}"
    data-fullscreen-label="{{ __('ui.trophy_room_fullscreen') }}"
    data-fullscreen-exit-label="{{ __('ui.trophy_room_fullscreen_exit') }}"
    data-touch-label="{{ __('ui.trophy_room_touch_label') }}"
    data-loading-label="{{ __('ui.trophy_room_loading') }}"
    data-loading-progress-label="{{ __('ui.trophy_room_loading_progress') }}"
    data-loading-preparing-label="{{ __('ui.trophy_room_loading_preparing') }}"
    data-loading-ready-label="{{ __('ui.trophy_room_loading_ready') }}"
    data-model-barrel="{{ asset('assets/themes/hnt_preview/models/barrel.glb') }}"
    data-model-lantern="{{ asset('assets/themes/hnt_preview/models/oil-lantern.glb') }}"
    data-model-custom-room="{{ asset('assets/themes/hnt_preview/models/custom/trophy-room-v1/trophy-room.glb') }}"
>
    <section class="trophy-room-hero">
        <div class="trophy-room-hero-copy">
            <span class="trophy-room-kicker"><i class="ph ph-trophy" aria-hidden="true"></i>{{ __('ui.trophy_room_kicker') }}</span>
            <h1>{{ __('ui.trophy_room_title') }}</h1>
            <p>{{ $usesRealTrophies ? __('ui.trophy_room_intro_real') : __('ui.trophy_room_intro') }}</p>
        </div>

        <div class="trophy-room-hero-actions">
            @if(auth()->user()?->isAdmin())
                <a class="trophy-room-soft-link" href="{{ route('trophy-room.prop-lab') }}">
                    <i class="ph ph-cube-focus" aria-hidden="true"></i>
                    {{ __('ui.trophy_prop_lab_kicker') }}
                </a>
            @endif
            <a class="btn-create" href="{{ route('gamification.index') }}">
                <i class="ph ph-medal" aria-hidden="true"></i>
                {{ __('ui.trophy_room_badges_link') }}
            </a>
            <a class="trophy-room-soft-link" href="{{ route('hall-of-fame.index') }}">
                <i class="ph ph-crown-simple" aria-hidden="true"></i>
                {{ __('ui.trophy_room_hall_link') }}
            </a>
        </div>
    </section>

    <section class="trophy-room-stage-card" aria-label="{{ __('ui.trophy_room_scene_aria') }}">
        <div class="trophy-room-stage-top">
            <div>
                <span>{{ $usesRealTrophies ? __('ui.trophy_room_scene_kicker_real') : __('ui.trophy_room_scene_kicker') }}</span>
                <h2>{{ __('ui.trophy_room_scene_title') }}</h2>
            </div>
            <div class="trophy-room-stage-actions">
                <button class="trophy-room-stage-status" type="button" data-trophy-fullscreen>
                    <i class="ph ph-corners-out" aria-hidden="true"></i>
                    {{ __('ui.trophy_room_fullscreen') }}
                </button>
                <div class="trophy-room-stage-status" data-trophy-status>
                    <i class="ph ph-spinner-gap" aria-hidden="true"></i>
                    {{ __('ui.trophy_room_loading') }}
                </div>
            </div>
        </div>

        <div class="trophy-room-canvas-wrap">
            <canvas class="trophy-room-canvas" data-trophy-canvas></canvas>
            <div class="trophy-room-loading-screen" data-trophy-loading-screen>
                <div class="trophy-room-loading-mark" aria-hidden="true">
                    <i class="ph ph-cube-focus"></i>
                </div>
                <strong>{{ __('ui.trophy_room_loading_title') }}</strong>
                <span data-trophy-loading-text>{{ __('ui.trophy_room_loading_progress') }}</span>
                <div class="trophy-room-loading-track" aria-hidden="true">
                    <span data-trophy-loading-bar style="width: 0%"></span>
                </div>
                <em data-trophy-loading-progress>0%</em>
            </div>
            <div class="trophy-room-crosshair" aria-hidden="true"></div>
            <div class="trophy-room-ingame-card" data-trophy-ingame-card hidden>
                <span class="trophy-room-ingame-kicker">{{ __('ui.trophy_room_ingame_kicker') }}</span>
                <strong data-trophy-ingame-title>{{ __('ui.trophy_room_panel_title') }}</strong>
                <small data-trophy-ingame-meta>{{ __('ui.trophy_room_ingame_empty') }}</small>
                <em>{{ __('ui.trophy_room_ingame_action') }}</em>
            </div>
            <div class="trophy-room-enter-overlay" data-trophy-lock-prompt>
                <button class="btn-create" type="button" data-trophy-enter>
                    <i class="ph ph-mouse" aria-hidden="true"></i>
                    {{ __('ui.trophy_room_enter_button') }}
                </button>
                <span>{{ __('ui.trophy_room_enter_hint') }}</span>
            </div>
            <div class="trophy-room-fallback" data-trophy-fallback hidden>
                <i class="ph ph-warning-circle" aria-hidden="true"></i>
                <strong>{{ __('ui.trophy_room_fallback_title') }}</strong>
                <span>{{ __('ui.trophy_room_fallback_text') }}</span>
            </div>
            <button class="trophy-room-reset" type="button" data-trophy-reset>
                <i class="ph ph-arrows-clockwise" aria-hidden="true"></i>
                {{ __('ui.trophy_room_reset_camera') }}
            </button>
            <div class="trophy-room-touch-controls" data-trophy-touch-controls aria-hidden="true">
                <div class="trophy-room-touch-look" data-trophy-touch-look></div>
                <div class="trophy-room-touch-joystick" data-trophy-touch-joystick>
                    <span></span>
                </div>
                <button class="trophy-room-touch-action" type="button" data-trophy-touch-interact>
                    <i class="ph ph-hand-tap" aria-hidden="true"></i>
                    {{ __('ui.trophy_room_touch_interact') }}
                </button>
            </div>
        </div>

        <div class="trophy-room-mobile-hint">
            <i class="ph ph-hand-swipe-left" aria-hidden="true"></i>
            {{ __('ui.trophy_room_mobile_hint_first_person') }}
        </div>
    </section>

    <section class="trophy-room-inline-detail" data-trophy-detail-panel aria-label="{{ __('ui.trophy_room_panel_aria') }}">
        <span class="trophy-room-side-kicker">{{ __('ui.trophy_room_panel_kicker') }}</span>
        <h2 data-trophy-detail-title>{{ __('ui.trophy_room_panel_title') }}</h2>
        <p data-trophy-detail-description>{{ $usesRealTrophies ? __('ui.trophy_room_panel_text_real') : __('ui.trophy_room_panel_text') }}</p>
        <dl class="trophy-room-detail-list">
            <div>
                <dt>{{ __('ui.trophy_room_panel_type') }}</dt>
                <dd data-trophy-detail-subtitle>{{ __('ui.trophy_room_panel_type_empty') }}</dd>
            </div>
            <div>
                <dt>{{ __('ui.trophy_room_panel_rarity') }}</dt>
                <dd data-trophy-detail-rarity>{{ __('ui.trophy_room_panel_rarity_empty') }}</dd>
            </div>
            <div>
                <dt>{{ __('ui.trophy_room_panel_meta') }}</dt>
                <dd data-trophy-detail-meta>{{ $usesRealTrophies ? __('ui.trophy_room_panel_meta_empty_real') : __('ui.trophy_room_panel_meta_empty') }}</dd>
            </div>
        </dl>
    </section>
</div>
@endsection

@push('scripts')
    <script type="module" src="{{ asset('assets/themes/hnt_preview/trophy-room.js') }}?v=769"></script>
@endpush
