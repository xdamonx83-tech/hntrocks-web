# Phase 51 – Header Notification Variable Hotfix

## Ursache
Nach Phase 50 konnte `/feed` mit vorhandenen Notifications einen 500er werfen.
Der Laravel-Log zeigte:

`Undefined variable $notification` in der kompilierten Header-View.

## Fix
Der Notifications-Loop im Header wurde defensiver umgebaut:

- kein `@forelse ($hhNotifications as $notification)` mehr
- stattdessen klare `@if ($hhNotifications->isEmpty())` / `@foreach ($hhNotifications as $hhNotification)` Struktur
- Variable von `$notification` auf `$hhNotification` umbenannt
- der Notification-Button wurde mehrzeilig und lesbarer aufgebaut

## Geänderte Datei

- `resources/views/partials/header.blade.php`

## Deployment

Nur Blade-View-Hotfix:

```bash
cd /home/users/hunthub/www/social.hunthub.online
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Kein `composer install`.
Keine Migration.
Kein `composer dump-autoload` nötig.
