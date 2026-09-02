@extends('admin.layouts.app')
@section('title', 'Arcade-Spiel bearbeiten')
@section('content')
@php($rankedRewards = $game->rankedRewardSettings())
<h1>{{ $game->name_de }} bearbeiten</h1>
<p>Technischer Key: <code>{{ $game->key }}</code> (nicht bearbeitbar)</p>
<form method="post" enctype="multipart/form-data" action="{{ route('admin.arcade-games.update', $game) }}">@csrf @method('put')
@foreach(['name_de'=>'Name DE','name_en'=>'Name EN','badge_de'=>'Badge DE','badge_en'=>'Badge EN','client_engine_key'=>'Client Engine Key','min_client_version'=>'Min. Client Version','launch_url'=>'Launch URL','sort_order'=>'Sortierung','min_players'=>'Min. Spieler','max_players'=>'Max. Spieler','game_version'=>'Game Version'] as $field=>$label)<label>{{ $label }}<input name="{{ $field }}" value="{{ old($field, $game->$field) }}"></label>@error($field)<div>{{ $message }}</div>@enderror @endforeach
@foreach(['description_de'=>'Beschreibung DE','description_en'=>'Beschreibung EN'] as $field=>$label)<label>{{ $label }}<textarea name="{{ $field }}">{{ old($field, $game->$field) }}</textarea></label>@endforeach
<label>Typ <select name="type">@foreach(['native','web'] as $v)<option @selected(old('type',$game->type->value)===$v)>{{ $v }}</option>@endforeach</select></label>
<label>Status <select name="status">@foreach(['active','coming_soon','maintenance','event','disabled'] as $v)<option @selected(old('status',$game->status->value)===$v)>{{ $v }}</option>@endforeach</select></label>
<label><input type="checkbox" name="casual_enabled" value="1" @checked(old('casual_enabled',$game->casual_enabled))> Casual</label><label><input type="checkbox" name="ranked_enabled" value="1" @checked(old('ranked_enabled',$game->ranked_enabled))> Ranked</label>
<fieldset><legend>Ranked Rewards</legend>
<input type="hidden" name="reward_settings[ranked_reward_enabled]" value="0"><label><input type="checkbox" name="reward_settings[ranked_reward_enabled]" value="1" @checked(old('reward_settings.ranked_reward_enabled',$rankedRewards['ranked_reward_enabled']))> Ranked Rewards aktiv</label>
@foreach(['ranked_win_reward'=>'Sieg','ranked_draw_reward'=>'Unentschieden','ranked_loss_reward'=>'Niederlage'] as $field=>$label)<label>{{ $label }}<input type="number" min="0" max="{{ \App\Models\Arcade\ArcadeGame::MAX_RANKED_REWARD }}" name="reward_settings[{{ $field }}]" value="{{ old('reward_settings.'.$field,$rankedRewards[$field]) }}"></label>@error('reward_settings.'.$field)<div>{{ $message }}</div>@enderror @endforeach
</fieldset>
<label>Cover <input type="file" name="cover" accept="image/*"></label><button type="submit">Speichern</button>
</form>
@endsection
