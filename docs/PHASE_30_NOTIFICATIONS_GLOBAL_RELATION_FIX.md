# Phase 30 – Globaler Notification-Relation-Fix

Dieser Patch stabilisiert die Notification-Relationen global. Hunthub verwendet die eigene Tabelle `user_notifications`. Laravel bringt über das `Notifiable`-Trait aber Standard-Relationen mit, die auf `notifications` zeigen.

## Fix

- `app/Models/User.php` überschreibt `notifications()` und `unreadNotifications()` und leitet beide auf `user_notifications` um.
- Die Kompatibilitätsmigration `2026_04_29_000019_create_laravel_notifications_compat_table.php` bleibt als Sicherheitsnetz enthalten.
- `resources/views/feed/index.blade.php` nutzt wieder `notificationItems()->unread()`.

## Ziel

Keine 500er mehr durch versehentliche Aufrufe von `$user->unreadNotifications()` oder `$user->notifications()`.
