@extends('themes.socialite.layouts.app')

@section('title', __('ui.outbound_meta_title'))
@section('robots', 'noindex,follow')
@section('meta_description', __('ui.outbound_meta_description'))

@section('content')
<div class="flex max-lg:flex-col 2xl:gap-12 gap-10 2xl:max-w-[1100px] max-w-[980px] mx-auto" id="js-outbound-page">
    <div class="flex-1 min-w-0">
        <section class="hnt-outbound-card box overflow-hidden">
            <div class="hnt-outbound-glow"></div>
            <div class="hnt-outbound-content">
                <span class="hnt-outbound-kicker">{{ __('ui.outbound_kicker') }}</span>
                <h1>{{ __('ui.outbound_title') }}</h1>
                <p>{{ __('ui.outbound_intro') }}</p>

                <div class="hnt-outbound-target">
                    <span>{{ __('ui.outbound_destination') }}</span>
                    <strong>{{ $link->safeDomain() }}</strong>
                    <small>{{ $link->title }}</small>
                    @if($link->description)
                        <p>{{ $link->description }}</p>
                    @endif
                </div>

                <div class="hnt-outbound-warning">
                    <strong>{{ __('ui.outbound_warning_title') }}</strong>
                    <p>{{ __('ui.outbound_warning_text') }}</p>
                </div>

                <div class="hnt-outbound-actions">
                    <a href="{{ route('feed.index') }}" class="hnt-outbound-secondary">{{ __('ui.outbound_back') }}</a>
                    <a href="{{ $link->continueUrl() }}" class="hnt-outbound-primary" rel="nofollow noopener noreferrer">{{ __('ui.outbound_continue') }}</a>
                </div>
            </div>
        </section>
    </div>

    <aside class="w-full lg:w-[330px]">
        <section class="hnt-outbound-side box p-5">
            <h2>{{ __('ui.outbound_privacy_title') }}</h2>
            <p>{{ __('ui.outbound_privacy_text') }}</p>
            <ul>
                <li>{{ __('ui.outbound_privacy_point_1') }}</li>
                <li>{{ __('ui.outbound_privacy_point_2') }}</li>
                <li>{{ __('ui.outbound_privacy_point_3') }}</li>
            </ul>
        </section>
    </aside>
</div>
@endsection
