# Patch 490 — Chat Tabs Route Cache Safety

Hotfix zu Patch 489.

## Änderung

- Der Header erzeugt die Chat-Tab-AJAX-URL nicht mehr über `route('messages.chat-tab', ...)`.
- Stattdessen wird eine normale interne URL mit `url('/messages/{id}/chat-tab')` ausgegeben.
- Dadurch kann die Feed-Seite nicht mehr durch eine alte Laravel-Route-Cache-Datei fatal abbrechen, falls `route:clear` beim Einspielen vergessen wurde.

## Dateien

- `resources/views/themes/socialite/partials/header.blade.php`

## Installation

```bash
php artisan optimize:clear
php artisan view:clear
```

Für funktionierende Chat-Tabs nach Patch 489 weiterhin wichtig:

```bash
php artisan route:clear
php artisan optimize:clear
php artisan view:clear
```
