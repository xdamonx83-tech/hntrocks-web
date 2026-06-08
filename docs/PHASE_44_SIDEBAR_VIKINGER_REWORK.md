# Phase 44 – Sidebar näher an Vikinger

Ziel: Sidebar optisch näher an die Vikinger-Referenz bringen, ohne Routen/Features neu zu erfinden.

Geändert:
- `resources/views/partials/sidebar.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

Umsetzung:
- kleine Desktop-Sidebar mit Vikinger-Hexagon-Avatar und 64px Icon-Kacheln
- aktive Sidebar-Items grün wie im Template
- große Desktop-Sidebar mit Cover, großem Avatar, Username, Level/XP, Badge-Zeile, Stats und Menü
- mobile Sidebar an dieselbe Struktur angenähert
- bestehende Hunthub-Routen bleiben erhalten

Keine Migration.
Kein neuer Controller.
Keine neuen Composer-Abhängigkeiten.
