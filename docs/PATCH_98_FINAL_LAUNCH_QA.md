# Patch 98 – Finale Launch-QA: Navigation, Branding, Routencheck

## Ziel

Kleiner Launch-QA-Patch ohne Feature-Ausbau. Der Patch beseitigt noch sichtbare alte Hunthub-/social.hunthub.online-Branding-Reste in Views und verbessert die mobile Cup-Erreichbarkeit für Fallback-/Neuinstallationsfälle.

## Geänderte Dateien

- `app/Services/Navigation/MobileNavService.php`
- `resources/views/overview/index.blade.php`
- `resources/views/partials/vikinger/header.blade.php`
- `resources/views/partials/vikinger/page-loader.blade.php`
- `docs/PATCH_98_FINAL_LAUNCH_QA.md`

## Änderungen

### Mobile Navigation

- `cups` ist in den Default-Definitionen der mobilen Navigation jetzt aktiviert.
- Wirkung:
  - Wenn `mobile_nav_items` noch nicht existiert, ist Cups im Fallback sichtbar.
  - Wenn ein neuer Default-Eintrag angelegt wird, ist Cups standardmäßig aktiv.
  - Bereits vorhandene Datenbankeinträge werden nicht überschrieben. Wenn Cups in der produktiven DB bereits deaktiviert ist, bitte im Adminbereich manuell aktivieren.

### Sichtbares Branding

- Sichtbarer alter Domaintext `social.hunthub.online` im Overview-Widget wurde auf `hnt.rocks` geändert.
- Alte `Hunthub`-Alt-/Brandtexte in den Vikinger-Partial-Fallbacks wurden auf `hnt.rocks` geändert.
- Technische Dateinamen, Klassen, Konfigurationsnamen und interne Funktionen wurden nicht umbenannt.

## Geprüft

Statisch geprüft auf dem zusammengesetzten Stand aus aktuellem Upload + Patch 92–97:

- Keine gefundenen Requests mehr auf:
  - `fonts.googleapis.com`
  - `fonts.gstatic.com`
  - `cdn.jsdelivr.net`
- Keine sichtbaren View-/Lang-Treffer mehr für:
  - `social.hunthub.online`
  - `hunthub.online`
  - sichtbares `Hunthub` in View-/Lang-Dateien
- `php artisan hunthub:audit-routes` meldet die wichtigen Routen als registriert.
- DE/EN `ui.php` haben gleich viele Keys.
- PHP-Syntax der geänderten Dateien geprüft.

## Bekannte Punkte, die dieser Patch nicht löst

- `php artisan route:list` kann in der Chat-Umgebung wegen fehlendem `mb_split()`/`mbstring` nicht vollständig ausgeführt werden. Auf dem Server bitte `php -m | grep mbstring` prüfen.
- `php artisan hunthub:health` meldet in der Chat-Umgebung fehlende DB-Treiber und fehlendes `public/storage`; das kann an der lokalen Containerumgebung liegen. Auf dem Server bitte `php artisan storage:link` nur ausführen, wenn der Symlink wirklich fehlt.
- Bestehende `mobile_nav_items`-DB-Einträge werden absichtlich nicht überschrieben. Für den Cup-Launch im Adminbereich prüfen, ob Cups mobil aktiv ist.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 98_hnt_final_launch_qa.zip -d .

php artisan view:clear
php artisan optimize:clear

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Nach dem Deployment prüfen

- `/login`
- `/register`
- `/forgot-password`
- `/reset-password/<token>` mit echtem Reset-Link
- `/impressum`
- `/datenschutz`
- `/nutzungsbedingungen`
- `/netiquette`
- `/cups`
- `/cups/bayou-blood-cup`
- `/hall-of-fame`
- mobil: Bottom Navigation und Cups-Erreichbarkeit
- mobil: Notification-/Friend-Sheets
- DevTools/Network: keine Google Fonts/jsDelivr Font-Requests
