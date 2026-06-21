@extends('themes.hnt_preview.layouts.app')

@section('robots', 'index,follow')

@section('title', 'Sicherheitsstandards zum Schutz von Kindern / Child Safety Standards')
@section('meta_description', 'Veröffentlichte hnt.rocks Sicherheitsstandards gegen sexuellen Missbrauch und sexuelle Ausbeutung von Kindern (CSAE/CSAM).')
@section('app_window_class', 'hnt-legal-window')
@section('main_class', 'hnt-legal-main')
@section('right_sidebar')@endsection

@section('content')
@php
    $hhLegalTitle = 'Sicherheitsstandards zum Schutz von Kindern';
    $hhLegalPretitle = 'Safety & Compliance';
    $hhLegalSummary = 'Null-Toleranz-Regeln gegen CSAE/CSAM, Meldewege und Sicherheitsmaßnahmen auf HNT.rocks.';
    $hhLegalUpdated = '18.05.2026';
    $hhLegalActiveRoute = 'legal.child_safety';
    $hhLegalContactLabel = 'Sicherheitsmeldung';
    $hhLegalContactHref = 'mailto:abuse@hnt.rocks?subject=Child%20Safety%20HNT.rocks';
    $hhLegalContactText = 'abuse@hnt.rocks';
    $hhLegalNav = [
        ['label' => 'Impressum', 'route' => 'legal.impressum'],
        ['label' => 'Datenschutz', 'route' => 'legal.datenschutz'],
        ['label' => 'Nutzungsbedingungen', 'route' => 'legal.nutzungsbedingungen'],
        ['label' => 'Netiquette', 'route' => 'legal.netiquette'],
        ['label' => 'Konto löschen', 'route' => 'legal.account_deletion'],
        ['label' => 'Jugendschutz', 'route' => 'legal.child_safety'],
    ];
@endphp

<div class="hh-legal-shell">
    <div class="hh-legal-layout">
        <article class="hh-legal-article">
            <header class="hh-legal-hero">
                <div class="hh-legal-hero-kicker">{{ $hhLegalPretitle }}</div>
                <h1>{{ $hhLegalTitle }}</h1>
                <p>{{ $hhLegalSummary }}</p>
                <div class="hh-legal-meta">
                    <span>HNT.rocks</span>
                    <span>Stand: {{ $hhLegalUpdated }}</span>
                </div>
            </header>

            <div class="hh-legal-content">
<p><strong>Stand:</strong> 18.05.2026</p>

            <p>
                HNT.rocks ist eine Community-Plattform für erwachsene Nutzer. Auch wenn sich HNT.rocks nicht an Kinder richtet, gilt auf HNT.rocks eine Null-Toleranz-Politik gegenüber sexuellem Missbrauch und sexueller Ausbeutung von Kindern.
                Diese Seite veröffentlicht die Sicherheitsstandards von HNT.rocks gegen Child Sexual Abuse and Exploitation (CSAE) und Child Sexual Abuse Material (CSAM).
            </p>

            <h2>1. Null Toleranz gegenüber CSAE und CSAM</h2>
            <p>
                Auf HNT.rocks sind Inhalte, Nachrichten, Profile, Uploads, Kommentare, Links oder sonstige Handlungen verboten, die sexuellen Missbrauch, sexuelle Ausbeutung oder Gefährdung von Kindern darstellen, fördern, anbahnen, verharmlosen oder unterstützen.
            </p>
            <p>
                Das Verbot umfasst insbesondere CSAM, Grooming, Sextortion, sexuelle Kontaktanbahnung zu Minderjährigen, Aufforderungen zur Erstellung oder Weitergabe sexueller Inhalte Minderjähriger, sexualisierte Darstellung Minderjähriger sowie jede sonstige Form der sexuellen Ausbeutung oder Gefährdung von Kindern.
            </p>

            <h2>2. Meldemöglichkeiten in der App</h2>
            <p>
                Nutzer können problematische Inhalte und Nutzer über die in HNT.rocks vorhandenen Melde- und Moderationsfunktionen melden, soweit diese im jeweiligen Bereich verfügbar sind. Dazu gehören insbesondere Community-Inhalte wie Beiträge, Kommentare, Profile, Medien, Nachrichten, Team-/LFG-Inhalte oder vergleichbare nutzergenerierte Inhalte.
            </p>
            <p>
                Zusätzlich können dringende Sicherheitsmeldungen jederzeit per E-Mail an <a href="mailto:abuse@hnt.rocks?subject=Child%20Safety%20Report%20HNT.rocks">abuse@hnt.rocks</a> gesendet werden. Meldungen sollten möglichst konkrete Informationen enthalten, zum Beispiel betroffene URL, Nutzername, Screenshot, Zeitpunkt und eine kurze Beschreibung.
            </p>

            <h2>3. Prüfung und Maßnahmen</h2>
            <p>
                Gemeldete oder erkannte Inhalte können geprüft, entfernt, gesperrt oder eingeschränkt werden. Accounts können verwarnt, eingeschränkt, vorläufig gesperrt oder dauerhaft ausgeschlossen werden. Bei schwerwiegenden Verstößen kann HNT.rocks relevante Informationen sichern und an zuständige Behörden oder Meldestellen weitergeben, soweit dies gesetzlich erforderlich oder zulässig ist.
            </p>
            <p>
                Inhalte oder Verhalten, die auf CSAE oder CSAM hindeuten, werden priorisiert behandelt. HNT.rocks kann außerdem technische Schutzmaßnahmen, Moderationsprüfungen, Upload-Einschränkungen und weitere Sicherheitsmaßnahmen einsetzen, um Missbrauch zu verhindern oder einzudämmen.
            </p>

            <h2>4. Zusammenarbeit mit Behörden und gesetzlichen Pflichten</h2>
            <p>
                HNT.rocks verpflichtet sich, anwendbare Gesetze zum Schutz von Kindern einzuhalten. Wenn HNT.rocks tatsächliche Kenntnis von illegalen Inhalten oder Handlungen erhält, können geeignete Maßnahmen bis hin zur Meldung an zuständige Strafverfolgungsbehörden, Plattformen, Hosting-Provider oder anerkannte Meldestellen erfolgen.
            </p>

            <h2>5. Schutz vor Wiederholung und Missbrauch</h2>
            <p>
                Um Nutzer und die Community zu schützen, kann HNT.rocks bei Verstößen Inhalte löschen, Konten sperren, Uploads entfernen, Meldungen dokumentieren, Sicherheitslogs prüfen und Maßnahmen gegen Umgehungsversuche ergreifen. Dies dient dem Schutz von Nutzern, der Rechtsdurchsetzung und der Verhinderung erneuter Verstöße.
            </p>

            <h2>6. Kontakt für Kindersicherheit</h2>
            <p>
                Der Kontakt für Meldungen und Rückfragen zu Kindersicherheit, CSAE und CSAM lautet:
                <a href="mailto:abuse@hnt.rocks?subject=Child%20Safety%20HNT.rocks">abuse@hnt.rocks</a>.
            </p>
            <p>
                Dieser Kontakt ist für Sicherheitsmeldungen vorgesehen. Allgemeine Datenschutzanfragen gehen an <a href="mailto:datenschutz@hnt.rocks">datenschutz@hnt.rocks</a>; allgemeine Supportanfragen an <a href="mailto:kontakt@hnt.rocks">kontakt@hnt.rocks</a>.
            </p>

            <h2>7. English summary</h2>
            <p>
                HNT.rocks has a zero-tolerance policy against Child Sexual Abuse and Exploitation (CSAE) and Child Sexual Abuse Material (CSAM). Content, messages, profiles, uploads, links or behavior that exploit, endanger, sexualize or facilitate abuse of minors are prohibited.
            </p>
            <p>
                Users can report safety concerns through available in-app reporting and moderation tools. Urgent child safety, CSAE or CSAM reports can also be sent to <a href="mailto:abuse@hnt.rocks?subject=Child%20Safety%20Report%20HNT.rocks">abuse@hnt.rocks</a>. HNT.rocks may remove content, restrict or ban accounts, preserve relevant information and report serious violations to competent authorities or reporting bodies where legally required or appropriate.
            </p>

            <h2>8. Weitere Regeln</h2>
            <p>
                Ergänzend gelten die <a href="{{ route('legal.nutzungsbedingungen') }}">Nutzungsbedingungen</a>, die <a href="{{ route('legal.netiquette') }}">Netiquette</a> und die <a href="{{ route('legal.datenschutz') }}">Datenschutzerklärung</a> von HNT.rocks.
            </p>
            </div>
        </article>

        <aside class="hh-legal-sidebar">
            <div class="hh-legal-sidebox">
                <h2>Rechtliches</h2>
                <nav class="hh-legal-nav" aria-label="Rechtliche Seiten">
                    @foreach($hhLegalNav as $item)
                        <a href="{{ route($item['route']) }}" class="{{ $item['route'] === $hhLegalActiveRoute ? 'is-active' : '' }}">
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="hh-legal-sidebox hh-legal-contactbox">
                <h2>{{ $hhLegalContactLabel }}</h2>
                <p>Dringende Sicherheitsmeldungen kannst du direkt an den Abuse-Kontakt senden.</p>
                <a href="{{ $hhLegalContactHref }}">{{ $hhLegalContactText }}</a>
            </div>
        </aside>
    </div>
</div>
@endsection
