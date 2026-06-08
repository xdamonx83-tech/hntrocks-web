# Patch 513 – Admin Medien Vorschau + Ziel-Links

## Ziel
Der Medien-Bereich unter `/admin/content?section=media` soll nicht nur Dateinamen zeigen, sondern schnell erkennbar machen, welches Bild/Video dahintersteckt und zu welchem Inhalt es gehört.

## Änderungen
- Medien-Zeilen bekommen kleine Vorschau-Kacheln.
- Bilder werden als Thumbnail angezeigt.
- Videos werden als kleine Video-Vorschau mit Play-Hinweis angezeigt.
- Dateien ohne Bild/Video bekommen eine Dateityp-Kachel.
- Pro Medium werden angezeigt:
  - Typ
  - Maße, wenn vorhanden
  - Dateigröße
  - verknüpfter Inhaltstyp
  - verknüpftes Ziel
- Neue Ziel-Links für verknüpfte Inhalte:
  - Feed-Beitrag
  - Moment
  - Cup-Einreichung
  - Cup
  - Team
  - LFG
  - Team-LFG
- Zusätzlich bleibt ein direkter Link zur Datei erhalten, wenn eine Datei-URL verfügbar ist.
- Admin-CSS-Version auf `v=513` erhöht.

## Keine Änderungen
- Keine Datenbankänderung
- Keine Migration
- Keine Routenänderung
- Keine Socialite-/Vikinger-Frontend-Änderung
- Keine Android-Änderung
