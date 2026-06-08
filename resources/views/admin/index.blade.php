@extends('admin.layouts.app')

@section('title', 'Admin · hnt.rocks')

@section('admin_heading', 'Dashboard')

@section('content')
@php
    $maxWeekly = max(1, collect($weeklySeries ?? [])->max('total') ?: 1);
@endphp

<section class="hh-admin-dashboard-hero">
    <div>
        <p class="hh-kicker">Admin Dashboard</p>
        <h1>Willkommen zurück, {{ auth()->user()?->name ?? 'Admin' }}</h1>
        <p>Dein schneller Überblick über Community, Moderation, Cups, Feedback und aktuelle Plattform-Aktivität.</p>
    </div>
    <div class="hh-admin-dashboard-hero-actions">
        <a class="hh-secondary-button" href="{{ route('feed.index') }}">Zur Website</a>
        <a class="hh-primary-button" href="{{ route('admin.reports.index') }}">Reports prüfen</a>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-dashboard-card-grid" aria-label="Wichtige Kennzahlen">
    @foreach($dashboardCards as $card)
        <a class="hh-dashboard-metric is-{{ $card['accent'] }}" href="{{ $card['route'] }}">
            <span>{{ $card['label'] }}</span>
            <strong>{{ number_format((int) $card['value'], 0, ',', '.') }}</strong>
            <small>{{ $card['meta'] }}</small>
        </a>
    @endforeach
</section>

<section class="hh-dashboard-layout">
    <div class="hh-dashboard-main-column">
        <article class="hh-card hh-dashboard-chart-card">
            <div class="hh-card-title-row">
                <div>
                    <h2>Aktivität der letzten 7 Tage</h2>
                    <p class="hh-muted">Beiträge, Kommentare und neue Mitglieder als schneller Tagesvergleich.</p>
                </div>
                <span class="hh-admin-chip">Live-Daten</span>
            </div>

            <div class="hh-dashboard-bars" aria-label="Aktivitätsdiagramm">
                @foreach($weeklySeries as $day)
                    @php
                        $height = max(12, (int) round(((int) $day['total'] / $maxWeekly) * 100));
                    @endphp
                    <div class="hh-dashboard-bar-item">
                        <div class="hh-dashboard-bar-stack" title="{{ $day['total'] }} Aktivitäten">
                            <span style="height: {{ $height }}%"></span>
                        </div>
                        <strong>{{ $day['label'] }}</strong>
                        <small>{{ $day['total'] }}</small>
                    </div>
                @endforeach
            </div>
        </article>

        <section class="hh-dashboard-split-grid">
            <article class="hh-card">
                <div class="hh-card-title-row">
                    <div>
                        <h2>Neue Reports</h2>
                        <p class="hh-muted">Schnell prüfen, was Moderation braucht.</p>
                    </div>
                    <a class="hh-link-button" href="{{ route('admin.reports.index') }}">Alle öffnen</a>
                </div>
                <div class="hh-dashboard-list">
                    @forelse($latestReports as $report)
                        <a href="{{ route('admin.reports.index', ['status' => $report->status]) }}" class="hh-dashboard-list-row">
                            <span class="hh-dashboard-dot is-{{ in_array($report->status, ['open', 'in_review'], true) ? 'red' : 'green' }}"></span>
                            <div>
                                <strong>{{ $report->reportableLabel() }} · {{ $report->reasonLabel() }}</strong>
                                <small>{{ $report->statusLabel() }} · {{ $report->reporter?->username ? '@'.$report->reporter->username : 'System' }} · {{ $report->created_at?->diffForHumans() }}</small>
                            </div>
                        </a>
                    @empty
                        <p class="hh-empty-state">Keine Reports vorhanden.</p>
                    @endforelse
                </div>
            </article>

            <article class="hh-card">
                <div class="hh-card-title-row">
                    <div>
                        <h2>Cup-Feedback</h2>
                        <p class="hh-muted">Neue Stimmen zum vergangenen Cup.</p>
                    </div>
                    <a class="hh-link-button" href="{{ route('admin.cup-feedback.index') }}">Feedback öffnen</a>
                </div>
                <div class="hh-dashboard-list">
                    @forelse($latestFeedback as $feedback)
                        <a href="{{ route('admin.cup-feedback.index', ['status' => $feedback->status]) }}" class="hh-dashboard-list-row">
                            <span class="hh-dashboard-avatar">{{ strtoupper(substr($feedback->user?->name ?? $feedback->user?->username ?? 'F', 0, 1)) }}</span>
                            <div>
                                <strong>{{ $feedback->subject ?: $feedback->categoryLabel() }}</strong>
                                <small>{{ $feedback->user?->username ? '@'.$feedback->user->username : 'Gast' }} · {{ $feedback->statusLabel() }} · {{ $feedback->submitted_at?->diffForHumans() ?? $feedback->created_at?->diffForHumans() }}</small>
                            </div>
                        </a>
                    @empty
                        <p class="hh-empty-state">Noch kein Cup-Feedback vorhanden.</p>
                    @endforelse
                </div>
            </article>
        </section>

        <article class="hh-card">
            <div class="hh-card-title-row">
                <div>
                    <h2>Aktueller Content</h2>
                    <p class="hh-muted">Letzte Feed-Beiträge und Cup-Einreichungen auf einen Blick.</p>
                </div>
                <a class="hh-link-button" href="{{ route('admin.content.index') }}">Inhalte verwalten</a>
            </div>
            <div class="hh-dashboard-content-grid">
                <div>
                    <h3>Feed</h3>
                    <div class="hh-dashboard-list">
                        @forelse($latestContent as $post)
                            <a href="{{ route('feed.show', $post) }}" class="hh-dashboard-list-row">
                                <span class="hh-dashboard-avatar">{{ strtoupper(substr($post->user?->name ?? $post->user?->username ?? 'P', 0, 1)) }}</span>
                                <div>
                                    <strong>{{ $post->user?->name ?? $post->user?->username ?? 'Nutzer' }}</strong>
                                    <small>{{ \Illuminate\Support\Str::limit(trim((string) $post->body), 90) ?: 'Medienbeitrag' }}</small>
                                </div>
                            </a>
                        @empty
                            <p class="hh-empty-state">Keine Feed-Beiträge vorhanden.</p>
                        @endforelse
                    </div>
                </div>
                <div>
                    <h3>Cup-Einreichungen</h3>
                    <div class="hh-dashboard-list">
                        @forelse($latestCupSubmissions as $submission)
                            <a href="{{ $submission->cup ? route('cups.show.section', [$submission->cup, 'submissions']) : route('admin.index') }}" class="hh-dashboard-list-row">
                                <span class="hh-dashboard-dot is-{{ in_array($submission->status, ['pending', 'review_required'], true) ? 'red' : 'gold' }}"></span>
                                <div>
                                    <strong>{{ $submission->cup?->title ?? 'Cup' }}</strong>
                                    <small>{{ $submission->submitter?->username ? '@'.$submission->submitter->username : 'Nutzer' }} · {{ $submission->statusLabel() }} · {{ (int) $submission->points }} Punkte</small>
                                </div>
                            </a>
                        @empty
                            <p class="hh-empty-state">Keine Cup-Einreichungen vorhanden.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </article>
    </div>

    <aside class="hh-dashboard-side-column">
        <article class="hh-card hh-dashboard-health-card">
            <h2>System & Aufgaben</h2>
            <div class="hh-dashboard-health-list">
                @foreach($healthItems as $item)
                    <div class="hh-dashboard-health-item is-{{ $item['state'] }}">
                        <div>
                            <strong>{{ number_format((int) $item['value'], 0, ',', '.') }}</strong>
                            <span>{{ $item['label'] }}</span>
                        </div>
                        <small>{{ $item['hint'] }}</small>
                    </div>
                @endforeach
            </div>
        </article>

        <article class="hh-card">
            <div class="hh-card-title-row">
                <div>
                    <h2>Neue Nutzer</h2>
                    <p class="hh-muted">Letzte Registrierungen.</p>
                </div>
                <a class="hh-link-button" href="{{ route('admin.users.index') }}">Verwalten</a>
            </div>
            <div class="hh-dashboard-list">
                @forelse($latestUsers as $user)
                    <a href="{{ route('admin.users.index', ['q' => $user->username ?: $user->email]) }}" class="hh-dashboard-list-row">
                        <img src="{{ $user->avatarUrl() }}" alt="" class="hh-dashboard-user-image">
                        <div>
                            <strong>{{ $user->name ?: $user->username }}</strong>
                            <small>{{ $user->username ? '@'.$user->username : $user->email }} · {{ $user->created_at?->diffForHumans() }}</small>
                        </div>
                    </a>
                @empty
                    <p class="hh-empty-state">Noch keine Nutzer vorhanden.</p>
                @endforelse
            </div>
        </article>

        <article class="hh-card hh-dashboard-roadmap-card">
            <p class="hh-kicker">Nächste Module</p>
            <h2>Roadmap-Ideen</h2>
            <ul>
                <li>Weekly Contracts / HNT-Aufträge</li>
                <li>Cup-Ideenwand mit Voting</li>
                <li>Moment der Woche</li>
                <li>Hunter-Trophäenschrank</li>
                <li>Loadout-Challenges</li>
            </ul>
        </article>
    </aside>
</section>
@endsection
