# Phase 56 – Header Notification Dropdown

## Ziel

Der stabile Header aus Phase 55 bleibt die Basis. In dieser Phase wird ausschließlich das Notification-Dropdown wieder sauber und klein eingebaut.

## Geändert

- `resources/views/partials/header.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`
- `public/assets/vikinger/js/hunthub-start.js`

## Enthalten

- Dropdown im Vikinger-Stil am Notification-Icon
- echte `user_notifications` des eingeloggten Nutzers
- Anzeige von Actor, Titel, Body, Zeitpunkt und Status
- ungelesener Badge bleibt erhalten
- einzelne Notification kann gelesen werden und leitet bei vorhandener Action-URL weiter
- „Alle gelesen“ nutzt die bestehende Route `/notifications/read-all`
- keine Messages-/Friend-Request-Dropdowns in dieser Phase

## Keine Strukturänderung

- keine Migration
- kein neuer Controller
- kein neues Model
- kein Composer-Paket

## Deployment

Bei reinen View-/Asset-/Language-Änderungen reicht:

```bash
cd /home/users/hunthub/www/social.hunthub.online
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```
