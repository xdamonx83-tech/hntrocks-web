@extends('admin.layouts.app')

@section('title', 'Profilmoderation · Admin · hnt.rocks')

@section('admin_heading', 'Profilmoderation')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Community</p>
        <h1>Profilmoderation</h1>
        <p>Automatisch markierte Profiltexte prüfen. Das System sperrt oder löscht niemals selbstständig.</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-card hh-card-compact">
    <div class="hh-admin-actions">
        <strong>Offen: {{ $counts['pending'] }}</strong>
        <span>Hohe Priorität: {{ $counts['high'] }}</span>
        <span>Bestätigt: {{ $counts['confirmed'] }}</span>
        <span>Bearbeitet: {{ $counts['actioned'] }}</span>
        <form method="post" action="{{ route('admin.profile-moderation.backfill') }}" onsubmit="return confirm('Alle bestehenden Profile erneut prüfen?');">
            @csrf
            <button class="hh-secondary-button" type="submit">Bestehende Profile prüfen</button>
        </form>
    </div>
</section>

<section class="hh-card hh-card-compact hh-filter-card">
    <form class="hh-admin-filter" method="get" action="{{ route('admin.profile-moderation.index') }}">
        <div>
            <label for="q">Suche</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="User, Text oder Grund">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                @foreach(['pending' => 'Zur Prüfung', 'confirmed' => 'Bestätigt', 'dismissed' => 'Fehlalarm', 'actioned' => 'Bearbeitet', 'superseded' => 'Veraltet'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? 'pending') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="category">Kategorie</label>
            <select id="category" name="category">
                <option value="">Alle</option>
                @foreach($categories as $category)
                    <option value="{{ $category }}" @selected(($filters['category'] ?? '') === $category)>{{ $category }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="min_score">Mindest-Score</label>
            <input id="min_score" type="number" min="0" max="100" name="min_score" value="{{ $filters['min_score'] ?? '' }}" placeholder="z. B. 80">
        </div>
        <button class="hh-primary-button" type="submit">Filtern</button>
    </form>
</section>

<section class="hh-card hh-card-compact">
    <div class="hh-admin-table">
        <table>
            <thead>
                <tr>
                    <th>Nutzer</th>
                    <th>Feld</th>
                    <th>Hinweis</th>
                    <th>Score</th>
                    <th>Text</th>
                    <th>Aktionen</th>
                </tr>
            </thead>
            <tbody>
                @forelse($flags as $flag)
                    <tr>
                        <td>
                            <strong>{{ $flag->user?->name ?? 'Unbekannt' }}</strong>
                            <span>{{ $flag->user?->username ? '@'.$flag->user->username : '' }}</span>
                        </td>
                        <td>{{ $flag->field }}</td>
                        <td>
                            <strong>{{ $flag->category }}</strong>
                            <span>{{ $flag->reason }}</span>
                        </td>
                        <td><strong>{{ $flag->score }}/100</strong></td>
                        <td style="max-width:420px; white-space:normal;">{{ $flag->excerpt }}</td>
                        <td>
                            <div class="hh-admin-actions">
                                @if($flag->user)
                                    <a class="hh-secondary-button" href="{{ route('admin.users.edit', $flag->user) }}">Profil bearbeiten</a>
                                @endif

                                @if($flag->status === 'pending')
                                    <form method="post" action="{{ route('admin.profile-moderation.review', $flag) }}">
                                        @csrf
                                        <input type="hidden" name="decision" value="confirmed">
                                        <button class="hh-secondary-button" type="submit">Verstoß bestätigen</button>
                                    </form>
                                    <form method="post" action="{{ route('admin.profile-moderation.review', $flag) }}">
                                        @csrf
                                        <input type="hidden" name="decision" value="dismissed">
                                        <button class="hh-secondary-button" type="submit">Fehlalarm</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">Keine Profile für diesen Filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="hh-pagination">
        {{ $flags->links() }}
    </div>
</section>
@endsection
