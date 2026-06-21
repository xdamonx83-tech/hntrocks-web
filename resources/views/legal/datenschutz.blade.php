@extends('themes.hnt_preview.layouts.app')

@section('robots', 'index,follow')

@section('title', 'Datenschutzerklärung')
@section('meta_description', 'Datenschutzerklärung für hnt.rocks mit Informationen zu Account, Uploads, Social Login, Tracking, Moderation und Community-Funktionen.')

@section('app_window_class', 'hnt-legal-window')
@section('main_class', 'hnt-legal-main')
@section('right_sidebar')@endsection

@section('content')
@php
    $hhLegalTitle = 'Datenschutzerklärung';
    $hhLegalPretitle = 'Datenschutz';
    $hhLegalSummary = 'Informationen zur Verarbeitung personenbezogener Daten, Cookies, Social Login, Uploads, Cups und Community-Funktionen.';
    $hhLegalUpdated = '10.05.2026';
    $hhLegalActiveRoute = 'legal.datenschutz';
    $hhLegalContactLabel = 'Datenschutzkontakt';
    $hhLegalContactHref = 'mailto:datenschutz@hnt.rocks';
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
<h2>1. Verantwortlicher</h2>
            <p>
                Verantwortlich für die Datenverarbeitung auf hnt.rocks ist:<br>
                Christian Müller<br>
                Nelseestr. 15<br>
                63739 Aschaffenburg<br>
                Deutschland<br>
                E-Mail: <a href="mailto:datenschutz@hnt.rocks">datenschutz@hnt.rocks</a>
            </p>

            <h2>2. Allgemeines</h2>
            <p>
                hnt.rocks ist eine Community-Plattform rund um Hunt: Showdown. Die Plattform verarbeitet personenbezogene Daten, wenn du die Webseite besuchst, einen Account erstellst, dich einloggst, Inhalte veröffentlichst, Medien hochlädst, an Cups teilnimmst, Meldungen einreichst oder mit anderen Nutzern interagierst.
            </p>
            <p>
                Personenbezogene Daten werden nur verarbeitet, soweit dies für Betrieb, Sicherheit, Community-Funktionen, Moderation, Cup-Durchführung, gesetzliche Pflichten oder auf Grundlage deiner Einwilligung erforderlich ist.
            </p>

            <h2>3. Hosting, Domain und Server-Logs</h2>
            <p>
                hnt.rocks wird auf einem eigenen Server betrieben. Nach aktuellem Setup wird der Server bei IONOS betrieben; die Domain/DNS-Verwaltung erfolgt über STRATO. Zur technischen Bereitstellung, Sicherheit, Fehleranalyse und Missbrauchsabwehr können Server-Logdaten verarbeitet werden, insbesondere IP-Adresse, Datum und Uhrzeit, angefragte URL, Referrer, Browser-/Geräteinformationen, übertragene Datenmenge und Statuscodes.
            </p>
            <p>
                Rechtsgrundlage ist unser berechtigtes Interesse an einem sicheren und stabilen Betrieb der Plattform sowie, soweit erforderlich, die Erfüllung gesetzlicher Pflichten.
            </p>

            <h2>4. Registrierung, Login und Account</h2>
            <p>
                Für die Nutzung vieler Funktionen werden Accountdaten verarbeitet, insbesondere Name, Nutzername, E-Mail-Adresse, Passwort-Hash, Profilangaben, Avatar, Titelbild, Spracheinstellungen, Plattformangaben, Gamertags, Sichtbarkeitseinstellungen, Sicherheitsereignisse, Login-Zeitpunkte und technische Accountdaten.
            </p>
            <p>
                Passwörter werden nicht im Klartext gespeichert, sondern als Hash. Pflichtangaben sind nur solche Daten, die für Registrierung, Login, Sicherheit und Betrieb des Accounts erforderlich sind.
            </p>

            <h2>5. Social Login</h2>
            <p>
                hnt.rocks kann Social Login über Google, Discord, Twitch, Steam, Microsoft und Facebook anbieten, soweit die jeweiligen Anbieter im System aktiviert und sichtbar sind. Beim Login über einen Anbieter werden je nach Anbieter insbesondere Provider-ID, Name/Nickname, E-Mail-Adresse, Avatar-URL und technische Profildaten verarbeitet. Social-Login-Verknüpfungen können mit bestehenden Accounts verbunden werden, wenn dies technisch und sicherheitsseitig zulässig ist, insbesondere bei übereinstimmender oder verifizierter E-Mail-Adresse.
            </p>
            <p>
                Die jeweiligen Anbieter verarbeiten Daten eigenverantwortlich nach ihren eigenen Datenschutzbedingungen. Wenn du Social Login nutzt, gelten zusätzlich die Datenschutz- und Nutzungsbedingungen des jeweiligen Anbieters.
            </p>

            <h2>6. Cookies, Sessions, CSRF und Remember-Me</h2>
            <p>
                hnt.rocks nutzt technisch notwendige Cookies und Session-Daten, um Login, Sicherheit, CSRF-Schutz, Spracheinstellungen und Formularfunktionen bereitzustellen. Diese Cookies sind für den Betrieb der Plattform erforderlich. Eine Remember-Me-Funktion kann den Login länger aufrechterhalten, wenn sie genutzt wird. Nach aktuellem Stand werden Schriften und Icon-Fonts lokal vom hnt.rocks-Server ausgeliefert; dafür ist keine Verbindung zu Google Fonts oder einem externen Icon-CDN erforderlich.
            </p>
            <p>
                Nicht notwendiges Visitor Tracking wird erst nach deiner Zustimmung im Cookie-Banner aktiviert. Du kannst deine Auswahl jederzeit über den Link „Cookie-Einstellungen“ im Footer ändern.
            </p>

            <h2>7. Visitor Tracking / Analytics</h2>
            <p>
                Zur internen Reichweitenmessung kann hnt.rocks pseudonyme Besuchsereignisse erfassen. Dabei werden nach aktuellem Stand keine Roh-IP-Adressen im Tracking gespeichert, sondern Hashwerte und technische Metadaten wie Seitenpfad, Referrer-Host, Quelle, Session-Hash, User-Agent-Hash und – falls technisch bereitgestellt – Landcode/Landname.
            </p>
            <p>
                Das persistente Besucher-Cookie <code>hh_vid</code> wird nur gesetzt, wenn der Analytics-Kategorie zugestimmt wurde. Ohne Zustimmung wird kein nicht notwendiges Visitor-Tracking-Cookie gesetzt.
            </p>
            <p>
                Für interne Kampagnenlinks wie <code>/go/...</code> kann hnt.rocks reine Klickzahlen erfassen, um den Erfolg eigener Beiträge oder Kampagnen nachvollziehen zu können. Dabei werden nach aktuellem Stand keine Roh-IP-Adressen, keine User-Agent-Daten, keine Cookies und keine personenbezogenen Einzelprofile gespeichert, sondern nur der angeklickte Kampagnenlink, ein Zeitstempel und aggregierte Zähler.
            </p>

            <h2>8. Profile, Feed, Kommentare, Mentions, LFG und Teams</h2>
            <p>
                Wenn du Inhalte erstellst, verarbeiten wir die eingegebenen Texte, Medien, Reaktionen, Kommentare, Mentions, Teamdaten, LFG-Beiträge, Bewerbungen und Sichtbarkeitseinstellungen. Je nach gewählter Sichtbarkeit können Inhalte für andere angemeldete Nutzer oder öffentlich sichtbar sein.
            </p>
            <p>
                Inhalte können mit deinem Nutzernamen, Avatar, Profilinformationen und Zeitstempeln verbunden angezeigt werden. Du solltest keine privaten Daten veröffentlichen, die nicht für andere Nutzer bestimmt sind.
            </p>
            <p>
                Für Feed-Beiträge und Kommentare kann hnt.rocks eine optionale Übersetzungsfunktion zwischen Deutsch und Englisch anbieten. Eine Übersetzung wird erst auf Nutzeraktion angefordert und kann anschließend gespeichert werden, damit derselbe Inhalt nicht mehrfach an einen Übersetzungsdienst übermittelt werden muss. Soweit die Funktion aktiviert ist, können die zu übersetzenden Inhalte an einen KI-/Übersetzungsdienstleister wie OpenAI übermittelt werden. Das Original bleibt weiterhin maßgeblich sichtbar; es erfolgt keine automatische Übersetzung beim Laden des Feeds.
            </p>

            <h2>9. Nachrichten und Benachrichtigungen</h2>
            <p>
                Private Nachrichten, Systembenachrichtigungen und Benachrichtigungseinstellungen werden verarbeitet, um Kommunikation innerhalb der Plattform zu ermöglichen. Nach aktuellem Stand wurden In-App-Benachrichtigungen gefunden. Browser-Push oder externe Push-Dienste müssen zusätzlich beschrieben werden, falls sie später aktiviert werden.
            </p>

            <h2>10. Uploads, Medien und KI-/Upload-Scanning</h2>
            <p>
                Bei Uploads werden Dateien, Dateityp, Größe, Speicherpfad, Sichtbarkeit, Kontext, Prüfergebnisse und technische Metadaten verarbeitet. Zur Erkennung von NSFW-Inhalten, Hass/Rassismus, verbotenen Symbolen, problematischen Texten oder Manipulationen können Texte, Bilder, Videos oder Cup-Screenshots automatisiert geprüft werden. Die automatisierte Prüfung ersetzt keine endgültige menschliche Entscheidung, insbesondere wenn Inhalte zur manuellen Prüfung markiert werden.
            </p>
            <p>
                Soweit die KI-Prüfung aktiviert ist, können Inhalte an einen KI-/Moderationsdienstleister wie OpenAI übermittelt werden. Die Prüfung dient dem Community-Schutz, der Missbrauchsabwehr, der Moderation und der Integrität von Cups. Inhalte können automatisch abgelehnt, zur manuellen Prüfung markiert oder nachträglich entfernt werden.
            </p>
            <p>
                Bei Feed-Uploads kann hnt.rocks zusätzlich verarbeiten, ob ein Nutzer ein Bild oder Video selbst als KI-generiert oder stark KI-bearbeitet gekennzeichnet hat. Außerdem können Medien automatisiert darauf geprüft werden, ob sie möglicherweise KI-generiert wirken. Solche Systemhinweise dienen der Transparenz und Admin-Prüfung; sie sind keine endgültige forensische Feststellung. Öffentlich sichtbar wird ein KI-Hinweis grundsätzlich nur, wenn der Nutzer den Inhalt selbst so markiert oder ein Administrator den Hinweis bestätigt.
            </p>

            <h2>11. Reports, Rechtsverletzungen und Moderation</h2>
            <p>
                Nutzer können Inhalte und Profile melden. Dabei werden gemeldeter Inhalt, Grund, Beschreibung, meldender Nutzer, betroffener Nutzer, Status, Moderator und Zeitpunkte verarbeitet. Meldungen können auch per E-Mail an <a href="mailto:abuse@hnt.rocks">abuse@hnt.rocks</a> erfolgen.
            </p>
            <p>
                Moderatoren und Administratoren können Inhalte prüfen, Status ändern und Maßnahmen wie Ausblendung, Löschung, Sperrung, Einschränkung, Verwarnung oder Ausschluss von Cups vorbereiten oder durchführen. Diese Verarbeitung dient der Sicherheit der Plattform, der Durchsetzung der Regeln und der Bearbeitung mutmaßlich rechtswidriger Inhalte.
            </p>

            <h2>12. Cups, Preise und Hall of Fame</h2>
            <p>
                Für Cups werden Teilnahmen, Einsendungen, Screenshots, KI-Auswertungen, Ergebnisse, Ranglisten, Preise und Gewinner verarbeitet. Gewinner und Top-Platzierungen können in der Hall of Fame mit Nutzername, Platzierung, Cup, Badge und Ergebnis angezeigt werden. Für die Preisabwicklung können Plattform, Kontaktmöglichkeit und regionale Informationen erforderlich sein. Eine Barauszahlung ist nicht vorgesehen; Plattform-Guthaben oder Sachpreise können je nach Plattform und Region eingeschränkt sein.
            </p>
            <p>
                Cup-Screenshots können aus Datenschutz- und Sicherheitsgründen privat gespeichert und nur über geschützte Zugriffswege bereitgestellt werden. Zur Fairness- und Missbrauchskontrolle kann der im Screenshot sichtbare Gamertag aus dem Blutlinien-/Profilbereich automatisiert ausgelesen und mit späteren Einreichungen desselben Cup-Teilnehmers verglichen werden. Abweichungen oder unsichere Erkennungen können zur manuellen Prüfung markiert werden. Manipulationsprüfungen und Moderationsentscheidungen können dokumentiert werden, um Cup-Ergebnisse nachvollziehbar zu halten.
            </p>

            <h2>13. Kontaktaufnahme und E-Mail</h2>
            <p>
                Wenn du uns kontaktierst, verarbeiten wir deine Angaben zur Bearbeitung der Anfrage. Dazu gehören insbesondere Name, E-Mail-Adresse, Inhalt der Nachricht und technische Begleitdaten. Für Systemmails, Passwort-Reset, Kontakt- oder Moderationsmails können Mailserverdaten verarbeitet werden.
            </p>

            <h2>14. Account-Löschung, Datenexport und Aufbewahrung</h2>
            <p>
                Im Accountbereich können Nutzer einen Datenexport anfordern und eine Account-Löschung beantragen. Nach aktueller Systemlogik kann eine Löschung mit einer 14-tägigen Widerrufsfrist vorgemerkt werden. Nach Ablauf der Frist können Accountdaten gelöscht oder anonymisiert werden, soweit keine gesetzlichen Pflichten, Sicherheitsinteressen, Missbrauchsnachweise, offene Vorgänge, Cup-Dokumentationen oder berechtigte Interessen entgegenstehen.
            </p>
            <p>
                Bestimmte Inhalte können nach Account-Löschung anonymisiert fortbestehen, wenn dies für Diskussionsverläufe, Rankings, Moderationsnachweise, Sicherheitslogs oder rechtliche Nachvollziehbarkeit erforderlich ist.
            </p>

            <h2>15. Rechtsgrundlagen</h2>
            <p>
                Die Verarbeitung erfolgt je nach Funktion auf Grundlage von Art. 6 Abs. 1 lit. b DSGVO zur Bereitstellung des Accounts und der Plattformfunktionen, Art. 6 Abs. 1 lit. c DSGVO zur Erfüllung rechtlicher Pflichten, Art. 6 Abs. 1 lit. f DSGVO aufgrund berechtigter Interessen an Sicherheit, Moderation, Missbrauchsabwehr und Plattformbetrieb oder Art. 6 Abs. 1 lit. a DSGVO auf Grundlage deiner Einwilligung, etwa bei nicht notwendigem Tracking.
            </p>

            <h2>16. Empfänger, Dienstleister und Drittanbieter</h2>
            <p>
                Daten können an technische Dienstleister und Drittanbieter übermittelt werden, insbesondere Hosting-Anbieter, Domain-/DNS-Anbieter, E-Mail-Dienstleister, Social-Login-Anbieter und KI-/Moderationsdienstleister. Soweit erforderlich, werden Auftragsverarbeitungsverträge oder andere geeignete Datenschutzmechanismen genutzt.
            </p>
            <p>
                Bei Social Login und KI-/Moderationsdiensten können Daten in Drittländer übermittelt werden. In diesen Fällen achten wir auf geeignete Garantien, soweit diese gesetzlich erforderlich sind.
            </p>

            <h2>17. Speicherdauer</h2>
            <p>
                Personenbezogene Daten werden gelöscht oder anonymisiert, wenn sie für die jeweiligen Zwecke nicht mehr erforderlich sind und keine gesetzlichen Aufbewahrungspflichten oder berechtigten Interessen entgegenstehen. Accountdaten bestehen grundsätzlich bis zur Löschung des Accounts. Sicherheitslogs, Moderationsnachweise und Cup-Dokumentationen können länger gespeichert werden, soweit dies zur Missbrauchsabwehr, Rechtsverteidigung oder Nachvollziehbarkeit erforderlich ist.
            </p>

            <h2>18. Deine Rechte</h2>
            <p>
                Du hast im Rahmen der DSGVO insbesondere Rechte auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung, Datenübertragbarkeit, Widerspruch und Widerruf erteilter Einwilligungen. Zur Ausübung deiner Rechte kannst du dich an <a href="mailto:datenschutz@hnt.rocks">datenschutz@hnt.rocks</a> wenden.
            </p>

            <h2>19. Beschwerderecht bei einer Aufsichtsbehörde</h2>
            <p>
                Du hast das Recht, dich bei einer Datenschutzaufsichtsbehörde zu beschweren. Für Bayern ist regelmäßig das Bayerische Landesamt für Datenschutzaufsicht zuständig, sofern keine andere Aufsichtsbehörde zuständig ist.
            </p>

            <h2>20. Cookie-Einstellungen ändern</h2>
            <p>
                Du kannst deine Cookie-Auswahl jederzeit über den Link „Cookie-Einstellungen“ im Footer ändern.
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
