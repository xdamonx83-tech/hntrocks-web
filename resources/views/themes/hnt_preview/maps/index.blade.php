@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.maps_meta_title'))
@section('robots', 'index,follow,max-image-preview:large,max-snippet:-1,max-video-preview:-1')
@section('meta_description', __('ui.maps_meta_description'))
@section('canonical', route('maps.index'))
@section('og_title', __('ui.maps_og_title'))
@section('og_description', __('ui.maps_og_description'))
@section('og_url', route('maps.index'))
@section('og_image', asset('assets/hnt/maps/stillwater-bayou/map.webp'))
@section('app_window_class', 'hnt-maps-window hnt-maps-finance-window')
@section('main_class', 'hnt-maps-finance-main')
@section('right_sidebar')<aside hidden></aside>@endsection

@push('head')
<link rel="stylesheet" href="{{ asset('assets/hnt/maps/maps-finance-overview.css') }}?v=1">
@include('maps.partials.finance-seo')
@endpush

@section('content')
@include('maps.partials.finance-overview')
@endsection
