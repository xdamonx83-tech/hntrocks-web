# Patch 189 - Laravel Notification action_url API fix

## Ziel

Mobile Notifications sollen nicht nur grob nach Typ springen, sondern die echte Ziel-URL nutzen können, z. B.:

- `/feed/posts/18#comment-9`
- `/teams/example-team`
- `/cups/bayou-blood-cup`

## Problem

`UserNotification` speichert den Link in `action_url`.

Die API-Resource `NotificationResource` gab bisher aber `url => $this->url` zurück. Dieses Feld ist im Model nicht die eigentliche gespeicherte Zielspalte. Dadurch kam in der Android-App kein nutzbarer Link an. Android konnte dann nur über `type=feed_comment_reply` grob in den Feed springen.

## Änderung

`NotificationResource` liefert jetzt:

- `url` = `action_url`
- `action_url` = `action_url`

Damit bleiben bestehende Android-Parser kompatibel und künftige Clients können das Feld eindeutig lesen.

## Dateien

- `app/Http/Resources/Api/NotificationResource.php`

## Deployment

Keine Migration.
Kein Composer install.

Empfohlen:

```bash
php artisan optimize:clear
php artisan route:clear
```
