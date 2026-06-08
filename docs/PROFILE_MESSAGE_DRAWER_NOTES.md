# Patch 59 – Profile Message Drawer

## Zweck

Profil-Buttons für private Nachrichten öffnen auf Desktop direkt den rechten Chat-Drawer, statt zur Nachrichten-Seite zu navigieren. Auf Mobile bleibt das robuste bisherige Seitenverhalten erhalten.

## Verhalten

- Desktop ab 961px: Profil → Nachricht öffnet/erstellt die private Unterhaltung per JSON und öffnet den vorhandenen Chat-Dock rechts.
- Mobile bis 960px: Der Link navigiert normal weiter.
- Ohne JavaScript: Der Link navigiert normal weiter.
- Die Zielseite `/messages/with/{user}` öffnet/erstellt die private Unterhaltung und leitet auf die konkrete Conversation-Seite weiter.
- Keine private Mentions.
- Keine Datenbankänderung.

## Geänderte Dateien

- `routes/web.php`
- `app/Http/Controllers/Messages/MessageController.php`
- `resources/views/partials/chat-dock.blade.php`
- `resources/views/partials/chat-dock-conversation-button.blade.php`
- `resources/views/partials/chat-dock-conversation-panel.blade.php`
- `resources/views/profile/show.blade.php`
- `resources/views/profile/about.blade.php`
- `resources/views/profile/friends.blade.php`
- `resources/views/profile/badges.blade.php`
- `resources/views/profile/teams.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`
- `public/assets/vikinger/js/hunthub-start.js`

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
composer dump-autoload
php artisan optimize:clear
php artisan config:clear
php artisan view:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```
