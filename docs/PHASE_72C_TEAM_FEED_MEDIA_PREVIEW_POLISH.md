# Phase 72c — Team-Feed Composer Media Preview Polish

## Basis
- Aktueller Stand nach Phase 72b (`72b_hunthub_team_feed_composer_match.zip`).

## Ziel
Der Team-Feed-Composer soll beim Medienupload näher am normalen Feed-Composer liegen:
- kleine Bildvorschau-Kacheln mit Entfernen-Button
- zusätzliche Plus-Kachel zum weiteren Anhängen
- große Video-Vorschau mit nativen Videocontrols

## Geänderte Dateien
- `resources/views/teams/show.blade.php`
- `public/assets/vikinger/js/hunthub-start.js`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Keine Änderungen
- Keine Migration
- Keine Controller-Änderung
- Keine Routen-Änderung
- Keine Änderung an der Feed-Speicherlogik

## Test
1. `/teams/{team-slug}` als Teammitglied öffnen.
2. Bild auswählen: kleine Vorschau + Entfernen-Button + Plus-Kachel prüfen.
3. Mehrere Bilder auswählen: mehrere kleine Vorschauen prüfen.
4. Video auswählen: große Video-Vorschau mit Controls prüfen.
5. Verwerfen klicken: Vorschau und Zähler sollen zurückgesetzt werden.
6. Posten prüfen: bestehende Team-Post-Logik muss weiter greifen.
