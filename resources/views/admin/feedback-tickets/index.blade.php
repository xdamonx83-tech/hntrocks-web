@extends('admin.layouts.app')

@section('title', 'Feedback & Tickets - Admin - hnt.rocks')

@section('admin_heading', 'Feedback & Tickets')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>Feedback & Tickets</h1>
        <p>Feedback aus der App kompakt prüfen und den Bearbeitungsstatus setzen.</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-admin-stat-grid">
    <article class="hh-card hh-card-compact"><strong>{{ $stats['open'] }}</strong><span>Offen</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['in_review'] }}</strong><span>In Prüfung</span></article>
    <article class="hh-card hh-card-compact"><strong>{{ $stats['closed'] }}</strong><span>Geschlossen</span></article>
</section>

<section class="hh-card hh-card-compact hh-filter-card">
    <form class="hh-admin-filter" method="get" action="{{ route('admin.feedback-tickets.index') }}">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Alle</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="type">Typ</label>
            <select id="type" name="type">
                <option value="">Alle</option>
                @foreach($typeOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="hh-primary-button" type="submit">Filtern</button>
    </form>
</section>

<section class="hh-admin-report-list">
    @forelse($tickets as $ticket)
        <article class="hh-card hh-card-compact">
            <div class="hh-admin-report-head">
                <div>
                    <p class="hh-kicker">{{ $ticket->statusLabel() }} - {{ $ticket->typeLabel() }}</p>
                    <h2>{{ $ticket->subject }}</h2>
                    <p>{{ $ticket->message }}</p>

                    <div class="hh-admin-report-context">
                        <span>{{ $ticket->userLabel() }}</span>
                        <span>{{ $ticket->created_at->diffForHumans() }}</span>
                        @if($ticket->user?->email)
                            <span>{{ $ticket->user->email }}</span>
                        @endif
                    </div>
                </div>
                <div class="hh-admin-report-meta">
                    <span>Ticket</span>
                    <strong>#{{ $ticket->id }}</strong>
                </div>
            </div>

            @if(! empty($ticket->meta))
                <div class="hh-card hh-card-compact" style="margin-top: 1rem;">
                    <h3>Kontext</h3>
                    <p class="hh-muted">{{ json_encode($ticket->meta, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</p>
                </div>
            @endif

            <form class="hh-admin-report-form" method="post" action="{{ route('admin.feedback-tickets.update', $ticket) }}">
                @csrf
                <div>
                    <label>Status</label>
                    <select name="status">
                        @foreach($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($ticket->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="hh-primary-button" type="submit">Speichern</button>
            </form>
        </article>
    @empty
        <section class="hh-card hh-card-compact">
            <p class="hh-muted">Keine Feedback-Tickets gefunden.</p>
        </section>
    @endforelse
</section>

<div class="hh-pagination">
    {{ $tickets->links() }}
</div>
@endsection
