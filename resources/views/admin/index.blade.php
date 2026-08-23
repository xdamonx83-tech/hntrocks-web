@extends('admin.layouts.app')

@section('title', 'Dashboard · HNT-ACP')
@section('admin_heading', 'Dashboard')
@section('body_class', 'hnt-acp-dashboard')

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
@endphp

<script id="hntAcpMemberGrowthData" type="application/json">@json($memberGrowth)</script>

@if(session('status'))
    <div class="hnt-acp-alert">{{ session('status') }}</div>
@endif

<section class="hnt-acp-dashboard-grid">
    <div class="hnt-acp-dashboard-main">
        <article class="hnt-acp-panel hnt-acp-growth-panel">
            <div class="hnt-acp-panel-head hnt-acp-growth-head">
                <div>
                    <h2>Mitglieder</h2>
                    <p>{{ $memberGrowth['periodLabel'] ?? 'Letzte 30 Tage' }}</p>
                </div>
                <div class="hnt-acp-growth-controls">
                    <div class="hnt-acp-chart-legend" aria-label="Legende">
                        <span><i class="is-previous"></i> Vorperiode</span>
                        <span><i class="is-current"></i> Aktuell</span>
                    </div>
                    <div class="hnt-acp-segmented" role="group" aria-label="Diagrammzeitraum">
                        <button type="button" class="is-active" data-growth-mode="daily">Daily</button>
                        <button type="button" data-growth-mode="monthly">Monthly</button>
                    </div>
                </div>
            </div>

            <div class="hnt-acp-growth-chart" aria-label="Mitgliederzuwachs Diagramm">
                <svg viewBox="0 0 760 225" role="img" aria-labelledby="hntGrowthTitle hntGrowthDesc">
                    <title id="hntGrowthTitle">Neue Mitglieder</title>
                    <desc id="hntGrowthDesc">Vergleich der Registrierungen zwischen aktueller und vorheriger Periode.</desc>
                    <defs>
                        <linearGradient id="hntAcpBlueArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#246CF9" stop-opacity=".32" />
                            <stop offset="100%" stop-color="#246CF9" stop-opacity="0" />
                        </linearGradient>
                        <linearGradient id="hntAcpPinkArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#FA2256" stop-opacity=".28" />
                            <stop offset="100%" stop-color="#FA2256" stop-opacity="0" />
                        </linearGradient>
                        <filter id="hntAcpBlueGlow" x="-20%" y="-50%" width="140%" height="200%">
                            <feGaussianBlur stdDeviation="8" result="blur" />
                            <feMerge><feMergeNode in="blur" /><feMergeNode in="SourceGraphic" /></feMerge>
                        </filter>
                        <filter id="hntAcpPinkGlow" x="-20%" y="-50%" width="140%" height="200%">
                            <feGaussianBlur stdDeviation="8" result="blur" />
                            <feMerge><feMergeNode in="blur" /><feMergeNode in="SourceGraphic" /></feMerge>
                        </filter>
                    </defs>
                    <g data-growth-grid></g>
                    <path data-growth-area="previous" fill="url(#hntAcpBlueArea)"></path>
                    <path data-growth-area="current" fill="url(#hntAcpPinkArea)"></path>
                    <path data-growth-line="previous" class="hnt-acp-line is-previous" filter="url(#hntAcpBlueGlow)"></path>
                    <path data-growth-line="current" class="hnt-acp-line is-current" filter="url(#hntAcpPinkGlow)"></path>
                    <g data-growth-points></g>
                    <g data-growth-labels></g>
                </svg>
                <div class="hnt-acp-chart-tooltip" data-growth-tooltip hidden></div>
            </div>

            <div class="hnt-acp-growth-summary">
                <strong>{{ number_format((int) ($memberGrowth['currentTotal'] ?? 0), 0, ',', '.') }}</strong>
                <span @class(['hnt-acp-trend', 'is-up' => $growthPositive, 'is-down' => ! $growthPositive])>
                    {{ $growthPositive ? '↑' : '↓' }} {{ number_format(abs($growth), 0, ',', '.') }}%
                </span>
                <p>Neue Mitglieder in den letzten 30 Tagen</p>
            </div>
        </article>

        <div class="hnt-acp-lower-grid">
            <div class="hnt-acp-mini-stack">
                <a class="hnt-acp-mini-card is-blue" href="{{ route('admin.content.index') }}">
                    <div><span>Feed Posts</span><strong>{{ number_format((int) ($stats['feed_posts'] ?? 0), 0, ',', '.') }}</strong><small>+{{ number_format((int) ($healthItems[2]['value'] ?? 0), 0, ',', '.') }} diese Woche</small></div>
                    <div class="hnt-acp-mini-ring" style="--ring-value: {{ min(100, (int) round(((int) ($healthItems[2]['value'] ?? 0) / max(1, (int) ($stats['feed_posts'] ?? 1))) * 1000)) }}"><span></span></div>
                </a>
                <a class="hnt-acp-mini-card is-pink" href="{{ route('admin.content.index', ['section' => 'moments']) }}">
                    <div><span>Moments</span><strong>{{ number_format((int) ($stats['moments'] ?? 0), 0, ',', '.') }}</strong><small>+{{ number_format((int) ($healthItems[3]['value'] ?? 0), 0, ',', '.') }} diese Woche</small></div>
                    <div class="hnt-acp-mini-ring" style="--ring-value: {{ min(100, (int) round(((int) ($healthItems[3]['value'] ?? 0) / max(1, (int) ($stats['moments'] ?? 1))) * 1000)) }}"><span></span></div>
                </a>
                <a class="hnt-acp-mini-card is-green" href="{{ route('admin.users.index') }}">
                    <div><span>Aktive Nutzer</span><strong>{{ number_format($activeUsers, 0, ',', '.') }}</strong><small>{{ number_format((int) ($healthItems[1]['value'] ?? 0), 0, ',', '.') }} heute neu</small></div>
                    <div class="hnt-acp-mini-ring" style="--ring-value: {{ $activeRate }}"><span></span></div>
                </a>
            </div>

            <article class="hnt-acp-panel hnt-acp-top-members">
                <div class="hnt-acp-panel-head hnt-acp-list-title">
                    <div>
                        <h2>Top Mitglieder</h2>
                        <p>Aktivität der letzten 30 Tage</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}">SHOW MORE ›</a>
                </div>
                <div class="hnt-acp-member-list">
                    @forelse($topActiveMembers as $entry)
                        @php($member = $entry['user'])
                        <a class="hnt-acp-member-row" href="{{ route('admin.users.index', ['q' => $member->username ?: $member->email]) }}">
                            <img src="{{ $member->avatarUrl() }}" alt="" loading="lazy">
                            <div class="hnt-acp-member-name">
                                <strong>{{ $member->name ?: $member->username }}</strong>
                                <small>{{ $entry['posts'] }} Posts · {{ $entry['comments'] }} Kommentare · {{ $entry['reactions'] }} Reaktionen</small>
                            </div>
                            <div class="hnt-acp-member-progress"><span style="width: {{ $entry['percent'] }}%"></span></div>
                            <b>{{ $entry['percent'] }}%</b>
                            <em>{{ $entry['actions'] }}</em>
                        </a>
                    @empty
                        <p class="hnt-acp-empty">Noch keine Aktivität in den letzten 30 Tagen.</p>
                    @endforelse
                </div>
            </article>
        </div>
    </div>

    <aside class="hnt-acp-dashboard-rail">
        <article class="hnt-acp-panel hnt-acp-rail-panel">
            <div class="hnt-acp-panel-head hnt-acp-rail-heading">
                <h2>Moderation & System</h2>
                <a href="{{ route('admin.reports.index') }}">SEE ALL ›</a>
            </div>
            <div class="hnt-acp-status-list">
                <a href="{{ route('admin.reports.index') }}"><span class="hnt-acp-status-icon is-red"><svg><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#danger"></use></svg></span><strong>Offene Reports</strong><em>{{ number_format((int) ($stats['open_reports'] ?? 0), 0, ',', '.') }}</em><b class="is-red">{{ ($stats['open_reports'] ?? 0) > 0 ? 'Prüfen' : 'OK' }}</b></a>
                <a href="{{ route('admin.cup-feedback.index') }}"><span class="hnt-acp-status-icon is-gold"><svg><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#message-question"></use></svg></span><strong>Cup-Feedback</strong><em>{{ number_format((int) ($stats['cup_feedback'] ?? 0), 0, ',', '.') }}</em><b>offen</b></a>
                <a href="{{ route('admin.content.index') }}"><span class="hnt-acp-status-icon is-blue"><svg><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#document-text"></use></svg></span><strong>Feed Posts</strong><em>{{ number_format((int) ($stats['feed_posts'] ?? 0), 0, ',', '.') }}</em><b>{{ number_format((int) ($healthItems[2]['value'] ?? 0), 0, ',', '.') }} / 7T</b></a>
                <a href="{{ route('admin.users.index', ['status' => 'suspended']) }}"><span class="hnt-acp-status-icon is-pink"><svg><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#profile-2user"></use></svg></span><strong>Gesperrt</strong><em>{{ number_format((int) ($stats['suspended_users'] ?? 0), 0, ',', '.') }}</em><b>Nutzer</b></a>
                <a href="{{ route('admin.content.index', ['section' => 'cup-submissions']) }}"><span class="hnt-acp-status-icon is-green"><svg><use href="{{ asset('assets/admin/iconsax-acp.svg') }}#cup"></use></svg></span><strong>Cup-Einreichungen</strong><em>{{ number_format((int) ($stats['pending_cup_submissions'] ?? 0), 0, ',', '.') }}</em><b>pending</b></a>
            </div>

            <div class="hnt-acp-rail-divider"></div>

            <div class="hnt-acp-rail-subhead"><span>Plattform</span><small>LIVE DATA</small></div>
            <div class="hnt-acp-quick-grid">
                <div><strong>{{ number_format((int) ($stats['teams'] ?? 0), 0, ',', '.') }}</strong><span>Teams</span></div>
                <div><strong>{{ number_format((int) ($stats['lfg_posts'] ?? 0), 0, ',', '.') }}</strong><span>LFG</span></div>
                <div><strong>{{ number_format((int) ($stats['media_assets'] ?? 0), 0, ',', '.') }}</strong><span>Medien</span></div>
                <div><strong>{{ number_format((int) ($stats['badges'] ?? 0), 0, ',', '.') }}</strong><span>Badges</span></div>
            </div>

            <div class="hnt-acp-rail-divider"></div>

            <div class="hnt-acp-gauge-wrap">
                <h3>Aktivitätsrate</h3>
                <svg class="hnt-acp-gauge" viewBox="0 0 140 86" role="img" aria-label="{{ $activeRate }} Prozent aktive Nutzer">
                    <path d="M15 70 A55 55 0 0 1 125 70" pathLength="100" class="hnt-acp-gauge-track"></path>
                    <path d="M15 70 A55 55 0 0 1 125 70" pathLength="100" class="hnt-acp-gauge-value" style="stroke-dasharray: {{ $activeRate }} 100"></path>
                </svg>
                <strong>{{ number_format($activeRate, 0, ',', '.') }}%</strong>
                <span>Aktive Nutzer / Gesamt</span>
            </div>
        </article>
    </aside>
</section>

<section class="hnt-acp-section-block">
    <div class="hnt-acp-section-heading">
        <div><h2>Aktuelle Plattform-Aktivität</h2><p>Die bestehenden Admin-Daten bleiben vollständig erreichbar.</p></div>
        <span>LETZTE 7 TAGE</span>
    </div>
    <div class="hnt-acp-week-strip">
        @foreach($weeklySeries as $day)
            @php($height = max(8, (int) round(((int) $day['total'] / $maxWeekly) * 100)))
            <div><div class="hnt-acp-week-bar"><span style="height: {{ $height }}%"></span></div><strong>{{ $day['label'] }}</strong><small>{{ $day['total'] }}</small></div>
        @endforeach
    </div>
</section>

<section class="hnt-acp-activity-grid">
    <article class="hnt-acp-panel">
        <div class="hnt-acp-panel-head hnt-acp-list-title"><div><h2>Neue Reports</h2><p>Moderation mit Handlungsbedarf.</p></div><a href="{{ route('admin.reports.index') }}">ALLE ›</a></div>
        <div class="hnt-acp-feed-list">
            @forelse($latestReports as $report)
                <a href="{{ route('admin.reports.index', ['status' => $report->status]) }}"><span class="hnt-acp-feed-dot is-red"></span><div><strong>{{ $report->reportableLabel() }} · {{ $report->reasonLabel() }}</strong><small>{{ $report->statusLabel() }} · {{ $report->reporter?->username ? '@'.$report->reporter->username : 'System' }} · {{ $report->created_at?->diffForHumans() }}</small></div></a>
            @empty<p class="hnt-acp-empty">Keine Reports vorhanden.</p>@endforelse
        </div>
    </article>

    <article class="hnt-acp-panel">
        <div class="hnt-acp-panel-head hnt-acp-list-title"><div><h2>Neue Mitglieder</h2><p>Letzte Registrierungen.</p></div><a href="{{ route('admin.users.index') }}">ALLE ›</a></div>
        <div class="hnt-acp-feed-list">
            @forelse($latestUsers as $user)
                <a href="{{ route('admin.users.index', ['q' => $user->username ?: $user->email]) }}"><img src="{{ $user->avatarUrl() }}" alt=""><div><strong>{{ $user->name ?: $user->username }}</strong><small>{{ $user->username ? '@'.$user->username : $user->email }} · {{ $user->created_at?->diffForHumans() }}</small></div></a>
            @empty<p class="hnt-acp-empty">Noch keine Nutzer vorhanden.</p>@endforelse
        </div>
    </article>

    <article class="hnt-acp-panel">
        <div class="hnt-acp-panel-head hnt-acp-list-title"><div><h2>Aktueller Content</h2><p>Letzte Feed-Beiträge.</p></div><a href="{{ route('admin.content.index') }}">ALLE ›</a></div>
        <div class="hnt-acp-feed-list">
            @forelse($latestContent as $post)
                <a href="{{ route('feed.show', $post) }}"><img src="{{ $post->user?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg') }}" alt=""><div><strong>{{ $post->user?->name ?? $post->user?->username ?? 'Nutzer' }}</strong><small>{{ \Illuminate\Support\Str::limit(trim((string) $post->body), 78) ?: 'Medienbeitrag' }}</small></div></a>
            @empty<p class="hnt-acp-empty">Keine Feed-Beiträge vorhanden.</p>@endforelse
        </div>
    </article>

    <article class="hnt-acp-panel">
        <div class="hnt-acp-panel-head hnt-acp-list-title"><div><h2>Cup-Einreichungen</h2><p>Zuletzt eingereichte Ergebnisse.</p></div><a href="{{ route('admin.content.index', ['section' => 'cup-submissions']) }}">ÜBERSICHT ›</a></div>
        <div class="hnt-acp-feed-list">
            @forelse($latestCupSubmissions as $submission)
                <a href="{{ $submission->cup ? route('cups.show.section', [$submission->cup, 'submissions']) : route('admin.index') }}"><span class="hnt-acp-feed-dot is-gold"></span><div><strong>{{ $submission->cup?->title ?? 'Cup' }}</strong><small>{{ $submission->submitter?->username ? '@'.$submission->submitter->username : 'Nutzer' }} · {{ $submission->statusLabel() }} · {{ (int) $submission->points }} Punkte</small></div></a>
            @empty<p class="hnt-acp-empty">Keine Cup-Einreichungen vorhanden.</p>@endforelse
        </div>
    </article>
</section>
@endsection

@push('scripts')
<script src="{{ asset('assets/admin/hnt-acp-dashboard.js') }}?v=1" defer></script>
@endpush
