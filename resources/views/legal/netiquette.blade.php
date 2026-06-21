@extends('themes.hnt_preview.layouts.app')

@section('robots', 'index,follow')

@section('title', 'Netiquette')
@section('meta_description', 'Community-Regeln und Netiquette für hnt.rocks: Fair Play, keine Hassinhalte, keine NSFW-Inhalte, Meldungen und Moderation.')
@section('app_window_class', 'hnt-legal-window')
@section('main_class', 'hnt-legal-main')
@section('right_sidebar')@endsection

@section('content')
@php
    $hhLegalTitle = 'Netiquette';
    $hhLegalPretitle = 'Community';
    $hhLegalSummary = 'Community-Regeln für Fair Play, Uploads, Cups, Meldungen und Moderation auf HNT.rocks.';
    $hhLegalUpdated = '10.05.2026';
    $hhLegalActiveRoute = 'legal.netiquette';
    $hhLegalContactLabel = 'Missbrauch melden';
    $hhLegalContactHref = 'mailto:abuse@hnt.rocks';
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
<p><strong>Stand:</strong> 10.05.2026</p>

            <h2>Grundsatz</h2>
            <p>
                hnt.rocks soll eine faire, erwachsene und respektvolle Community für Hunt-Spieler sein. Harte Matches, Rivalität und klare Meinungen gehören zum Spiel. Persönliche Angriffe, Hass, Manipulation und rechtswidrige Inhalte gehören nicht auf diese Plattform.
            </p>

            <h2>1. Respektvoll bleiben</h2>
            <p>
                Beleidigungen, Drohungen, gezielte Provokation, Belästigung, Bloßstellung, Doxing oder das Nachstellen einzelner Nutzer sind verboten. Kritik an Spielweise, Meinungen oder Inhalten ist erlaubt, persönliche Angriffe nicht.
            </p>

            <h2>2. Kein Rassismus, Hass oder Extremismus</h2>
            <p>
                Rassistische, antisemitische, sexistische, queerfeindliche, behindertenfeindliche, extremistische, volksverhetzende oder sonst menschenverachtende Inhalte sind verboten. Das gilt auch für Codes, Symbole, Andeutungen, Bilder, Profiltexte, Teamnamen und vermeintliche „Witze“.
            </p>

            <h2>3. Keine NSFW- oder illegalen Inhalte</h2>
            <p>
                Pornografie, explizite sexuelle Darstellungen, sexuelle Belästigung, illegale Inhalte, Gewaltverherrlichung außerhalb des Spielkontexts, verbotene Symbole, Schadsoftware, Phishing und Betrug sind nicht erlaubt.
            </p>

            <h2>4. Keine privaten Daten anderer Personen</h2>
            <p>
                Veröffentliche keine privaten Daten anderer Personen ohne Erlaubnis. Dazu gehören insbesondere Adressen, Telefonnummern, private E-Mail-Adressen, echte Namen, Fotos, Chatverläufe oder Accountdaten, wenn die betroffene Person dem nicht zugestimmt hat.
            </p>

            <h2>5. Fair Play bei Cups</h2>
            <p>
                Cheating, manipulierte Screenshots, falsche Angaben, Mehrfachmeldungen, Ergebnisabsprachen, künstliches Boosten, Umgehung von Cooldowns oder Missbrauch der Submission-Funktion führen zum Ausschluss. Cup-Screenshots müssen den originalen Summary-Screen vollständig und unbearbeitet zeigen. Top-Platzierungen können bis zur manuellen Prüfung vorläufig bleiben; Preise können bei Verstößen aberkannt werden.
            </p>

            <h2>6. Uploads und Rechte</h2>
            <p>
                Lade nur Inhalte hoch, die du verwenden darfst. Achte auf Urheberrechte, Markenrechte, Persönlichkeitsrechte und die Privatsphäre anderer Personen. Cup-Screenshots müssen echt, vollständig und zur jeweiligen Teilnahme passend sein.
            </p>
            <p>
                KI-generierte oder stark KI-bearbeitete Medien müssen beim Upload gekennzeichnet werden, wenn sie realistisch wirken, andere Nutzer täuschen könnten oder nicht klar als kreativer/fiktiver Inhalt erkennbar sind. Nicht gekennzeichnete oder irreführende KI-Inhalte können nachträglich markiert, eingeschränkt oder entfernt werden. Die Kennzeichnung ist kein Freifahrtschein für NSFW-Inhalte, Hassinhalte, verbotene Symbole oder Rechteverletzungen.
            </p>

            <h2>7. Spam, Werbung und Scam</h2>
            <p>
                Spam, Scam, Phishing, massenhafte Werbung, irreführende Links, automatisierte Beiträge und Werbung für fragwürdige Dienste sind verboten. Eigenwerbung kann entfernt werden, wenn sie störend, irreführend oder thematisch unpassend ist.
            </p>

            <h2>8. Meldungen richtig nutzen</h2>
            <p>
                Nutze die Report-Funktion oder schreibe an <a href="mailto:abuse@hnt.rocks">abuse@hnt.rocks</a>, wenn du problematische Inhalte siehst. Melde möglichst konkret: betroffene URL, Inhalt, Grund und kurze Beschreibung. Antworte nicht mit Gegenbeleidigungen oder Eskalation.
            </p>

            <h2>9. Moderation und Folgen</h2>
            <p>
                Admins und Moderatoren können Inhalte prüfen, vorläufig ausblenden, löschen, Accounts einschränken, Nutzer verwarnen oder sperren und Cup-Ergebnisse korrigieren oder aberkennen. Entscheidungen richten sich nach Kontext, Schwere, Wiederholung und Risiko für Plattform oder Nutzer.
            </p>

            <h2>10. Missbrauch der Meldefunktion</h2>
            <p>
                Absichtlich falsche, massenhafte oder schikanöse Meldungen sind nicht erlaubt. Die Report-Funktion dient der Sicherheit und Rechtsdurchsetzung, nicht als Werkzeug für persönliche Streitigkeiten.
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
                <p>Problematische Inhalte, Regelverstöße oder Missbrauch kannst du direkt melden.</p>
                <a href="{{ $hhLegalContactHref }}">{{ $hhLegalContactText }}</a>
            </div>
        </aside>
    </div>
</div>
@endsection
