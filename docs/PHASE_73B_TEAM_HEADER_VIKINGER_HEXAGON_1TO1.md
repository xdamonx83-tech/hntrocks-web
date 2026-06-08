# Phase 73b – Team Header Avatar als echtes Vikinger-Hexagon

## Basis

- Aktuelle `resources/views/teams/show.blade.php` aus dem bisherigen Arbeitsstand.
- Neu hochgeladene `public/assets/vikinger/css/hunthub-vikinger-start.css` als aktuelle CSS-Wahrheit.
- Neu hochgeladene `public/assets/vikinger/css/styles.min.css` als aktuelle Template-CSS-Wahrheit.

## Änderung

Der Team-Avatar im Team-Header nutzt jetzt wieder das originale Vikinger-Hexagon-Markup:

- Desktop: `user-avatar big no-stats`
- Border: `hexagon-148-164`
- Bild: `hexagon-image-124-136`
- Mobile: `user-avatar medium no-stats`
- Mobile Border: `hexagon-120-130`
- Mobile Bild: `hexagon-image-100-110`

Die vorherige eigene Clip-Path-/Gradient-Hexagon-Optik wird nicht mehr mitgeliefert.

## Nicht geändert

- Keine Controller
- Keine Routes
- Keine Migration
- Keine JS-Logik
- Kein Team-Level-System
- Kein Team-Feed-Umbau
