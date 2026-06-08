@php
    $selectedType = old('type', $post?->type ?? 'team_seeks_players');
@endphp

<div class="hh-form-grid">
    <div>
        <label for="type">{{ __('ui.team_lfg_type_label') }}</label>
        <select id="type" name="type" required>
            <option value="team_seeks_players" @selected($selectedType === 'team_seeks_players')>{{ __('ui.team_lfg_type_team_seeks_players') }}</option>
            <option value="player_seeks_team" @selected($selectedType === 'player_seeks_team')>{{ __('ui.team_lfg_type_player_seeks_team') }}</option>
        </select>
    </div>
    <div>
        <label for="team_id">{{ __('ui.team_lfg_team_title') }}</label>
        <select id="team_id" name="team_id">
            <option value="">{{ __('ui.team_lfg_form_no_team') }}</option>
            @foreach ($manageableTeams as $team)
                <option value="{{ $team->id }}" @selected((int) old('team_id', $post?->team_id) === (int) $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
    </div>
</div>

@if ($manageableTeams->isEmpty())
    <div class="hh-alert hh-alert-danger">
        {{ __('ui.team_lfg_form_team_required_hint') }}
    </div>
@endif

<div>
    <label for="title">{{ __('ui.team_lfg_form_title') }}</label>
    <input id="title" name="title" type="text" value="{{ old('title', $post?->title) }}" maxlength="140" required placeholder="{{ __('ui.team_lfg_form_title_placeholder') }}">
</div>

<label for="body">{{ __('ui.team_lfg_form_body') }}</label>
<textarea id="body" name="body" rows="6" maxlength="3200" data-hh-mention-context="team_lfg" data-hh-mention-team-field="#team_id" placeholder="{{ __('ui.team_lfg_form_body_placeholder') }}">{{ old('body', $post?->body) }}</textarea>

<div class="hh-form-grid">
    <div>
        <label for="platform">{{ __('ui.team_lfg_platform') }}</label>
        <select id="platform" name="platform">
            <option value="">{{ __('ui.select_option') }}</option>
            @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                <option value="{{ $option }}" @selected(old('platform', $post?->platform) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="playstyle">{{ __('ui.team_lfg_playstyle') }}</label>
        <select id="playstyle" name="playstyle">
            <option value="">{{ __('ui.select_option') }}</option>
            @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                <option value="{{ $option }}" @selected(old('playstyle', $post?->playstyle) === $option)>{{ \App\Models\TeamLfgPost::localizedOptionLabelFor('playstyle', $option) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="region">{{ __('ui.team_lfg_region') }}</label>
        <select id="region" name="region">
            <option value="">{{ __('ui.select_option') }}</option>
            @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                <option value="{{ $option }}" @selected(old('region', $post?->region) === $option)>{{ $option }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="language">{{ __('ui.team_lfg_language') }}</label>
        <select id="language" name="language">
            <option value="">{{ __('ui.select_option') }}</option>
            @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                <option value="{{ $option }}" @selected(old('language', $post?->language) === $option)>{{ \App\Models\TeamLfgPost::localizedOptionLabelFor('language', $option) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="preferred_time">{{ __('ui.team_lfg_preferred_time') }}</label>
        <select id="preferred_time" name="preferred_time">
            <option value="">{{ __('ui.select_option') }}</option>
            @foreach (['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                <option value="{{ $option }}" @selected(old('preferred_time', $post?->preferred_time) === $option)>{{ \App\Models\TeamLfgPost::localizedOptionLabelFor('preferred_time', $option) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="experience_level">{{ __('ui.team_lfg_experience') }}</label>
        <select id="experience_level" name="experience_level">
            <option value="">{{ __('ui.select_option') }}</option>
            @foreach (['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                <option value="{{ $option }}" @selected(old('experience_level', $post?->experience_level) === $option)>{{ \App\Models\TeamLfgPost::localizedOptionLabelFor('experience_level', $option) }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label for="slots_total">{{ __('ui.team_lfg_form_slots_total') }}</label>
        <input id="slots_total" name="slots_total" type="number" min="1" max="50" value="{{ old('slots_total', $post?->slots_total ?? 1) }}">
    </div>
    <div>
        <label for="visibility">{{ __('ui.team_lfg_form_visibility') }}</label>
        <select id="visibility" name="visibility" required>
            <option value="public" @selected(old('visibility', $post?->visibility ?? 'public') === 'public')>{{ __('ui.team_lfg_visibility_public') }}</option>
            <option value="private" @selected(old('visibility', $post?->visibility) === 'private')>{{ __('ui.team_lfg_visibility_private') }}</option>
        </select>
    </div>
    @if ($isEdit)
        <div>
            <label for="slots_filled">{{ __('ui.team_lfg_form_slots_filled') }}</label>
            <input id="slots_filled" name="slots_filled" type="number" min="0" max="50" value="{{ old('slots_filled', $post?->slots_filled ?? 0) }}">
        </div>
        <div>
            <label for="status">{{ __('ui.status') }}</label>
            <select id="status" name="status" required>
                <option value="open" @selected(old('status', $post?->status) === 'open')>{{ __('ui.team_lfg_status_open') }}</option>
                <option value="filled" @selected(old('status', $post?->status) === 'filled')>{{ __('ui.team_lfg_status_filled') }}</option>
                <option value="closed" @selected(old('status', $post?->status) === 'closed')>{{ __('ui.team_lfg_status_closed') }}</option>
            </select>
        </div>
    @endif
</div>

<label class="hh-checkline hh-checkline-card">
    <input type="checkbox" name="voice_required" value="1" @checked(old('voice_required', $post?->voice_required ?? false))>
    {{ __('ui.team_lfg_form_voice_required') }}
</label>
