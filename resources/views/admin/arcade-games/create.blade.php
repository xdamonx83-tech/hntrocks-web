@extends('admin.layouts.app')
@section('title', 'Arcade-Spiel anlegen')
@section('content')
<h1>Arcade-Spiel anlegen</h1>
<p>Das Spiel wird unabhängig von den Eingaben immer als <strong>Draft</strong> angelegt. Eine Veröffentlichung erfolgt erst später bewusst über Status und – bei Web-Spielen – einen veröffentlichten Dynamic-Release.</p>
<form method="post" enctype="multipart/form-data" action="{{ route('admin.arcade-games.store') }}">
    @csrf
    <input type="hidden" name="status" value="draft">
    @foreach(['key'=>'Technischer Key','name_de'=>'Name DE','name_en'=>'Name EN','badge_de'=>'Badge DE','badge_en'=>'Badge EN','client_engine_key'=>'Client Engine Key','min_client_version'=>'Min. Client Version','launch_url'=>'Legacy Launch URL','sort_order'=>'Sortierung','min_players'=>'Min. Spieler','max_players'=>'Max. Spieler','game_version'=>'Game Version'] as $field=>$label)
        <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field, in_array($field, ['sort_order'], true) ? 0 : (in_array($field, ['min_players','max_players','game_version'], true) ? 1 : '')) }}"></label>
        @error($field)<div>{{ $message }}</div>@enderror
    @endforeach
    @foreach(['description_de'=>'Beschreibung DE','description_en'=>'Beschreibung EN'] as $field=>$label)
        <label>{{ $label }}<textarea name="{{ $field }}">{{ old($field) }}</textarea></label>
        @error($field)<div>{{ $message }}</div>@enderror
    @endforeach
    <label>Typ
        <select name="type">
            @foreach(['native','web'] as $v)<option value="{{ $v }}" @selected(old('type','web')===$v)>{{ $v }}</option>@endforeach
        </select>
    </label>
    <label><input type="checkbox" name="casual_enabled" value="1" @checked(old('casual_enabled'))> Casual</label>
    <label><input type="checkbox" name="ranked_enabled" value="1" @checked(old('ranked_enabled'))> Ranked</label>
    <label>Cover <input type="file" name="cover" accept="image/*"></label>
    @error('cover')<div>{{ $message }}</div>@enderror
    <button type="submit">Als Draft anlegen</button>
</form>
@endsection
