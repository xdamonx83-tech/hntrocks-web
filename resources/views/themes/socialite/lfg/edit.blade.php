@extends('themes.socialite.layouts.app')

@section('title', __('ui.lfg_edit_title') . ' · hnt.rocks')
@section('meta_description', __('ui.lfg_edit_banner_text'))

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @include('themes.socialite.lfg.partials.form', [
        'post' => $post,
        'isEdit' => true,
        'title' => __('ui.lfg_edit_title'),
        'intro' => __('ui.lfg_edit_banner_text'),
        'action' => route('lfg.update', $post),
        'method' => 'PUT',
        'classicUrl' => route('lfg.edit', ['post' => $post, 'classic_lfg' => 1]),
        'submitLabel' => __('ui.save_changes'),
    ])
</div>
@endsection
