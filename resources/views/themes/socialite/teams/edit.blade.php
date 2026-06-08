@extends('themes.socialite.layouts.app')

@section('title', __('ui.team_edit_title') . ' · hnt.rocks')
@section('meta_description', __('ui.team_edit_banner_text'))

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @include('themes.socialite.teams.partials.form', [
        'team' => $team,
        'isEdit' => true,
        'title' => __('ui.team_edit_title'),
        'intro' => __('ui.team_edit_banner_text'),
        'action' => route('teams.update', $team),
        'method' => 'PUT',
        'classicUrl' => route('teams.edit', ['team' => $team, 'classic_teams' => 1]),
        'cancelUrl' => route('teams.show', $team),
        'submitLabel' => __('ui.team_saved_status'),
    ])
</div>
@endsection
