# Patch 57 – Public Cups + Login Legal Links

## Ziel

Der Cup-Bereich soll für Gäste öffentlich lesbar sein, damit externe Werbung nicht auf eine reine Login-Wand führt. Anmeldung, Einreichungen, Team-/Solo-Registrierung und administrative Aktionen bleiben weiterhin geschützt.

## Geprüfter Bestand

- Login/Register verwenden `resources/views/layouts/auth.blade.php`.
- Dieses Layout enthält bereits `partials.auth-legal-footer`.
- `partials.auth-legal-footer` lädt `partials.legal-footer-links`.
- Damit sind Impressum, Datenschutz, Nutzungsbedingungen, Netiquette und Cookie-Einstellungen auf Login/Register bereits vorhanden.

## Änderungen

- `GET /cups` ist öffentlich.
- `GET /cups/{cup:slug}` ist öffentlich.
- `GET /hall-of-fame` ist öffentlich.
- Cup-Erstellung, Bearbeitung, Löschung, Anmeldung, Join-Link, Screenshot-Einreichung, Screenshot-Auslieferung und Review-Aktionen bleiben in der `auth`-Gruppe.
- Gäste sehen auf Cup-Detailseiten eine Registrierung-/Login-CTA statt eines POST-Formulars.
- Private Submission-Details werden Gästen nicht angezeigt.
- `robots.txt` blockiert Cups/Hall of Fame nicht mehr pauschal, blockiert aber private Cup-Aktionen.
- `sitemap.xml` enthält `/cups` und `/hall-of-fame`.

## Keine Migration

Dieser Patch benötigt keine Migration.

## Test

- Als Gast `/login` öffnen: Legal-Links müssen sichtbar sein.
- Als Gast `/cups` öffnen: Seite muss ohne Login laden.
- Als Gast `/cups/{slug}` öffnen: öffentliche Cup-Infos, Regeln, Preise und Leaderboard müssen sichtbar sein.
- Als Gast darf kein Ergebnis-Upload-Formular erscheinen.
- Als Gast Klick auf Teilnahme-CTA führt zu Register/Login.
- Als eingeloggter Nutzer bleiben Anmeldung und Einreichung möglich.
- Admin bleibt für Create/Edit/Delete erforderlich.
