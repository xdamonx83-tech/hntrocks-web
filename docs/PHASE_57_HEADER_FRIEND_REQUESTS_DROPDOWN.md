# Phase 57 — Header Friend Requests Dropdown

Basis: aktueller stabiler Upload `2.zip` plus akzeptierter Patch `56_hunthub_header_notification_dropdown.zip`.

Umfang:
- Friend-Requests-Dropdown im Header ergänzt.
- Es werden nur echte eingehende, offene `friendships` mit Status `pending` geladen.
- Annehmen/Ablehnen läuft über bestehende Routes `friends.accept` und `friends.decline`.
- AJAX nutzt den vorhandenen Header-AJAX-Handler und zeigt Toasts aus der bestehenden Controller-Response.
- Das sichtbare Badge wird nach jeder Aktion aktualisiert.
- Kein Umbau von Messages oder Notifications.

Geänderte Dateien:
- `resources/views/partials/header.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`
- `public/assets/vikinger/js/hunthub-start.js`

Nicht nötig:
- keine Migration
- kein Composer install
- kein Composer dump-autoload
