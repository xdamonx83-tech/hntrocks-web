# Patch 95 — Mobile Header / Notification- und Friend-Sheets

## Ziel

Dieser Patch stabilisiert die mobilen Header-Sheets für Freundschaftsanfragen und Benachrichtigungen, ohne Desktop-Header, Mail, Datenbank, Cups, Feed oder Admin-Logik umzubauen.

## Geänderte Dateien

- `resources/views/partials/header.blade.php`
- `public/assets/vikinger/js/hunthub-start.js`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Änderungen

- Mobile Friend-Request- und Notification-Sheets erhalten `role="dialog"` und `aria-modal="true"`.
- Beide mobilen Sheets bekommen einen sichtbaren Schließen-Button.
- Ein mobiler Backdrop wird ergänzt und schließt das geöffnete Sheet bei Tap/Klick.
- Während ein mobiles Sheet offen ist, wird der Hintergrund-Scroll gesperrt.
- Beim Wechsel auf Desktop-Breite werden offene mobile Sheets automatisch geschlossen.
- Mobile Empty-States nach AJAX-Aktionen nutzen jetzt mobile Sheet-Klassen statt Desktop-Dropdown-Klassen.
- Friend-Request- und Notification-Aktionen entfernen synchron mobile und Desktop-Listenitems, falls beide im DOM vorhanden sind.
- Desktop Guard bleibt erhalten: Mobile Quick-Icons und mobile Sheets bleiben oberhalb von 960px verborgen.

## Nicht geändert

- Keine Migration.
- Kein Composer.
- Keine Mail-Konfiguration.
- Keine neuen Routen.
- Keine Controller-/Model-Änderungen.
- Keine Änderungen an Feed, Cup-Scoring, Moments oder Admin-Bereich.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 95_hnt_mobile_header_sheets.zip -d .

php artisan view:clear
php artisan optimize:clear

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Prüfen nach Deployment

- Mobile Ansicht öffnen.
- Nachrichten-Icon prüfen: Link führt weiterhin zu Nachrichten.
- Freundschaftsanfragen-Icon öffnen und schließen.
- Benachrichtigungs-Icon öffnen und schließen.
- Backdrop-Tap schließt das Sheet.
- ESC schließt auf Desktop/Tablet mit Tastatur.
- Desktop prüfen: Mobile Quick-Icons dürfen nicht sichtbar sein.
- Desktop Header-Dropdowns für Freundschaftsanfragen/Benachrichtigungen weiterhin testen.
