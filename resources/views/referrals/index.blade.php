@extends('layouts.app')

@section('title', 'Referrals / Gewinnspiele · hnt.rocks')

@section('content')
<div class="hh-page-header">
    <div>
        <p class="hh-kicker">Einladen &amp; Chancen sammeln</p>
        <h1>Referrals / Gewinnspiele</h1>
        <p>Dein persönlicher Invite-Link, Referral-Tracking und die erste Gewinnspiel-Chancenlogik.</p>
    </div>
    <div class="hh-page-header-meta">
        <strong>{{ $summary['total'] }}</strong>
        <span>Chancen</span>
    </div>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<section class="hh-referral-grid">
    <article class="hh-card hh-card-compact hh-referral-link-card">
        <p class="hh-kicker">Dein Referral-Link</p>
        <h2>Freunde einladen</h2>
        <p>Jeder Nutzer bekommt einen eigenen Link. Registrierungen und vollständig gepflegte Profile werden für zukünftige Gewinnspiele vorbereitet.</p>

        <div class="hh-copy-box">
            <input type="text" value="{{ $referralUrl }}" readonly onclick="this.select()">
            <a class="hh-primary-button" href="{{ $referralUrl }}" target="_blank" rel="noopener">Öffnen</a>
        </div>

        <dl class="hh-mini-list hh-section-space-small">
            <div><dt>Klicks</dt><dd>{{ number_format((int) $link->clicks_count, 0, ',', '.') }}</dd></div>
            <div><dt>Registrierungen</dt><dd>{{ number_format((int) $link->signups_count, 0, ',', '.') }}</dd></div>
            <div><dt>Vollständige Profile</dt><dd>{{ number_format((int) $link->completed_profiles_count, 0, ',', '.') }}</dd></div>
            <div><dt>Erfolgreiche Bonus-Invites</dt><dd>{{ number_format((int) $successfulReferrals, 0, ',', '.') }}</dd></div>
        </dl>
    </article>

    <article class="hh-card hh-card-compact hh-giveaway-card">
        <p class="hh-kicker">Gewinnspiel</p>
        @if ($giveaway)
            <h2>{{ $giveaway->title }}</h2>
            <p>{{ $giveaway->description }}</p>
            <div class="hh-chance-total">{{ $summary['total'] }}</div>
            <p class="hh-muted">Aktuelle persönliche Chancen.</p>
            <dl class="hh-mini-list">
                <div><dt>Registrierung</dt><dd>+{{ $summary['base'] }}</dd></div>
                <div><dt>Profil vollständig</dt><dd>+{{ $summary['profile'] }}</dd></div>
                <div><dt>Erfolgreicher Invite</dt><dd>+{{ $summary['referral'] }}</dd></div>
            </dl>
        @else
            <h2>Kein aktives Gewinnspiel</h2>
            <p>Die Referral-Grundlage ist aktiv. Sobald ein Gewinnspiel im System aktiv ist, werden Chancen automatisch berechnet.</p>
        @endif
    </article>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <p class="hh-kicker">Regellogik</p>
            <h2>Aktuelle Chancenberechnung</h2>
        </div>
    </div>

    <div class="hh-badge-grid hh-referral-rules">
        <article class="hh-badge-card">
            <div class="hh-badge-icon">1</div>
            <div>
                <h3>Registrierung</h3>
                <p>Jeder registrierte Nutzer erhält eine Basis-Chance.</p>
                <span>{{ $summary['base'] > 0 ? 'Aktiv' : 'Noch offen' }}</span>
            </div>
        </article>
        <article class="hh-badge-card {{ $summary['profile'] <= 0 ? 'is-locked' : '' }}">
            <div class="hh-badge-icon">+</div>
            <div>
                <h3>100%-Profil</h3>
                <p>Ein vollständig gepflegtes Profil gibt eine Bonus-Chance.</p>
                <span>{{ $summary['profile'] > 0 ? 'Erreicht' : \App\Support\ProfileCompletion::score(auth()->user()).'% vollständig' }}</span>
            </div>
        </article>
        <article class="hh-badge-card {{ $summary['referral'] <= 0 ? 'is-locked' : '' }}">
            <div class="hh-badge-icon">↗</div>
            <div>
                <h3>Erfolgreicher Invite</h3>
                <p>Wenn ein eingeladener Nutzer sein Profil vollständig pflegt, zählt das als erfolgreicher Invite.</p>
                <span>{{ $summary['referral'] > 0 ? 'Bonus aktiv' : 'Noch offen' }}</span>
            </div>
        </article>
    </div>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <p class="hh-kicker">Referral-Verlauf</p>
            <h2>Letzte Registrierungen über deinen Link</h2>
        </div>
        <span>{{ $recentSignups->count() }}</span>
    </div>

    <div class="hh-referral-list">
        @forelse ($recentSignups as $signup)
            <article>
                <div>
                    <strong>{{ $signup->referredUser?->name ?? 'Gelöschter Nutzer' }}</strong>
                    <p>{{ $signup->profile_completed_at ? 'Profil vollständig · Bonusfähig' : 'Registriert · Profil noch nicht vollständig' }}</p>
                </div>
                <small>{{ $signup->registered_at?->diffForHumans() ?? $signup->created_at?->diffForHumans() }}</small>
            </article>
        @empty
            <p>Noch keine Registrierungen über deinen Referral-Link.</p>
        @endforelse
    </div>
</section>
@endsection
