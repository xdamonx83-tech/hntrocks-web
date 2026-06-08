@extends('themes.socialite.layouts.app')

@section('title', __('ui.lfg_create_title') . ' · hnt.rocks')
@section('meta_description', __('ui.lfg_create_banner_text'))

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @include('themes.socialite.lfg.partials.form', [
        'post' => null,
        'isEdit' => false,
        'title' => __('ui.lfg_create_title'),
        'intro' => __('ui.lfg_create_banner_text'),
        'action' => route('lfg.store'),
        'method' => 'POST',
        'classicUrl' => route('lfg.create', ['classic_lfg' => 1]),
        'submitLabel' => __('ui.lfg_publish'),
    ])
</div>
@endsection
