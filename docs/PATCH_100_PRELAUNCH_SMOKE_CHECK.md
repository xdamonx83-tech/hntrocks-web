# Patch 100 — Finaler Pre-Launch Smoke Check

## Ziel

Dieser Patch baut keine neue Web-Funktion. Er ergänzt prüfbare Launch-Checks, damit vor dem öffentlichen Start weniger geraten werden muss.

## Änderungen

- `routes/console.php`
  - alte sichtbare Console-Ausgaben von „Hunthub“ auf `hnt.rocks` angepasst
  - `hunthub:audit-routes` um wichtige öffentliche Launch-Routen erweitert
  - neuer Check: `php artisan hnt:prelaunch-check`
- `scripts/prelaunch-smoke-check.sh`
  - prüft die wichtigsten öffentlichen URLs per HTTP-Status

## Was `php artisan hnt:prelaunch-check` prüft

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://hnt.rocks`
- sichere Session-Cookie-Basis
- Visitor Tracking verlangt Consent
- Cup-Screenshots liegen nicht auf public Disk
- Mail nutzt nicht `sendmail`
- wichtige Routen sind registriert
- keine alten Google-/jsDelivr-Font-/Icon-Referenzen in den relevanten Asset-Dateien
- DE/EN-Sprachdateien haben gleiche Keys
- wichtige Launch-Sprachkeys sind vorhanden

## Was das Script prüft

```bash
bash scripts/prelaunch-smoke-check.sh https://hnt.rocks
```

Geprüft werden:

- `/login`
- `/register`
- `/forgot-password`
- `/impressum`
- `/datenschutz`
- `/nutzungsbedingungen`
- `/netiquette`
- `/cups`
- `/cups/bayou-blood-cup`
- `/hall-of-fame`

## Nicht enthalten

- keine Migration
- kein Composer
- keine Mail-Änderung
- keine Web-Feature-Änderung
- kein Header-/Feed-/Cup-Rework
- kein Admin-Rework

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 100_hnt_prelaunch_smoke_check.zip -d .

php artisan optimize:clear
php artisan hnt:prelaunch-check
bash scripts/prelaunch-smoke-check.sh https://hnt.rocks

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Hinweis

Der Check ersetzt keinen echten Browser-Test, keine DB-Prüfung und keine finale anwaltliche/datenschutzrechtliche Prüfung. Er ist ein technischer Smoke-Test für den Launch-Zustand.
