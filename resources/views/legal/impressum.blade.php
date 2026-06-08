@extends('themes.hnt_preview.layouts.app')

@section('title', 'Impressum')
@section('meta_description', 'Impressum, Anbieterkennzeichnung und Kontaktangaben von hnt.rocks.')

@section('app_window_class', 'hnt-legal-window')
@section('main_class', 'hnt-legal-main')
@section('right_sidebar')@endsection

@section('content')
@php
    $hhLegalTitle = 'Impressum';
    $hhLegalPretitle = 'Legal';
    $hhLegalSummary = 'Anbieterkennzeichnung, Kontaktwege und rechtliche Hinweise zu HNT.rocks.';
    $hhLegalUpdated = '08.05.2026';
    $hhLegalActiveRoute = 'legal.impressum';
    $hhLegalContactLabel = 'Kontakt';
    $hhLegalContactHref = 'mailto:kontakt@hnt.rocks';
    $hhLegalContactText = 'kontakt@hnt.rocks';
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
        <article class="box hh-legal-article">
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
<h2>Angaben gemäß § 5 DDG</h2>
            <p>
                <strong>Betreiber / Diensteanbieter:</strong><br>
                Christian Müller<br>
                Nelseestr. 15<br>
                63739 Aschaffenburg<br>
                Deutschland
            </p>

            <h2>Kontakt</h2>
            <p>
                E-Mail: <a href="mailto:kontakt@hnt.rocks">kontakt@hnt.rocks</a>
            </p>

            <h2>Kontakt für Rechtsverletzungen und Missbrauch</h2>
            <p>
                Hinweise auf rechtswidrige Inhalte, Urheberrechtsverletzungen, Persönlichkeitsrechtsverletzungen, Missbrauch, Spam, Betrug oder sonstige schwerwiegende Regelverstöße können über die Report-Funktion auf hnt.rocks oder per E-Mail gemeldet werden:
                <a href="mailto:abuse@hnt.rocks">abuse@hnt.rocks</a>
            </p>
            <p>
                Eine Meldung sollte möglichst die betroffene URL, den betroffenen Inhalt, den Grund der Meldung und eine kurze Begründung enthalten. hnt.rocks prüft Meldungen nach Eingang und kann Inhalte vorläufig ausblenden, entfernen oder Accounts einschränken, wenn dies erforderlich erscheint.
            </p>

            <h2>Datenschutzkontakt</h2>
            <p>
                Datenschutzanfragen können an folgende Adresse gerichtet werden:<br>
                <a href="mailto:datenschutz@hnt.rocks">datenschutz@hnt.rocks</a>
            </p>

            <h2>Verantwortlich für redaktionelle Inhalte</h2>
            <p>
                Verantwortlich nach § 18 Abs. 2 MStV, soweit redaktionelle Inhalte angeboten werden:<br>
                Christian Müller<br>
                Nelseestr. 15<br>
                63739 Aschaffenburg<br>
                Deutschland
            </p>

            <h2>Hinweis zu Hunt: Showdown</h2>
            <p>
                hnt.rocks ist ein unabhängiges Community-Projekt und kein offizielles Angebot von Crytek. Hunt: Showdown sowie dazugehörige Marken, Namen, Grafiken und Inhalte gehören den jeweiligen Rechteinhabern. Eine Verbindung, Partnerschaft, Billigung oder Unterstützung durch Crytek besteht nicht, sofern dies nicht ausdrücklich angegeben wird.
            </p>

            <h2>Haftung für eigene Inhalte</h2>
            <p>
                Die eigenen Inhalte auf hnt.rocks werden mit Sorgfalt erstellt und gepflegt. Für Vollständigkeit, Richtigkeit und Aktualität kann jedoch keine Gewähr übernommen werden. Gesetzliche Haftungsregelungen bleiben unberührt.
            </p>

            <h2>Haftung für Nutzerinhalte</h2>
            <p>
                hnt.rocks ermöglicht Nutzern, eigene Inhalte wie Beiträge, Kommentare, Profile, Medien, LFG-Einträge, Teamangaben und Cup-Einsendungen zu veröffentlichen. Für diese Inhalte sind grundsätzlich die jeweiligen Nutzer verantwortlich. Sobald konkrete Hinweise auf rechtswidrige Inhalte bekannt werden, werden diese geprüft und bei Bedarf entfernt oder gesperrt.
            </p>

            <h2>Haftung für externe Links</h2>
            <p>
                hnt.rocks kann Links zu externen Webseiten enthalten. Auf deren Inhalte hat hnt.rocks keinen Einfluss. Für Inhalte externer Seiten sind ausschließlich deren Betreiber verantwortlich. Bei Bekanntwerden rechtswidriger Inhalte werden entsprechende Links geprüft und gegebenenfalls entfernt.
            </p>
            </div>
        </article>

        <aside class="hh-legal-sidebar">
            <div class="box hh-legal-sidebox">
                <h2>Rechtliches</h2>
                <nav class="hh-legal-nav" aria-label="Rechtliche Seiten">
                    @foreach($hhLegalNav as $item)
                        <a href="{{ route($item['route']) }}" class="{{ $item['route'] === $hhLegalActiveRoute ? 'is-active' : '' }}">
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>

            <div class="box hh-legal-sidebox hh-legal-contactbox">
                <h2>{{ $hhLegalContactLabel }}</h2>
                <p>Für Fragen oder Meldungen zu dieser Seite kannst du uns direkt erreichen.</p>
                <a href="{{ $hhLegalContactHref }}">{{ $hhLegalContactText }}</a>
            </div>
        </aside>
    </div>
</div>
@endsection
