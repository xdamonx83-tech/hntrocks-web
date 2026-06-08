@extends('admin.layouts.app')

@section('title', 'Nutzerverwaltung · Admin · hnt.rocks')

@section('admin_heading', 'Nutzerverwaltung')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>Nutzerverwaltung</h1>
        <p>Nutzer suchen, sperren/entsperren und Adminrechte verwalten.</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-card hh-card-compact hh-filter-card">
    <form class="hh-admin-filter" method="get" action="{{ route('admin.users.index') }}">
        <div>
            <label for="q">Suche</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, Username oder E-Mail">
        </div>
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Alle</option>
                <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktiv</option>
                <option value="suspended" @selected(($filters['status'] ?? '') === 'suspended')>Gesperrt</option>
            </select>
        </div>
        <label class="hh-checkline hh-checkline-card">
            <input type="checkbox" name="admins" value="1" @checked(! empty($filters['admins']))>
            Nur Admins
        </label>
        <button class="hh-primary-button" type="submit">Filtern</button>
    </form>
</section>

<section class="hh-card hh-card-compact">
    <div class="hh-admin-table">
        <table>
            <thead>
                <tr>
                    <th>Nutzer</th>
                    <th>E-Mail</th>
                    <th>Status</th>
                    <th>Rolle</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <strong>{{ $user->name }}</strong>
                            <span>{{ '@'.$user->username }} · Level {{ $user->level ?? 1 }}</span>
                        </td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->status ?? 'active' }}</td>
                        <td>{{ $user->is_admin ? 'Admin' : 'Nutzer' }}</td>
                        <td>
                            <div class="hh-admin-actions">
                                <form method="post" action="{{ route('admin.users.status', $user) }}">
                                    @csrf
                                    <input type="hidden" name="status" value="{{ ($user->status ?? 'active') === 'suspended' ? 'active' : 'suspended' }}">
                                    <button class="hh-secondary-button" type="submit">{{ ($user->status ?? 'active') === 'suspended' ? 'Entsperren' : 'Sperren' }}</button>
                                </form>
                                <form method="post" action="{{ route('admin.users.admin', $user) }}">
                                    @csrf
                                    <button class="hh-secondary-button" type="submit">{{ $user->is_admin ? 'Admin entfernen' : 'Admin machen' }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">Keine Nutzer gefunden.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="hh-pagination">
        {{ $users->links() }}
    </div>
</section>
@endsection
