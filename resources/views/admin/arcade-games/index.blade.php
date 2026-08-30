@extends('admin.layouts.app')
@section('title', 'Arcade-Spiele')
@section('content')
<h1>Arcade-Spiele</h1>
<p>Der technische Key ist nach Anlage unveränderlich. Settings und Reward Settings werden in Phase A nicht frei im Admin bearbeitet.</p>
<table><thead><tr><th>Key</th><th>Name</th><th>Typ</th><th>Status</th><th>Sortierung</th><th></th></tr></thead><tbody>
@foreach($games as $game)<tr><td><code>{{ $game->key }}</code></td><td>{{ $game->name_de }}</td><td>{{ $game->type->value }}</td><td>{{ $game->status->value }}</td><td>{{ $game->sort_order }}</td><td><a href="{{ route('admin.arcade-games.edit', $game) }}">Bearbeiten</a></td></tr>@endforeach
</tbody></table>
@endsection
