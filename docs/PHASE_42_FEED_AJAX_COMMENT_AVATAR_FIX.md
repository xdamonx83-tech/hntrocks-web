# Phase 42 – Feed AJAX-Kommentar Avatar/Hydration Fix

## Ziel
Nach dem AJAX-Absenden eines Kommentars war der Kommentar sofort sichtbar, aber Avatar/Avatar-Hydration wirkte erst nach einem Reload vollständig.

## Änderung
- Kommentar-Partial enthält jetzt einen robusten Avatar-Fallback per inline background-image und img-Fallback.
- JS-Hydration für neu eingefügte `data-src`-Elemente wurde gehärtet.
- CSS stellt sicher, dass AJAX-eingefügte Kommentar-Avatare sofort sichtbar bleiben.

## Keine Änderung
- keine Migration
- keine neue Route
- kein neuer Controller
- keine Änderung an Feed-Logik
