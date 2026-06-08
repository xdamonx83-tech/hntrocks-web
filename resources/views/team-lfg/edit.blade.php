@extends('layouts.app')

@section('title', __('ui.team_lfg_edit_title') . ' · hnt.rocks')

@section('content')
<section class="hh-card hh-card-wide">
    <p class="hh-kicker">{{ __('ui.team_lfg_index_heading') }}</p>
    <h1>{{ __('ui.team_lfg_edit_title') }}</h1>
    <p>{{ __('ui.team_lfg_edit_intro') }}</p>

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

    <form method="post" action="{{ route('team-lfg.update', $post) }}" class="hh-form">
        @csrf
        @method('PUT')
        @include('team-lfg.partials.team-lfg-form', ['post' => $post, 'isEdit' => true, 'manageableTeams' => $manageableTeams])
        <button class="hh-primary-button" type="submit">{{ __('ui.team_lfg_save') }}</button>
    </form>

    <form method="post" action="{{ route('team-lfg.destroy', $post) }}" class="hh-danger-zone-form" onsubmit="return confirm('{{ __('ui.team_lfg_archive_confirm') }}')">
        @csrf
        @method('DELETE')
        <button class="hh-secondary-button hh-danger-link" type="submit">{{ __('ui.team_lfg_archive') }}</button>
    </form>
</section>
@endsection
