@extends('admin.layouts.app')

@section('title', 'Dashboard · HNT-ACP')
@section('admin_heading', 'Dashboard')
@section('body_class', 'hnt-acp-dashboard')

@push('head')
<link rel="stylesheet" href="{{ asset('assets/admin/hnt-acp-demo6-dashboard.css') }}?v=1">
@endpush

@section('content')
@php
    $memberGrowth = $memberGrowth ?? ['currentTotal' => 0, 'previousTotal' => 0, 'growthPercent' => 0, 'periodLabel' => '', 'daily' => [], 'monthly' => []];
    $topActiveMembers = collect($topActiveMembers ?? []);
    $usersTotal = (int) ($stats['users'] ?? 0);
    $activeUsers = (int) ($dashboardCards[1]['value'] ?? 0);
    $activeRate = $usersTotal > 0 ? min(100, (int) round(($activeUsers / $usersTotal) * 100)) : 0;
    $growth = (int) ($memberGrowth['growthPercent'] ?? 0);
    $growthPositive = $growth >= 0;
    $maxWeekly = max(1, collect($weeklySeries ?? [])->max('total') ?: 1);
    $kpiIcons = ['profile-2user', 'profile-2user', 'danger', 'message-question'];
@endphp

<script id="hntAcpMemberGrowthData" type="application/json">@json($memberGrowth)</script>

<div class="hnt-d6-dashboard">
    @if(session('status'))
        <div class="hnt-acp-alert">{{ session('status') }}</div>
    @endif

    <section class="hnt-d6-kpis" aria-label="Dashboard Kennzahlen">
        @foreach($dashboardCards as $index => $card)
            <a class="hnt-d6-kpi" href="{{ $card['route'] }}">
                <div class="hnt-d6-kpi-head">
                    <span class="hnt-d6-kpi-label">{{ $card['label'] }}</span>
                    <span class="hnt-d6-kpi-icon">
                        <svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#{{ $kpiIcons[$index] ?? 'category' }}"></use></svg>
                    </span>
                </div>
                <div class="hnt-d6-kpi-value">
                    <strong>{{ number_format((int) $card['value'], 0, ',', '.') }}</strong>
                    <small>{{ $card['meta'] }}</small>
                </div>
            </a>
        @endforeach
    </section>

    <section class="hnt-d6-grid-main">
        <article class="hnt-d6-card hnt-d6-growth-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading">
                    <h2>Mitgliederentwicklung</h2>
                    <p>{{ $memberGrowth['periodLabel'] ?? 'Letzte 30 Tage' }}</p>
                </div>
                <div class="hnt-d6-growth-tools">
                    <div class="hnt-d6-growth-legend" aria-label="Legende">
                        <span><i class="is-previous"></i> Vorperiode</span>
                        <span><i class="is-current"></i> Aktuell</span>
                    </div>
                    <div class="hnt-acp-segmented" role="group" aria-label="Diagrammzeitraum">
                        <button type="button" class="is-active" data-growth-mode="daily">Täglich</button>
                        <button type="button" data-growth-mode="monthly">Monatlich</button>
                    </div>
                </div>
            </header>

            <div class="hnt-d6-card-body">
                <div class="hnt-acp-growth-chart" aria-label="Mitgliederzuwachs Diagramm">
                    <svg viewBox="0 0 760 225" role="img" aria-labelledby="hntGrowthTitle hntGrowthDesc">
                        <title id="hntGrowthTitle">Neue Mitglieder</title>
                        <desc id="hntGrowthDesc">Vergleich der Registrierungen zwischen aktueller und vorheriger Periode.</desc>
                        <defs>
                            <linearGradient id="hntAcpPreviousArea" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#858C96" stop-opacity=".18" />
                                <stop offset="100%" stop-color="#858C96" stop-opacity="0" />
                            </linearGradient>
                            <linearGradient id="hntAcpCurrentArea" x1="0" x2="0" y1="0" y2="1">
                                <stop offset="0%" stop-color="#E07A5F" stop-opacity=".24" />
                                <stop offset="100%" stop-color="#E07A5F" stop-opacity="0" />
                            </linearGradient>
                        </defs>
                        <g data-growth-grid></g>
                        <path data-growth-area="previous" fill="url(#hntAcpPreviousArea)"></path>
                        <path data-growth-area="current" fill="url(#hntAcpCurrentArea)"></path>
                        <path data-growth-line="previous" class="hnt-acp-line is-previous"></path>
                        <path data-growth-line="current" class="hnt-acp-line is-current"></path>
                        <g data-growth-points></g>
                        <g data-growth-labels></g>
                    </svg>
                    <div class="hnt-acp-chart-tooltip" data-growth-tooltip hidden></div>
                </div>

                <div class="hnt-d6-growth-summary">
                    <strong>{{ number_format((int) ($memberGrowth['currentTotal'] ?? 0), 0, ',', '.') }}</strong>
                    <span @class(['hnt-acp-trend', 'is-up' => $growthPositive, 'is-down' => ! $growthPositive])>
                        {{ $growthPositive ? '↑' : '↓' }} {{ number_format(abs($growth), 0, ',', '.') }}%
                    </span>
                    <p>neue Mitglieder in den letzten 30 Tagen</p>
                </div>
            </div>
        </article>

        <aside class="hnt-d6-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading">
                    <h2>Highlights</h2>
                    <p>Moderation & System</p>
                </div>
                <a class="hnt-d6-card-link" href="{{ route('admin.reports.index') }}">Alle anzeigen</a>
            </header>

            <div class="hnt-d6-highlight-list">
                <a class="hnt-d6-highlight" href="{{ route('admin.reports.index') }}">
                    <span class="hnt-d6-highlight-icon is-danger"><svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#danger"></use></svg></span>
                    <span class="hnt-d6-highlight-copy"><strong>Offene Reports</strong><small>Moderationsfälle</small></span>
                    <span class="hnt-d6-highlight-value">
                        <b>{{ number_format((int) ($stats['open_reports'] ?? 0), 0, ',', '.') }}</b>
                        <em @class(['hnt-d6-status-chip', 'is-danger' => ($stats['open_reports'] ?? 0) > 0])>{{ ($stats['open_reports'] ?? 0) > 0 ? 'Prüfen' : 'OK' }}</em>
                    </span>
                </a>

                <a class="hnt-d6-highlight" href="{{ route('admin.cup-feedback.index') }}">
                    <span class="hnt-d6-highlight-icon is-warning"><svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#message-question"></use></svg></span>
                    <span class="hnt-d6-highlight-copy"><strong>Cup-Feedback</strong><small>Neu / in Prüfung</small></span>
                    <span class="hnt-d6-highlight-value"><b>{{ number_format((int) ($stats['cup_feedback'] ?? 0), 0, ',', '.') }}</b></span>
                </a>

                <a class="hnt-d6-highlight" href="{{ route('admin.content.index', ['section' => 'cup-submissions']) }}">
                    <span class="hnt-d6-highlight-icon is-success"><svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#cup"></use></svg></span>
                    <span class="hnt-d6-highlight-copy"><strong>Cup-Einreichungen</strong><small>Warten auf Prüfung</small></span>
                    <span class="hnt-d6-highlight-value"><b>{{ number_format((int) ($stats['pending_cup_submissions'] ?? 0), 0, ',', '.') }}</b></span>
                </a>

                <a class="hnt-d6-highlight" href="{{ route('admin.users.index', ['status' => 'suspended']) }}">
                    <span class="hnt-d6-highlight-icon is-accent"><svg aria-hidden="true"><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#profile-2user"></use></svg></span>
                    <span class="hnt-d6-highlight-copy"><strong>Gesperrte Nutzer</strong><small>Account-Status suspended</small></span>
                    <span class="hnt-d6-highlight-value"><b>{{ number_format((int) ($stats['suspended_users'] ?? 0), 0, ',', '.') }}</b></span>
                </a>
            </div>

            <div class="hnt-d6-rate">
                <div class="hnt-d6-rate-head">
                    <span>Aktivitätsrate</span>
                    <strong>{{ number_format($activeRate, 0, ',', '.') }}%</strong>
                </div>
                <div class="hnt-d6-rate-track"><span style="width: {{ $activeRate }}%"></span></div>
                <p>{{ number_format($activeUsers, 0, ',', '.') }} aktive Nutzer in den letzten 7 Tagen von {{ number_format($usersTotal, 0, ',', '.') }} insgesamt.</p>
            </div>
        </aside>
    </section>

    <section class="hnt-d6-grid-secondary">
        <article class="hnt-d6-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading">
                    <h2>Top Mitglieder</h2>
                    <p>Aktivität der letzten 30 Tage</p>
                </div>
                <a class="hnt-d6-card-link" href="{{ route('admin.users.index') }}">Nutzer öffnen</a>
            </header>

            <div class="hnt-d6-member-list">
                @forelse($topActiveMembers as $entry)
                    @php($member = $entry['user'])
                    <a class="hnt-d6-member-row" href="{{ route('admin.users.index', ['q' => $member->username ?: $member->email]) }}">
                        <img src="{{ $member->avatarUrl() }}" alt="" loading="lazy">
                        <span class="hnt-d6-member-copy">
                            <strong>{{ $member->name ?: $member->username }}</strong>
                            <small>{{ $entry['posts'] }} Posts · {{ $entry['comments'] }} Kommentare · {{ $entry['reactions'] }} Reaktionen</small>
                        </span>
                        <span class="hnt-d6-progress"><span style="width: {{ $entry['percent'] }}%"></span></span>
                        <span class="hnt-d6-member-score">{{ $entry['actions'] }}</span>
                    </a>
                @empty
                    <p class="hnt-d6-empty">Noch keine Aktivität in den letzten 30 Tagen.</p>
                @endforelse
            </div>
        </article>

        <article class="hnt-d6-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading">
                    <h2>Plattform</h2>
                    <p>Aktueller Datenstand</p>
                </div>
            </header>

            <div class="hnt-d6-platform-grid">
                <a class="hnt-d6-platform-stat" href="{{ route('admin.content.index') }}">
                    <strong>{{ number_format((int) ($stats['feed_posts'] ?? 0), 0, ',', '.') }}</strong>
                    <span>Feed Posts · +{{ number_format((int) ($healthItems[2]['value'] ?? 0), 0, ',', '.') }} / 7T</span>
                </a>
                <a class="hnt-d6-platform-stat" href="{{ route('admin.content.index', ['section' => 'moments']) }}">
                    <strong>{{ number_format((int) ($stats['moments'] ?? 0), 0, ',', '.') }}</strong>
                    <span>Moments · +{{ number_format((int) ($healthItems[3]['value'] ?? 0), 0, ',', '.') }} / 7T</span>
                </a>
                <div class="hnt-d6-platform-stat">
                    <strong>{{ number_format((int) ($stats['teams'] ?? 0), 0, ',', '.') }}</strong>
                    <span>Teams</span>
                </div>
                <div class="hnt-d6-platform-stat">
                    <strong>{{ number_format((int) ($stats['lfg_posts'] ?? 0), 0, ',', '.') }}</strong>
                    <span>LFG Posts</span>
                </div>
                <div class="hnt-d6-platform-stat">
                    <strong>{{ number_format((int) ($stats['media_assets'] ?? 0), 0, ',', '.') }}</strong>
                    <span>Medien</span>
                </div>
                <a class="hnt-d6-platform-stat" href="{{ route('admin.gamification.index') }}">
                    <strong>{{ number_format((int) ($stats['badges'] ?? 0), 0, ',', '.') }}</strong>
                    <span>Badges · {{ number_format((int) ($stats['active_quests'] ?? 0), 0, ',', '.') }} aktive Quests</span>
                </a>
            </div>
        </article>
    </section>

    <section class="hnt-d6-card">
        <header class="hnt-d6-card-header">
            <div class="hnt-d6-card-heading">
                <h2>Plattform-Aktivität</h2>
                <p>Posts, Kommentare und Registrierungen zusammengefasst</p>
            </div>
            <span class="hnt-d6-status-chip">Letzte 7 Tage</span>
        </header>
        <div class="hnt-d6-week">
            <div class="hnt-d6-week-bars">
                @foreach($weeklySeries as $day)
                    @php($height = max(8, (int) round(((int) $day['total'] / $maxWeekly) * 100)))
                    <div class="hnt-d6-week-day">
                        <div class="hnt-d6-week-bar"><span style="height: {{ $height }}%"></span></div>
                        <strong>{{ $day['label'] }}</strong>
                        <small>{{ $day['total'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="hnt-d6-feed-grid">
        <article class="hnt-d6-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading"><h2>Neue Reports</h2><p>Moderation mit Handlungsbedarf</p></div>
                <a class="hnt-d6-card-link" href="{{ route('admin.reports.index') }}">Alle</a>
            </header>
            <div class="hnt-d6-feed-list">
                @forelse($latestReports as $report)
                    <a class="hnt-d6-feed-row" href="{{ route('admin.reports.index', ['status' => $report->status]) }}">
                        <span class="hnt-d6-feed-dot is-danger"></span>
                        <span class="hnt-d6-feed-copy">
                            <strong>{{ $report->reportableLabel() }} · {{ $report->reasonLabel() }}</strong>
                            <small>{{ $report->statusLabel() }} · {{ $report->reporter?->username ? '@'.$report->reporter->username : 'System' }} · {{ $report->created_at?->diffForHumans() }}</small>
                        </span>
                    </a>
                @empty
                    <p class="hnt-d6-empty">Keine Reports vorhanden.</p>
                @endforelse
            </div>
        </article>

        <article class="hnt-d6-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading"><h2>Neue Mitglieder</h2><p>Letzte Registrierungen</p></div>
                <a class="hnt-d6-card-link" href="{{ route('admin.users.index') }}">Alle</a>
            </header>
            <div class="hnt-d6-feed-list">
                @forelse($latestUsers as $user)
                    <a class="hnt-d6-feed-row" href="{{ route('admin.users.index', ['q' => $user->username ?: $user->email]) }}">
                        <img src="{{ $user->avatarUrl() }}" alt="" loading="lazy">
                        <span class="hnt-d6-feed-copy">
                            <strong>{{ $user->name ?: $user->username }}</strong>
                            <small>{{ $user->username ? '@'.$user->username : $user->email }} · {{ $user->created_at?->diffForHumans() }}</small>
                        </span>
                    </a>
                @empty
                    <p class="hnt-d6-empty">Noch keine Nutzer vorhanden.</p>
                @endforelse
            </div>
        </article>

        <article class="hnt-d6-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading"><h2>Aktueller Content</h2><p>Letzte Feed-Beiträge</p></div>
                <a class="hnt-d6-card-link" href="{{ route('admin.content.index') }}">Alle</a>
            </header>
            <div class="hnt-d6-feed-list">
                @forelse($latestContent as $post)
                    <a class="hnt-d6-feed-row" href="{{ route('feed.show', $post) }}">
                        <img src="{{ $post->user?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg') }}" alt="" loading="lazy">
                        <span class="hnt-d6-feed-copy">
                            <strong>{{ $post->user?->name ?? $post->user?->username ?? 'Nutzer' }}</strong>
                            <small>{{ \Illuminate\Support\Str::limit(trim((string) $post->body), 78) ?: 'Medienbeitrag' }}</small>
                        </span>
                    </a>
                @empty
                    <p class="hnt-d6-empty">Keine Feed-Beiträge vorhanden.</p>
                @endforelse
            </div>
        </article>

        <article class="hnt-d6-card">
            <header class="hnt-d6-card-header">
                <div class="hnt-d6-card-heading"><h2>Cup-Einreichungen</h2><p>Zuletzt eingereichte Ergebnisse</p></div>
                <a class="hnt-d6-card-link" href="{{ route('admin.content.index', ['section' => 'cup-submissions']) }}">Übersicht</a>
            </header>
            <div class="hnt-d6-feed-list">
                @forelse($latestCupSubmissions as $submission)
                    <a class="hnt-d6-feed-row" href="{{ $submission->cup ? route('cups.show.section', [$submission->cup, 'submissions']) : route('admin.index') }}">
                        <span class="hnt-d6-feed-dot is-warning"></span>
                        <span class="hnt-d6-feed-copy">
                            <strong>{{ $submission->cup?->title ?? 'Cup' }}</strong>
                            <small>{{ $submission->submitter?->username ? '@'.$submission->submitter->username : 'Nutzer' }} · {{ $submission->statusLabel() }} · {{ (int) $submission->points }} Punkte</small>
                        </span>
                    </a>
                @empty
                    <p class="hnt-d6-empty">Keine Cup-Einreichungen vorhanden.</p>
                @endforelse
            </div>
        </article>
    </section>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/hnt-acp-dashboard.js') }}?v=2" defer></script>
@endpush