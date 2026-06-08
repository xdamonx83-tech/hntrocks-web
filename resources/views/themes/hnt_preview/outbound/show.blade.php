@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.outbound_meta_title'))
@section('main_class', 'outbound-main')

@section('content')
    <div class="outbound-shell" id="js-outbound-page">
        <section class="outbound-hero-card">
            <div class="outbound-hero-glow" aria-hidden="true"></div>
            <div class="outbound-badge-icon" aria-hidden="true">
                <i class="ph ph-star" aria-hidden="true"></i>
            </div>

            <span class="outbound-kicker">{{ __('ui.outbound_kicker') }}</span>
            <h1>{{ __('ui.outbound_title') }}</h1>
            <p>{{ __('ui.outbound_intro') }}</p>

            <div class="outbound-target-card">
                <span>{{ __('ui.outbound_destination') }}</span>
                <strong>{{ $link->safeDomain() }}</strong>
                <small>{{ $link->title }}</small>
                @if($link->description)
                    <p>{{ $link->description }}</p>
                @endif
            </div>

            <div class="outbound-warning-card">
                <div class="outbound-warning-icon" aria-hidden="true">
                    <i class="ph ph-warning" aria-hidden="true"></i>
                </div>
                <div>
                    <strong>{{ __('ui.outbound_warning_title') }}</strong>
                    <p>{{ __('ui.outbound_warning_text') }}</p>
                </div>
            </div>

            <div class="outbound-actions">
                <a href="{{ route('feed.index') }}" class="outbound-action secondary">{{ __('ui.outbound_back') }}</a>
                <a href="{{ $link->continueUrl() }}" class="outbound-action primary" rel="nofollow noopener noreferrer">{{ __('ui.outbound_continue') }}</a>
            </div>
        </section>
    </div>
@endsection

@section('right_sidebar')
    <aside class="sidebar-right outbound-sidebar" aria-label="{{ __('ui.outbound_privacy_title') }}">
        <section class="widget outbound-side-widget">
            <div class="widget-header">
                <h2>{{ __('ui.outbound_privacy_title') }}</h2>
            </div>
            <p>{{ __('ui.outbound_privacy_text') }}</p>
            <ul>
                <li>{{ __('ui.outbound_privacy_point_1') }}</li>
                <li>{{ __('ui.outbound_privacy_point_2') }}</li>
                <li>{{ __('ui.outbound_privacy_point_3') }}</li>
            </ul>
        </section>
    </aside>
@endsection
