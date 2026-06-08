@extends('admin.layouts.app')

@section('title', 'Reports · Admin · hnt.rocks')

@section('admin_heading', 'Reports')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>Reports</h1>
        <p>Gemeldete Nutzer, Beiträge, Medien, Moments, Teams, LFGs und Cup-Inhalte prüfen.</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-card hh-card-compact hh-filter-card">
    <form class="hh-admin-filter" method="get" action="{{ route('admin.reports.index') }}">
        <div>
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Alle</option>
                @foreach(['open' => 'Offen', 'in_review' => 'In Prüfung', 'resolved' => 'Erledigt', 'rejected' => 'Abgelehnt'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="reason">Grund</label>
            <select id="reason" name="reason">
                <option value="">Alle</option>
                @foreach(['spam' => 'Spam', 'abuse' => 'Beleidigung', 'hate' => 'Hassrede', 'nsfw' => 'NSFW', 'fraud' => 'Betrug', 'cheating' => 'Cheating', 'privacy' => 'Datenschutz', 'other' => 'Sonstiges'] as $value => $label)
                    <option value="{{ $value }}" @selected(($filters['reason'] ?? '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="hh-primary-button" type="submit">Filtern</button>
    </form>
</section>

<section class="hh-admin-report-list">
    @forelse($reports as $report)
        @php($reportUrl = $report->reportableUrl())
        <article class="hh-card hh-card-compact">
            <div class="hh-admin-report-head">
                <div>
                    <p class="hh-kicker">{{ $report->statusLabel() }}</p>
                    <h2>{{ $report->reportableLabel() }} · {{ $report->reasonLabel() }}</h2>
                    <p>{{ $report->body ?: 'Keine zusätzliche Beschreibung.' }}</p>

                    <div class="hh-admin-report-context">
                        <span>Gemeldet von {{ $report->reporterLabel() }}</span>
                        <span>{{ $report->created_at->diffForHumans() }}</span>
                        @if ($report->assignee)
                            <span>Bearbeitung: {{ $report->assignee->username ? '@'.$report->assignee->username : $report->assignee->name }}</span>
                        @endif
                    </div>

                    <div class="hh-admin-report-actions">
                        @if ($reportUrl)
                            <a class="hh-secondary-button hh-admin-report-target-link" href="{{ $reportUrl }}" target="_blank" rel="noopener">Gemeldeten Inhalt öffnen</a>
                        @else
                            <span class="hh-admin-report-target-missing">Ziel nicht mehr direkt verlinkbar</span>
                        @endif
                    </div>
                </div>
                <div class="hh-admin-report-meta">
                    <span>{{ class_basename((string) $report->reportable_type) }}</span>
                    <strong>#{{ $report->reportable_id }}</strong>
                </div>
            </div>

            <form class="hh-admin-report-form" method="post" action="{{ route('admin.reports.update', $report) }}">
                @csrf
                <div>
                    <label>Status</label>
                    <select name="status">
                        @foreach(['open' => 'Offen', 'in_review' => 'In Prüfung', 'resolved' => 'Erledigt', 'rejected' => 'Abgelehnt'] as $value => $label)
                            <option value="{{ $value }}" @selected($report->status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label>Notiz</label>
                    <input type="text" name="resolution_note" value="{{ $report->resolution_note }}" placeholder="Moderationsnotiz">
                </div>
                <button class="hh-primary-button" type="submit">Speichern</button>
            </form>
        </article>
    @empty
        <section class="hh-card hh-card-compact">
            <p class="hh-muted">Keine Reports gefunden.</p>
        </section>
    @endforelse
</section>

<div class="hh-pagination">
    {{ $reports->links() }}
</div>
@endsection
