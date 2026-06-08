# Patch 534 - Cup platform checkboxes

## Ziel
Die Cup-Erstellen/Bearbeiten-Seite soll bei der Plattform nicht nur einen einzelnen Wert per Select speichern, sondern mehrere Plattformen direkt auswählbar machen. Wichtigster Fall: PlayStation + Xbox für Konsolen-Cups.

## Änderung
- Das alte Plattform-Select in `cups/partials/cup-form.blade.php` wurde durch Checkboxen ersetzt.
- Sichtbare Labels:
  - PC
  - PlayStation 5
  - Xbox Series X|S
- Die Checkboxen speichern weiterhin die vorhandenen Werte `PC`, `PlayStation`, `Xbox` in `allowed_platforms[]`.
- Die doppelte Plattform-Auswahl im Regelprofil-Bereich wurde entfernt.
- Die Sidebar-Vorschau zeigt die aktuell gewählten Plattformen.
- `CupController` setzt das Legacy-Feld `platform` beim Speichern automatisch aus den gewählten Plattformen, z. B. `PlayStation / Xbox`.

## Keine Änderung
- Keine Migration
- Keine neue Route
- Keine KI-Prompt-Änderung
- Keine Scoring-Änderung
- Keine Android-Änderung
