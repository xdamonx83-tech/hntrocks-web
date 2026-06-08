# Patch 512 – Admin Inhalte Kategorie-Tabs

## Ziel
Die Admin-Inhaltsmoderation wird in Bereiche aufgeteilt, damit die Seite nicht alle Inhaltstypen untereinander ausgibt und unnötig lang wird.

## Änderungen
- `/admin/content` zeigt standardmäßig nur `Feed-Beiträge`.
- Oben gibt es Bereichs-Tabs für:
  - Alle
  - Feed-Beiträge
  - Moments
  - Medien
  - Teams
  - LFG
  - Team-LFG
  - Cups
  - Cup-Einreichungen
- Jeder Tab zeigt die Gesamtanzahl des jeweiligen Bereichs.
- `?section=...` steuert, welcher Bereich angezeigt wird.
- Der bestehende Statuswechsel bleibt unverändert.
- Admin-CSS-Version wurde auf `v=512` erhöht.

## Keine Änderungen
- Keine Datenbankänderung
- Keine Migration
- Keine Routenänderung
- Keine Socialite-/Vikinger-Frontend-Änderung
- Keine Android-Änderung
