@extends('themes.socialite.layouts.app')

@section('title', __('ui.team_lfg_edit_title') . ' · hnt.rocks')
@section('meta_description', __('ui.team_lfg_edit_intro'))

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @include('themes.socialite.team-lfg.partials.form', [
        'post' => $post,
        'isEdit' => true,
        'title' => __('ui.team_lfg_edit_title'),
        'intro' => __('ui.team_lfg_edit_intro'),
        'action' => route('team-lfg.update', $post),
        'method' => 'PUT',
        'classicUrl' => route('team-lfg.edit', ['post' => $post, 'classic_team_lfg' => 1]),
        'submitLabel' => __('ui.save_changes'),
        'manageableTeams' => $manageableTeams,
    ])
</div>
@endsection
