@extends('admin.layouts.app')

@section('title', 'Nutzerverwaltung · Admin · hnt.rocks')
@section('admin_heading', 'Nutzerverwaltung')

@push('head')
<link rel="stylesheet" href="{{ asset('assets/admin/admin-users-v2.css') }}?v=1">
@endpush

@section('content')
<div class="acp-users-v2">
    <header class="acp-u-panel acp-u-heading">
        <div>
            <span class="acp-u-eyebrow">HNT Administration / Community</span>
            <h1>Nutzerverwaltung</h1>
            <p>Mitglieder verwalten, Moderationsfälle prüfen und Zugriffsrechte bearbeiten.</p>
        </div>
        <div class="acp-u-counter" aria-label="Anzahl gefundener Nutzer">
            <strong>{{ number_format($users->total(), 0, ',', '.') }}</strong>
            <span>Gefundene Nutzer</span>
        </div>
    </header>

    @if(session('status'))
        <div class="hh-alert hh-alert-success" role="status">{{ session('status') }}</div>
    @endif

    <section class="acp-u-panel" aria-label="Nutzerfilter">
        <form method="get" action="{{ route('admin.users.index') }}" class="acp-u-filters">
            <div class="acp-u-field">
                <label for="acp-u-search">Nutzer suchen</label>
                <input id="acp-u-search" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, @username oder E-Mail">
            </div>
            <div class="acp-u-field">
                <label for="acp-u-status">Kontostatus</label>
                <select id="acp-u-status" name="status">
                    <option value="">Alle Status</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktiv</option>
                    <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Gesperrt</option>
                </select>
            </div>
            <label class="acp-u-filter-check" for="acp-u-admins">
                <input type="checkbox" id="acp-u-admins" name="admins" value="1" @checked(! empty($filters['admins']))>
                Nur Admins
            </label>
            <button class="acp-u-btn is-primary" type="submit">Filtern</button>
            <a class="acp-u-btn" href="{{ route('admin.users.index') }}">Zurücksetzen</a>
        </form>
    </section>

    <section class="acp-u-panel acp-u-table-panel">
        <div class="acp-u-section-heading">
            <h2>Mitgliederübersicht</h2>
            <small>Seite {{ $users->currentPage() }} von {{ max(1, $users->lastPage()) }}</small>
        </div>
        <div class="acp-u-table-scroll">
            <table class="acp-u-table">
                <thead>
                    <tr>
                        <th scope="col">Mitglied</th>
                        <th scope="col">E-Mail-Adresse</th>
                        <th scope="col">Status</th>
                        <th scope="col">Rolle</th>
                        <th scope="col">Moderation</th>
                        <th scope="col" style="text-align:right;">Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($users as $user)
                    @php
                        $isSuspended = ($user->status ?? 'active') === 'suspended';
                        $isActive = ($user->status ?? 'active') === 'active';
                        $pendingFlags = (int) ($user->pending_profile_flags_count ?? 0);
                        $avatarUrl = method_exists($user, 'avatarUrl')
                            ? $user->avatarUrl()
                            : asset('assets/vikinger/img/default-avatar.svg');
                    @endphp
                    <tr>
                        <td>
                            <div class="acp-u-user">
                                <img class="acp-u-avatar" src="{{ $avatarUrl }}" alt="" loading="lazy">
                                <div>
                                    <strong>{{ $user->name }}</strong>
                                    <span>{{ '@'.$user->username }} · Level {{ $user->level ?? 1 }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="acp-u-email">{{ $user->email }}</td>
                        <td>
                            <span @class(['acp-u-pill', 'is-green' => $isActive, 'is-red' => $isSuspended, 'is-muted' => ! $isActive && ! $isSuspended])>
                                {{ $isSuspended ? 'Gesperrt' : ($isActive ? 'Aktiv' : ($user->status ?? 'Unbekannt')) }}
                            </span>
                        </td>
                        <td>
                            <span @class(['acp-u-pill', 'is-blue' => $user->is_admin, 'is-muted' => ! $user->is_admin])>
                                {{ $user->is_admin ? 'Administrator' : 'Mitglied' }}
                            </span>
                        </td>
                        <td>
                            <span @class(['acp-u-flags', 'is-open' => $pendingFlags > 0])>
                                {{ $pendingFlags > 0 ? $pendingFlags.' offen' : 'Keine offenen' }}
                            </span>
                        </td>
                        <td>
                            <div class="acp-u-actions">
                                <a class="acp-u-btn" href="{{ route('admin.users.edit', $user) }}">Bearbeiten</a>
                                <details>
                                    <summary aria-label="Weitere Aktionen für {{ '@'.$user->username }}">
                                        <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#more"></use></svg>
                                    </summary>
                                    <div class="acp-u-action-menu">
                                        <form method="post" action="{{ route('admin.users.status', $user) }}" onsubmit="return confirm('{{ $isSuspended ? 'Mitglied entsperren?' : 'Mitglied wirklich sperren?' }}');">
                                            @csrf
                                            <input type="hidden" name="status" value="{{ $isSuspended ? 'active' : 'suspended' }}">
                                            <button type="submit" @class(['is-danger' => ! $isSuspended])>{{ $isSuspended ? 'Entsperren' : 'Sperren' }}</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.users.admin', $user) }}" onsubmit="return confirm('{{ $user->is_admin ? 'Adminrechte wirklich entfernen?' : 'Diesem Mitglied Adminrechte geben?' }}');">
                                            @csrf
                                            <button type="submit">{{ $user->is_admin ? 'Adminrechte entfernen' : 'Adminrechte vergeben' }}</button>
                                        </form>
                                    </div>
                                </details>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td class="acp-u-empty" colspan="6">Keine Nutzer gefunden. Passe die Suchfilter an.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="acp-u-table-footer">
            <span>{{ $users->total() ? ($users->firstItem().'–'.$users->lastItem().' von '.number_format($users->total(), 0, ',', '.')) : '0' }} Ergebnisse</span>
            <div>{{ $users->links() }}</div>
        </div>
    </section>
</div>
@endsection
