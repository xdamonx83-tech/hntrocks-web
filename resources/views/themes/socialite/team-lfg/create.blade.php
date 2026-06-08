@extends('themes.socialite.layouts.app')

@section('title', __('ui.team_lfg_create_title') . ' · hnt.rocks')
@section('meta_description', __('ui.team_lfg_create_intro'))

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @include('themes.socialite.team-lfg.partials.form', [
        'post' => null,
        'isEdit' => false,
        'title' => __('ui.team_lfg_create_title'),
        'intro' => __('ui.team_lfg_create_intro'),
        'action' => route('team-lfg.store'),
        'method' => 'POST',
        'classicUrl' => route('team-lfg.create', ['classic_team_lfg' => 1]),
        'submitLabel' => __('ui.team_lfg_publish'),
        'manageableTeams' => $manageableTeams,
    ])
</div>
@endsection
