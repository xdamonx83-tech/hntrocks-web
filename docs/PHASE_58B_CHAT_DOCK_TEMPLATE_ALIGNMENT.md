# Phase 58b – Chat-Dock näher am Vikinger-Original

## Ziel
Die rechte Chat-Leiste wurde optisch näher an die Vikinger-Referenz angepasst.

## Änderungen
- Rechte Chat-Leiste arbeitet jetzt wie das Vikinger-Widget:
  - geschlossen: schmale Avatar-Leiste sichtbar
  - geöffnet: Nachrichtenliste fährt als 300px-Widget auf
  - Chat-Ansicht legt sich als eigenes Overlay über die Liste
- Suchfeld sitzt wie im Template unten über dem grünen Chat-Button.
- Chat-Button bleibt unten grün und zeigt im geöffneten Zustand den Text „Messages / Chat“.
- Konversationen verwenden kompaktere Bubble-/Abstandslogik nach Template-Vorbild.
- Bestehende AJAX-Sende- und Gelesen-Logik bleibt erhalten.

## Geänderte Dateien
- resources/views/partials/chat-dock.blade.php
- public/assets/vikinger/js/hunthub-start.js
- public/assets/vikinger/css/hunthub-vikinger-start.css
- resources/lang/de/ui.php
- resources/lang/en/ui.php

## Nicht geändert
- Keine Migrationen
- Keine Composer-Abhängigkeiten
- Keine Controller-/Model-Änderungen
- Header-Notification- und Friend-Request-Dropdowns bleiben unangetastet
