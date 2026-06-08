# Phase 58 - Right Chat Dock

Basis: 2.zip + Phase 56 + Phase 57 + Phase 57b.

Ziel:
- Chats aus dem Header-Dropdown herauslösen.
- Rechte Vikinger-nahe Chat-Leiste mit Avataren ergänzen.
- Chat-Liste und einzelne private Konversation als Dock/Panel öffnen.
- Nachrichten im Dock per AJAX senden, ohne die aktuelle Seite zu verlassen.

Geändert:
- resources/views/layouts/app.blade.php
- resources/views/partials/header.blade.php
- resources/views/partials/chat-dock.blade.php
- app/Http/Controllers/Messages/MessageController.php
- routes/web.php
- public/assets/vikinger/js/hunthub-start.js
- public/assets/vikinger/css/hunthub-vikinger-start.css
- resources/lang/de/ui.php
- resources/lang/en/ui.php

Deployment:
- Kein composer install nötig.
- Kein composer dump-autoload nötig.
- Keine Migration nötig.
- php artisan view:clear
- php artisan optimize:clear

Hinweis:
Dies ist bewusst nur der Chat-Dock-Schritt. Notifications und Friend-Requests bleiben getrennt.
