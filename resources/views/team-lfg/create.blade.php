@extends('layouts.app')

@section('title', __('ui.team_lfg_create_title') . ' · hnt.rocks')

@section('content')
<section class="hh-card hh-card-wide">
    <p class="hh-kicker">{{ __('ui.team_lfg_index_heading') }}</p>
    <h1>{{ __('ui.team_lfg_create_title') }}</h1>
    <p>{{ __('ui.team_lfg_create_intro') }}</p>

    @if ($errors->any())
        <div class="hh-alert hh-alert-danger">
            <strong>{{ __('ui.please_check') }}</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="{{ route('team-lfg.store') }}" class="hh-form">
        @csrf
        @include('team-lfg.partials.team-lfg-form', ['post' => null, 'isEdit' => false, 'manageableTeams' => $manageableTeams])
        <button class="hh-primary-button" type="submit">{{ __('ui.team_lfg_publish') }}</button>
    </form>
</section>
@endsection
