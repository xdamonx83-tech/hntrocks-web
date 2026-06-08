# Patch 55 – Private Cup-Screenshots

## Ziel

Cup-Submission-Screenshots sollen nicht mehr direkt über den öffentlichen Storage-Link abrufbar sein. Neue Cup-Screenshots werden deshalb standardmäßig auf dem privaten Laravel-Disk gespeichert und nur noch über eine authentifizierte Controller-Route ausgeliefert.

## Neue Route

```text
GET /cups/{cup}/submissions/{submission}/screenshot
```

Route-Name:

```text
cups.submissions.screenshot
```

## Zugriff

Ein Screenshot wird nur ausgeliefert, wenn der eingeloggte Nutzer eine der folgenden Bedingungen erfüllt:

- Admin / Cup-Management-Berechtigung
- Einreicher der Submission
- aktives Mitglied des zugehörigen Cup-Teams bzw. Solo-Cup-Teilnehmer

Alle anderen Nutzer erhalten 403 oder 404.

## Neue Uploads

Neue Uploads aus `CupSubmissionController::store()` nutzen:

```env
HH_CUP_SUBMISSION_SCREENSHOT_DISK=local
```

Standardwert: `local`

Im aktuellen `config/filesystems.php` zeigt `local` auf:

```text
storage/app/private
```

Damit liegt die Datei nicht mehr unter `public/storage`.

## Bestehende Screenshots migrieren

Bestehende Screenshots, die bereits auf dem öffentlichen Disk liegen, bleiben physisch öffentlich, bis sie migriert werden. Dafür gibt es einen Artisan-Befehl.

Zuerst prüfen:

```bash
php artisan hnt:migrate-cup-screenshots-private --dry-run
```

Dann echt migrieren und alte öffentliche Quelldateien löschen:

```bash
php artisan hnt:migrate-cup-screenshots-private --force --delete-source
```

Optional limitiert laufen lassen:

```bash
php artisan hnt:migrate-cup-screenshots-private --force --delete-source --limit=50
```

## Wichtig

Der Befehl ändert keine Tabellenstruktur. Er kopiert Dateien vom bisherigen Disk auf den privaten Ziel-Disk, aktualisiert `media_assets.disk` und löscht die alte Quelldatei nur, wenn `--delete-source` gesetzt ist.

Ohne `--delete-source` ist die Datenbank zwar auf privat umgestellt, aber die alte öffentliche Datei kann technisch weiterhin unter der alten URL existieren. Für den Launch ist deshalb nach erfolgreichem Dry-Run die Variante mit `--delete-source` empfohlen.
