@extends('themes.hnt_preview.layouts.app')

@section('title', 'Nutzungsbedingungen')
@section('meta_description', 'Nutzungsbedingungen für die Nutzung von hnt.rocks, User Content, Uploads, Cups, Meldungen und Moderation.')

@section('app_window_class', 'hnt-legal-window')
@section('main_class', 'hnt-legal-main')
@section('right_sidebar')@endsection

@section('content')
@php
    $hhLegalTitle = 'Nutzungsbedingungen';
    $hhLegalPretitle = 'Regeln';
    $hhLegalSummary = 'Regeln für Account, Inhalte, Uploads, Cups, Fair Play, Meldungen und Moderation auf HNT.rocks.';
    $hhLegalUpdated = '10.05.2026';
    $hhLegalActiveRoute = 'legal.nutzungsbedingungen';
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
<h2>1. Anbieter und Geltungsbereich</h2>
            <p>
                hnt.rocks wird betrieben von Christian Müller, Nelseestr. 15, 63739 Aschaffenburg. Diese Nutzungsbedingungen gelten für die Nutzung von hnt.rocks und aller dazugehörigen Community-Funktionen, insbesondere Feed, Profile, Kommentare, Medien, LFG, Team-LFG, Teams, Cups, Cup-Einsendungen, Rankings und Hall of Fame.
            </p>
            <p>
                Mit der Registrierung oder Nutzung der Plattform akzeptierst du diese Nutzungsbedingungen, die Netiquette und die Datenschutzerklärung. Abweichende Regeln einzelner Cups oder Aktionen gelten ergänzend, wenn sie auf der jeweiligen Seite genannt werden.
            </p>

            <h2>2. Registrierung und Account</h2>
            <p>
                Für viele Funktionen ist ein Account erforderlich. Du bist verpflichtet, Zugangsdaten vertraulich zu behandeln, dein Konto nicht missbräuchlich zu verwenden und keine Accounts anderer Nutzer zu nutzen. Mehrfachaccounts, automatisierte Registrierungen, Fake-Accounts oder Accounts zur Umgehung von Sperren können eingeschränkt oder gelöscht werden.
            </p>
            <p>
                hnt.rocks darf Accounts vorübergehend oder dauerhaft einschränken, sperren oder löschen, wenn ein begründeter Verdacht auf Missbrauch, Manipulation, Sicherheitsrisiken oder erhebliche Regelverstöße besteht.
            </p>

            <h2>3. User Content und Nutzungsrechte</h2>
            <p>
                Du bleibst für alle Inhalte verantwortlich, die du auf hnt.rocks veröffentlichst oder hochlädst. Dazu gehören insbesondere Texte, Kommentare, Profilangaben, Bilder, Videos, Cup-Screenshots, Teamdaten, LFG-Einträge, Reaktionen und Nachrichten.
            </p>
            <p>
                Mit dem Einstellen von Inhalten bestätigst du, dass du die dafür erforderlichen Rechte besitzt und keine Rechte Dritter verletzt. Du räumst hnt.rocks ein einfaches, nicht ausschließliches, räumlich unbeschränktes und technisch notwendiges Nutzungsrecht ein, um deine Inhalte im Rahmen der Plattform zu speichern, zu verarbeiten, zu moderieren, anzuzeigen, zu vervielfältigen, technisch zu konvertieren und anderen Nutzern entsprechend der gewählten Sichtbarkeit zugänglich zu machen.
            </p>
            <p>
                Dieses Nutzungsrecht endet grundsätzlich, wenn der Inhalt gelöscht wird, soweit keine gesetzlichen Aufbewahrungspflichten, Sicherheitsinteressen, Moderationsnachweise, Cup-Dokumentation oder berechtigte Interessen an der Nachvollziehbarkeit entgegenstehen.
            </p>

            <h2>4. Verbotene Inhalte und Handlungen</h2>
            <p>Verboten sind insbesondere:</p>
            <ul>
                <li>rassistische, antisemitische, extremistische, volksverhetzende oder menschenverachtende Inhalte,</li>
                <li>NSFW-Inhalte, Pornografie, sexualisierte Inhalte, explizite Darstellungen oder belästigende Inhalte,</li>
                <li>Beleidigungen, Drohungen, gezielte Belästigung, Doxing, Bloßstellung oder Aufrufe zu Gewalt,</li>
                <li>rechtswidrige Inhalte, Urheberrechtsverletzungen, Markenrechtsverletzungen oder Persönlichkeitsrechtsverletzungen,</li>
                <li>Spam, Scam, Phishing, Schadsoftware, betrügerische Links oder automatisierte Massenaktionen,</li>
                <li>Manipulation von Cups, Screenshots, Ergebnissen, Rankings, Badges, Reports oder Plattformfunktionen,</li>
                <li>Umgehung technischer Schutzmaßnahmen, Sicherheitsprüfungen, Uploadlimits oder Moderationsentscheidungen.</li>
            </ul>

            <h2>5. Uploads, Medien und automatisierte Prüfungen</h2>
            <p>
                Uploads können technisch begrenzt, automatisch geprüft und manuell moderiert werden. Dies kann insbesondere Dateityp, Dateigröße, Sichtbarkeit, NSFW-Erkennung, Hass-/Rassismus-Erkennung, verbotene Symbole, Manipulationshinweise und Cup-relevante Prüfungen betreffen.
            </p>
            <p>
                Inhalte können abgelehnt, ausgeblendet, gelöscht, gesperrt oder zur manuellen Prüfung markiert werden. Es besteht kein Anspruch auf Veröffentlichung, Speicherung oder dauerhafte Verfügbarkeit bestimmter Inhalte.
            </p>

            <h2>6. Meldesystem, Notice-and-Action und Moderation</h2>
            <p>
                Problematische oder rechtswidrige Inhalte können über die Report-Funktion oder per E-Mail an <a href="mailto:abuse@hnt.rocks">abuse@hnt.rocks</a> gemeldet werden. Eine Meldung sollte die betroffene URL, den betroffenen Inhalt, den Meldegrund und eine nachvollziehbare Begründung enthalten.
            </p>
            <p>
                hnt.rocks prüft Meldungen nach pflichtgemäßem Ermessen. Je nach Schwere und Kontext können Inhalte vorläufig ausgeblendet, gelöscht, eingeschränkt oder Accounts verwarnt, eingeschränkt, gesperrt oder gelöscht werden. Offensichtlich missbräuchliche Meldungen können ebenfalls Maßnahmen gegen den meldenden Account auslösen.
            </p>

            <h2>7. Freistellung bei Rechtsverletzungen</h2>
            <p>
                Wenn du schuldhaft Inhalte veröffentlichst oder Handlungen vornimmst, die Rechte Dritter verletzen oder gegen diese Nutzungsbedingungen verstoßen, stellst du hnt.rocks im gesetzlich zulässigen Umfang von daraus entstehenden Ansprüchen Dritter frei. Dies gilt insbesondere für angemessene Kosten der Rechtsverteidigung, soweit du den Verstoß zu vertreten hast. Gesetzliche Verbraucherrechte bleiben unberührt.
            </p>

            <h2>8. Cups, Fair Play und Preise</h2>
            <p>
                Cups können eigene Beschreibungen, Regeln, Scoring-Vorgaben, Teilnahmebedingungen und Preise enthalten. Für den Bayou Blood Cup gilt eine Solo-Wertung, auch wenn Spieler mit Freunden, Teams oder Randoms spielen. Manipulation, Cheating, falsche Screenshots, Mehrfacheinsendungen, falsche Angaben, Umgehungen des Cooldowns oder sonstige Täuschungen können zum Ausschluss und zur Aberkennung von Ergebnissen oder Preisen führen.
            </p>
            <p>
                Preise werden nur nach erfolgreicher Prüfung vergeben. Ranglisten und Top-Platzierungen können bis zum Abschluss einer manuellen Prüfung als vorläufig gelten. Eine Barauszahlung erfolgt nicht, sofern sie nicht ausdrücklich angeboten wird. Plattform-Guthaben kann regionalen Einschränkungen unterliegen. Gewinner müssen zur Preisabwicklung eine geeignete Kontaktmöglichkeit und gegebenenfalls Plattforminformationen angeben.
            </p>
            <p>
                Cups und Preise werden von hnt.rocks veranstaltet, sofern nicht ausdrücklich anders angegeben. Crytek, Sony, Microsoft, Valve, Twitch, Discord oder andere Drittanbieter sind nicht Veranstalter und nicht für Durchführung, Preise oder Support verantwortlich.
            </p>

            <h2>9. Hall of Fame, Rankings und Badges</h2>
            <p>
                Cup-Ergebnisse, Gewinner, Top-Platzierungen, Badges, Nutzernamen, Cup-Namen und Punktestände können in Rankings und in der Hall of Fame angezeigt werden. Die Darstellung dient der Durchführung, Transparenz und Community-Historie der Cups. Bei berechtigten Gründen, technischen Fehlern, Regelverstößen oder unklaren Nachweisen kann hnt.rocks Einträge korrigieren, vorläufig markieren, ausblenden oder entfernen.
            </p>

            <h2>10. Minderjährige</h2>
            <p>
                Die Nutzung von hnt.rocks und die Teilnahme an Cups müssen im Einklang mit den geltenden Altersvorgaben, den Regeln der jeweiligen Plattformen und den Vorgaben des Spiels erfolgen. Minderjährige dürfen Preise nur entgegennehmen, soweit dies rechtlich zulässig ist und erforderliche Zustimmungen vorliegen.
            </p>

            <h2>11. Verfügbarkeit, Änderungen und Beendigung</h2>
            <p>
                hnt.rocks wird ohne Garantie auf ständige Verfügbarkeit bereitgestellt. Funktionen können jederzeit geändert, eingeschränkt oder entfernt werden, insbesondere aus Sicherheitsgründen, zur Missbrauchsabwehr, wegen Wartung, aus rechtlichen Gründen oder zur Weiterentwicklung der Plattform.
            </p>

            <h2>12. Haftung</h2>
            <p>
                hnt.rocks haftet nach den gesetzlichen Vorschriften. Die Haftung für Vorsatz, grobe Fahrlässigkeit, Schäden aus der Verletzung von Leben, Körper oder Gesundheit sowie zwingende gesetzliche Haftung bleibt unberührt. Für von Nutzern veröffentlichte Inhalte sind grundsätzlich die jeweiligen Nutzer verantwortlich. hnt.rocks haftet für Nutzerinhalte erst ab Kenntnis und nur im Rahmen der gesetzlichen Vorschriften.
            </p>

            <h2>13. Externe Dienste und Links</h2>
            <p>
                hnt.rocks kann Links zu externen Seiten oder Dienste Dritter enthalten. Für deren Inhalte, Datenschutz und Verfügbarkeit sind die jeweiligen Anbieter verantwortlich, soweit hnt.rocks diese nicht selbst kontrolliert.
            </p>

            <h2>14. Kein offizielles Crytek-Angebot</h2>
            <p>
                hnt.rocks ist ein unabhängiges Community-Projekt. Es besteht keine offizielle Verbindung zu Crytek oder Hunt: Showdown, sofern dies nicht ausdrücklich angegeben wird.
            </p>

            <h2>15. Änderungen dieser Bedingungen</h2>
            <p>
                hnt.rocks kann diese Nutzungsbedingungen ändern, wenn dies aus rechtlichen, technischen oder organisatorischen Gründen erforderlich ist. Bei wesentlichen Änderungen werden Nutzer in geeigneter Weise informiert.
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
