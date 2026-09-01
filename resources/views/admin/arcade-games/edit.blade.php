@extends('admin.layouts.app')
@section('title', 'Arcade-Spiel bearbeiten')
@section('content')
<h1>{{ $game->name_de }} bearbeiten</h1>
<p>Technischer Key: <code>{{ $game->key }}</code> (nicht bearbeitbar)</p>
<form method="post" enctype="multipart/form-data" action="{{ route('admin.arcade-games.update', $game) }}">
    @csrf @method('put')
    @foreach(['name_de'=>'Name DE','name_en'=>'Name EN','badge_de'=>'Badge DE','badge_en'=>'Badge EN','client_engine_key'=>'Client Engine Key','min_client_version'=>'Min. Client Version','launch_url'=>'Legacy Launch URL','sort_order'=>'Sortierung','min_players'=>'Min. Spieler','max_players'=>'Max. Spieler','game_version'=>'Game Version'] as $field=>$label)
        <label>{{ $label }}<input name="{{ $field }}" value="{{ old($field, $game->$field) }}"></label>
        @error($field)<div>{{ $message }}</div>@enderror
    @endforeach
    @foreach(['description_de'=>'Beschreibung DE','description_en'=>'Beschreibung EN'] as $field=>$label)
        <label>{{ $label }}<textarea name="{{ $field }}">{{ old($field, $game->$field) }}</textarea></label>
        @error($field)<div>{{ $message }}</div>@enderror
    @endforeach
    <label>Typ <select name="type">@foreach(['native','web'] as $v)<option value="{{ $v }}" @selected(old('type',$game->type->value)===$v)>{{ $v }}</option>@endforeach</select></label>
    <label>Status <select name="status">@foreach(['draft','active','coming_soon','maintenance','event','disabled'] as $v)<option value="{{ $v }}" @selected(old('status',$game->status->value)===$v)>{{ $v }}</option>@endforeach</select></label>
    <label><input type="checkbox" name="casual_enabled" value="1" @checked(old('casual_enabled',$game->casual_enabled))> Casual</label>
    <label><input type="checkbox" name="ranked_enabled" value="1" @checked(old('ranked_enabled',$game->ranked_enabled))> Ranked</label>
    <label>Cover <input type="file" name="cover" accept="image/*"></label>
    <button type="submit">Spiel speichern</button>
</form>

@if($game->type->value === 'web')
    <hr>
    <h2>Dynamic Client Publisher</h2>
    <p>Der Publisher registriert nur versionierte Releases auf HNT-kontrollierten HTTPS-Hosts. Es werden keine ZIPs entpackt und kein Servercode hochgeladen. Ein Release ändert den Spielstatus nicht automatisch.</p>

    <h3>Neuen Release-Draft registrieren</h3>
    <form method="post" action="{{ route('admin.arcade-games.releases.store', $game) }}">
        @csrf
        <label>SemVer <input name="version" placeholder="1.0.0" value="{{ old('version') }}"></label>
        @error('version')<div>{{ $message }}</div>@enderror
        <label>Entrypoint URL <input name="entrypoint_url" placeholder="https://games.hnt.rocks/example/1.0.0/index.html" value="{{ old('entrypoint_url') }}"></label>
        @error('entrypoint_url')<div>{{ $message }}</div>@enderror
        <label>Manifest URL (optional) <input name="manifest_url" placeholder="https://games.hnt.rocks/example/1.0.0/manifest.json" value="{{ old('manifest_url') }}"></label>
        @error('manifest_url')<div>{{ $message }}</div>@enderror
        <label>Artifact SHA-256 <input name="integrity_sha256" maxlength="64" value="{{ old('integrity_sha256') }}"></label>
        @error('integrity_sha256')<div>{{ $message }}</div>@enderror
        <button type="submit">Release-Draft anlegen</button>
    </form>

    <h3>Releases</h3>
    @if($game->releases->isEmpty())
        <p>Noch keine Releases registriert.</p>
    @else
        <table>
            <thead><tr><th>Version</th><th>Status</th><th>Entrypoint</th><th>SHA-256</th><th>Published</th><th>Aktion</th></tr></thead>
            <tbody>
            @foreach($game->releases as $release)
                <tr>
                    <td><code>{{ $release->version }}</code></td>
                    <td>{{ $release->status }}</td>
                    <td><code>{{ $release->entrypoint_url }}</code></td>
                    <td><code>{{ $release->integrity_sha256 }}</code></td>
                    <td>{{ $release->published_at?->toDateTimeString() ?? '—' }}</td>
                    <td>
                        @if($release->status !== \App\Models\Arcade\ArcadeGameRelease::STATUS_PUBLISHED)
                            <form method="post" action="{{ route('admin.arcade-games.releases.publish', [$game, $release]) }}" style="display:inline">@csrf<button type="submit">Veröffentlichen</button></form>
                        @endif
                        @if($release->status !== \App\Models\Arcade\ArcadeGameRelease::STATUS_RETIRED)
                            <form method="post" action="{{ route('admin.arcade-games.releases.retire', [$game, $release]) }}" style="display:inline">@csrf<button type="submit">Zurückziehen</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
@endif
@endsection
