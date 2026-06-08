@extends('layouts.app')

@section('title', __('ui.feed_show_title'))

@section('content')
@php
    $viewer = auth()->user();
    $postTeam = $post->team;
@endphp

<div class="section-banner hh-feed-section-banner hh-feed-single-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/newsfeed-icon.png') }}" alt="{{ __('ui.newsfeed') }}">
    <p class="section-banner-title">{{ __('ui.feed_post') }}</p>
    <p class="section-banner-text">
        @if ($postTeam)
            {{ __('ui.feed_team_post_from', ['team' => $postTeam->name]) }}
        @else
            {{ __('ui.feed_single_default') }}
        @endif
    </p>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-3-6-3 mobile-prefer-content hh-feed-single-grid">
    <div class="grid-column hh-feed-single-side-column">
        <div class="widget-box hh-feed-single-nav-card">
            <p class="widget-box-title">{{ __('ui.navigation') }}</p>
            <div class="widget-box-content">
                <a class="button small secondary full" href="{{ route('feed.index') }}">{{ __('ui.back_to_feed') }}</a>
                @if ($postTeam)
                    <a class="button small white full hh-feed-single-nav-secondary" href="{{ route('teams.show', $postTeam) }}">{{ __('ui.to_team') }}</a>
                @endif
            </div>
        </div>
    </div>

    <div class="grid-column hh-feed-single-main-column">
        @include('feed.partials.post-card', [
            'post' => $post,
            'showComments' => true,
            'showAllComments' => true,
        ])
    </div>

    <div class="grid-column hh-feed-single-side-column">
        <div class="widget-box hh-feed-single-info-card">
            <p class="widget-box-title">{{ __('ui.note') }}</p>
            <div class="widget-box-content">
                <p class="widget-box-text">{{ __('ui.feed_show_text') }}</p>
            </div>
        </div>
    </div>
</div>
@endsection
