# Phase 50 – Header Dropdowns für Notifications, Friend Requests und Messages

## Ziel

Der obere Header soll näher an die Vikinger-Referenz rücken und die seit Phase 46/47 vorhandenen Freundschafts-, Nachrichten- und Benachrichtigungsdaten direkt im Header anzeigen.

## Geändert

- `resources/views/partials/header.blade.php`
- `app/Http/Controllers/Friends/FriendshipController.php`
- `app/Http/Controllers/Notifications/NotificationController.php`
- `public/assets/vikinger/js/hunthub-start.js`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Enthalten

### Friend Requests

- neuer Header-Dropdown für eingehende Freundschaftsanfragen
- zeigt bis zu 5 echte pending Requests
- Annehmen/Ablehnen direkt im Dropdown
- AJAX-Aktion ohne vollen Seitenreload
- Toast bei Aktion
- Badge aktualisiert sich nach AJAX-Aktion

### Messages

- neuer Header-Dropdown mit privaten Unterhaltungen
- zeigt letzte private Conversations
- zeigt letzte Nachricht und ungelesene Anzahl pro Conversation
- Link zu allen Nachrichten

### Notifications

- Dropdown mit echten `user_notifications`
- zeigt Actor, Titel, Body, Zeit und Icon
- „Mark all as Read“ per AJAX
- Badge aktualisiert sich
- Klick auf einzelne Notification markiert sie als gelesen und leitet zur Action-URL weiter

## Keine Migration

Es werden nur bestehende Tabellen genutzt:

- `friendships`
- `conversations`
- `messages`
- `user_notifications`

## Deployment

- `composer dump-autoload` empfohlen, weil bestehende Controller-Signaturen geändert wurden
- keine Migration
- kein `composer install`
