@extends('admin.layouts.app')
@section('title', 'Arcade-Spiele')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;gap:16px;">
    <div>
        <h1>Arcade-Spiele</h1>
        <p>Native Spiele und kontrollierte Dynamic-Web-Clients werden hier verwaltet. Neue Spiele starten immer als Draft.</p>
    </div>
    <a href="{{ route('admin.arcade-games.create') }}">Neues Arcade-Spiel</a>
</div>
<table>
    <thead><tr><th>Key</th><th>Name</th><th>Typ</th><th>Status</th><th>Dynamic Release</th><th>Sortierung</th><th></th></tr></thead>
    <tbody>
    @foreach($games as $game)
        <tr>
            <td><code>{{ $game->key }}</code></td>
            <td>{{ $game->name_de }}</td>
            <td>{{ $game->type->value }}</td>
            <td>{{ $game->status->value }}</td>
            <td>{{ $game->publishedRelease?->version ?? '—' }}</td>
            <td>{{ $game->sort_order }}</td>
            <td><a href="{{ route('admin.arcade-games.edit', $game) }}">Bearbeiten / Publisher</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
@endsection
