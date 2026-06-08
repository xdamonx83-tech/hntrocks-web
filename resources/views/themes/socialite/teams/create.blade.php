@extends('themes.socialite.layouts.app')

@section('title', __('ui.team_create_title') . ' · hnt.rocks')
@section('meta_description', __('ui.team_create_banner_text'))

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @include('themes.socialite.teams.partials.form', [
        'team' => null,
        'isEdit' => false,
        'title' => __('ui.team_create_title'),
        'intro' => __('ui.team_create_banner_text'),
        'action' => route('teams.store'),
        'method' => 'POST',
        'classicUrl' => route('teams.create', ['classic_teams' => 1]),
        'cancelUrl' => route('teams.index'),
        'submitLabel' => __('ui.create_team'),
    ])
</div>
@endsection
