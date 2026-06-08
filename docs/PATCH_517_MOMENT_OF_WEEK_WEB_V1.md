# Patch 517: Moment der Woche Web v1

## Ziel
Neues Web-Modul „Moment der Woche“ als Community-Spotlight für HNT.rocks.

## Änderungen
- Neue Nutzerseite: `/moment-of-week`
- Neue Adminseite: `/admin/moment-of-week`
- Neue Tabelle `moment_spotlights`
- Admin kann vorhandene veröffentlichte Moments als Wochen-Highlight auswählen
- Aktuelles Highlight wird prominent angezeigt
- Archiv vergangener Highlights
- Kandidatenbereich mit aktuellen/top Moments nach Likes, Kommentaren und Views
- Link in Socialite-Sidebar ergänzt
- Link in Admin-Sidebar ergänzt
- DE/EN-Sprachkeys ergänzt
- Socialite/Admin-CSS ergänzt

## Bewusst nicht enthalten
- Kein öffentliches Voting in v1
- Keine automatischen Badge-/XP-Belohnungen
- Keine Android-App-Integration
- Keine Änderung am bestehenden Moment-Upload oder Moment-Viewer

## Installation
```bash
composer dump-autoload
php artisan route:clear
php artisan migrate --force
php artisan optimize:clear
php artisan view:clear
```

Danach ggf. Rechte setzen:
```bash
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Test
1. `/admin/moment-of-week` öffnen.
2. Einen veröffentlichten Moment auswählen.
3. Zeitraum setzen und „Als aktuelles Highlight anzeigen“ aktiv lassen.
4. Speichern.
5. `/moment-of-week` öffnen und Darstellung prüfen.
