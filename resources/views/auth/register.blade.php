@extends('layouts.auth')

@section('title', __('ui.register_title'))
@section('robots', 'noindex,follow')
@section('auth_tab', 'register')

@section('content')
    @include('auth.partials.landing', ['hhAuthMode' => 'register'])
@endsection
