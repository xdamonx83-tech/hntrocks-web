@extends('layouts.auth')

@section('title', __('ui.login_title'))
@section('robots', 'noindex,follow')
@section('auth_tab', 'login')

@section('content')
    @include('auth.partials.landing', ['hhAuthMode' => 'login'])
@endsection
