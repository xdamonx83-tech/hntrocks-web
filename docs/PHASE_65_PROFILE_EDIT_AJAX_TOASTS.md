# Phase 65 – Profile edit AJAX + toasts

Basis: vollständiger aktueller Upload `3.zip`.

## Inhalt

- Profilformular speichert per AJAX ohne Seitenreload.
- Fallback ohne JavaScript bleibt über den normalen PUT-Request erhalten.
- Erfolg wird als vorhandener Hunthub-Toast angezeigt.
- Validierungsfehler werden inline am passenden Feld gesetzt.
- Fehler beim Speichern zeigen einen Error-Toast.
- Avatar- und Cover-Vorschau aktualisieren sich direkt bei Dateiauswahl.
- Name und Headline aktualisieren die Vorschau während der Eingabe.
- Header, Chat-Dock, Sidebar und Gamification wurden nicht umgebaut.

## Geänderte Dateien

- app/Http/Controllers/Profile/ProfileController.php
- resources/views/profile/edit.blade.php
- public/assets/vikinger/js/hunthub-start.js
- public/assets/vikinger/css/hunthub-vikinger-start.css
- resources/lang/de/ui.php
- resources/lang/en/ui.php
