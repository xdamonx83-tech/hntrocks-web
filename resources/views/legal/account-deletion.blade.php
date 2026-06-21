@extends('themes.hnt_preview.layouts.app')

@section('robots', 'index,follow')

@section('title', 'Konto löschen / Account deletion')
@section('meta_description', 'Öffentliche Informationen zur Löschung eines hnt.rocks Kontos und der zugehörigen personenbezogenen Daten.')

@section('app_window_class', 'hnt-legal-window')
@section('main_class', 'hnt-legal-main')
@section('right_sidebar')@endsection

@section('content')
@php
    $hhLegalTitle = 'Konto löschen';
    $hhLegalPretitle = 'Account & Datenschutz';
    $hhLegalSummary = 'Informationen zur Kontolöschung, Schutzfrist, Identitätsprüfung und Verarbeitung vorhandener Daten.';
    $hhLegalUpdated = '18.05.2026';
    $hhLegalActiveRoute = 'legal.account_deletion';
    $hhLegalContactLabel = 'Löschanfrage';
    $hhLegalContactHref = 'mailto:datenschutz@hnt.rocks?subject=Kontol%C3%B6schung%20hnt.rocks';
    $hhLegalContactText = 'datenschutz@hnt.rocks';
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
<h2>1. Kontolöschung direkt im Konto</h2>
            <p>
                Wenn du Zugriff auf dein hnt.rocks Konto hast, kannst du die Löschung direkt in den Sicherheitseinstellungen vormerken.
                Melde dich dazu an und öffne <a href="{{ route('settings.security.index') }}">Einstellungen &gt; Sicherheit</a>. Dort findest du den Bereich „Kontolöschung“.
            </p>
            <p>
                Die Löschung wird aus Sicherheitsgründen nicht sofort endgültig verarbeitet, sondern mit einer Schutzfrist vorgemerkt. Während dieser Frist kannst du die Löschung im selben Bereich widerrufen.
            </p>

            <h2>2. Löschanfrage ohne Login</h2>
            <p>
                Falls du keinen Zugriff mehr auf dein Konto hast, kannst du die Löschung deines Kontos und der zugehörigen personenbezogenen Daten per E-Mail anfragen.
                Sende deine Anfrage an <a href="mailto:datenschutz@hnt.rocks?subject=Kontol%C3%B6schung%20hnt.rocks">datenschutz@hnt.rocks</a>.
            </p>
            <p>
                Bitte verwende möglichst die E-Mail-Adresse, die mit deinem hnt.rocks Konto verbunden ist. Wenn du dich über Google, Discord, Twitch, Facebook, Microsoft, Steam oder einen anderen Social-Login registriert hast, nutze bitte die dort verwendete E-Mail-Adresse oder beschreibe nachvollziehbar, welchem Konto die Anfrage zugeordnet werden soll.
            </p>
            <p>
                Damit keine fremden Konten gelöscht werden, kann hnt.rocks vor der Bearbeitung eine Identitäts- oder Eigentumsbestätigung verlangen, zum Beispiel durch eine Antwort über die registrierte E-Mail-Adresse.
            </p>
            <p>
                <a class="hh-legal-mail-button" href="mailto:datenschutz@hnt.rocks?subject=Kontol%C3%B6schung%20hnt.rocks">Löschanfrage per E-Mail stellen</a>
            </p>

            <h2>3. Welche Daten werden gelöscht oder anonymisiert?</h2>
            <p>
                Bei einer Kontolöschung werden insbesondere Accountdaten, Login-Daten, Social-Login-Verknüpfungen, Profilinformationen, Uploads und eigene Inhalte gelöscht, anonymisiert oder anderweitig verarbeitet, soweit keine rechtlichen Pflichten oder berechtigten Sicherheitsinteressen entgegenstehen.
            </p>
            <p>
                Dazu können je nach Nutzung insbesondere Feed-Beiträge, Kommentare, Reaktionen, Profilangaben, Team-/LFG-Inhalte, Medien, Nachrichten, Cup-Teilnahmen und technische Accountdaten gehören.
            </p>

            <h2>4. Was kann aufbewahrt werden?</h2>
            <p>
                Einzelne Daten können aus Sicherheits-, Missbrauchsvermeidungs-, Nachweis- oder gesetzlichen Gründen für eine begrenzte Zeit aufbewahrt werden. Das betrifft zum Beispiel Sicherheitslogs, Moderationsnachweise, Missbrauchsmeldungen, Cup-Dokumentationen, offene Supportfälle oder Daten, die zur Rechtsverteidigung erforderlich sind.
            </p>
            <p>
                Inhalte können außerdem anonymisiert fortbestehen, wenn dies für Diskussionsverläufe, Rankings, Moderationsnachweise oder technische Nachvollziehbarkeit erforderlich ist.
            </p>

            <h2>5. Bearbeitungszeit</h2>
            <p>
                Direkte Löschanfragen im eingeloggten Konto werden mit einer Schutzfrist vorgemerkt. Externe Anfragen per E-Mail werden nach Prüfung bearbeitet. Die Bearbeitung erfolgt grundsätzlich so schnell wie möglich und im Rahmen der gesetzlichen Fristen.
            </p>

            <h2>6. English summary</h2>
            <p>
                If you can access your hnt.rocks account, sign in and open <a href="{{ route('settings.security.index') }}">Settings &gt; Security</a> to request account deletion. If you can no longer access your account, send an account deletion request to <a href="mailto:datenschutz@hnt.rocks?subject=Account%20deletion%20hnt.rocks">datenschutz@hnt.rocks</a> using the email address connected to your hnt.rocks account whenever possible.
            </p>
            <p>
                hnt.rocks may ask you to confirm ownership of the account before processing the request. Account data, login data, social login links, profile data, uploads and your own content will be deleted, anonymized or otherwise processed unless legal obligations, security interests, moderation records, abuse prevention, cup documentation or other legitimate reasons require limited retention.
            </p>

            <h2>7. Weitere Informationen</h2>
            <p>
                Weitere Informationen zur Datenverarbeitung findest du in der <a href="{{ route('legal.datenschutz') }}">Datenschutzerklärung</a>. Für allgemeine Datenschutzanfragen kannst du ebenfalls <a href="mailto:datenschutz@hnt.rocks">datenschutz@hnt.rocks</a> kontaktieren.
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
