@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.preview_nav_moments').' · HNT.rocks')
@section('app_window_class', 'hnt-moments-window')
@section('main_class', 'hnt-moments-main')
@section('right_sidebar')
    <aside class="hnt-moments-right-placeholder" hidden></aside>
@endsection

@section('content')
<div class="hnt-moment-empty-screen">
    <div class="hnt-moment-empty-card">
        <span>{{ __('ui.preview_nav_moments') }}</span>
        <h1>{{ __('ui.moment_week_empty_title') }}</h1>
        <p>{{ __('ui.moment_week_empty_text') }}</p>
        @auth
            <a class="btn-create" href="{{ route('moments.create') }}">{{ __('ui.moment_add') }}</a>
        @endauth
    </div>
</div>
@endsection
