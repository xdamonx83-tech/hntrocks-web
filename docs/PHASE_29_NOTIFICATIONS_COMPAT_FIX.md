# Phase 29 – Notifications Compatibility / 500 Fix

Fix für 500-Fehler nach Phase 27.

Ursache: In `resources/views/feed/index.blade.php` wurde versehentlich Laravels Standard-Relation `unreadNotifications()` verwendet. Das Projekt nutzt aber die eigene Tabelle `user_notifications` über `notificationItems()`.

Änderungen:
- Feed-Zähler nutzt wieder `notificationItems()->unread()->count()`.
- Zusätzlich wird eine Laravel-kompatible `notifications`-Tabelle angelegt, damit versehentliche Standard-Notification-Aufrufe künftig nicht direkt einen 500er auslösen.

Nach Einspielen:

```bash
composer dump-autoload
php artisan migrate
php artisan view:clear
php artisan optimize:clear
```
