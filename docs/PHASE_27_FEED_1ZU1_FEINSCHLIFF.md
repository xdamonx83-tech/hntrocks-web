# Phase 27 – Feed 1:1-Feinschliff Richtung Vikinger newsfeed.html

Ziel dieser Phase war kein neues Feature, sondern eine engere optische Angleichung des bestehenden funktionierenden Newsfeeds an die Struktur aus `newsfeed.html`.

## Geändert

- `resources/views/feed/index.blade.php`
- `resources/views/feed/partials/post-card.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`
- `docs/ROADMAP.md`

## Inhalt

- Quick-Post-Composer näher an Vikinger-Struktur gebracht.
- Feed-Tabs näher an `simple-tab-items` aus Vikinger angeglichen.
- Linke und rechte Feed-Spalte stärker als Vikinger-Widgets aufgebaut.
- Post-Karten stärker an `widget-box no-padding`, `widget-box-status`, `content-actions` und `post-options` angelehnt.
- Kommentarbereich näher an Vikinger-Kommentarstruktur verschoben.
- Reactions optisch näher vorbereitet, funktional bleibt aktuell weiterhin der einfache Like-Toggle.
- Speichern bleibt funktional erhalten.

## Bewusst nicht geändert

- Keine neuen Datenbanktabellen.
- Keine neue Feed-Logik.
- Keine Reactions-Tabelle für mehrere Reaction-Typen.
- Keine echte Share-Funktion.
- Keine Algorithmus-Änderung.
