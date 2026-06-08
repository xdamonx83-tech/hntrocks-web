# Phase 62 – Achievement Toasts

## Ziel
Xbox-artige Toasts für Badge-Freischaltungen und abgeschlossene Quests.

## Enthalten
- Session-basierte Achievement-Toasts bei normalen Requests.
- AJAX-Nachladung über `/gamification/achievements/pending`.
- Automatische Erkennung nach mutierenden Fetch-Requests.
- Badge-Toast bei automatisch freigeschalteten Badges.
- Quest-Toast bei abgeschlossenen Quests.
- Optionaler XP-Hinweis im Toast.
- Badge-/Quest-Notifications bleiben erhalten.

## Geänderte Dateien
- app/Services/GamificationService.php
- routes/web.php
- resources/views/layouts/app.blade.php
- resources/views/partials/achievement-toasts.blade.php
- resources/lang/de/ui.php
- resources/lang/en/ui.php
- public/assets/vikinger/js/hunthub-start.js
- public/assets/vikinger/css/hunthub-vikinger-start.css
