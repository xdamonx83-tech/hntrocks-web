# Phase 72b – Team-Feed Composer an normalen Feed angeglichen

## Ziel
Der Composer in der Team-Timeline soll optisch nicht mehr wie ein Sonderformular wirken, sondern wie der normale Vikinger-Feed-Composer.

## Geändert
- `resources/views/teams/show.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`
- `public/assets/vikinger/js/hunthub-start.js`

## Inhalt
- Team-Composer nutzt jetzt die Vikinger-Struktur mit Status / Blog Post / Poll.
- Textarea, Counter, Footer-Actions und Buttons orientieren sich am normalen Feed.
- Kamera-Icon öffnet weiterhin den Medien-Upload.
- GIF/Tag-Icons sind optische Platzhalter wie im Feed, ohne neue Logik.
- Zeichen-Counter läuft live.
- Team-Feed-Post-Logik aus Phase 72 bleibt unverändert.

## Keine Änderungen
- Keine Migration.
- Kein Controller-Umbau.
- Keine Änderung am globalen Feed.
- Keine Änderung an Team-Rechten.
