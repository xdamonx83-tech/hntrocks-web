# Phase 61 – Gamification Quest Admin

Basis: Phase 60 Badge-Admin-Foundation.

## Enthalten

- Admin kann Quests unter `/admin/gamification` anlegen.
- Admin kann Quests bearbeiten.
- Admin kann Quest-Icons hochladen.
- Admin kann Fallback-Icons setzen.
- Quest kann eine bestehende Gamification-Action tracken.
- Quest kann XP vergeben.
- Quest kann optional ein Badge über `badge_slug` vergeben.
- Quest kann aktiv/inaktiv gesetzt werden.
- Quest kann wiederholbar markiert werden.
- Quest kann eine Notification bei Abschluss erzeugen.
- Quest kann gelöscht werden, solange noch keine Nutzer-Fortschritte daran hängen.
- Frontend `/gamification` zeigt Quest-Icons an.

## Geänderte Dateien

- `app/Http/Controllers/Admin/AdminGamificationController.php`
- `app/Models/Quest.php`
- `app/Services/GamificationService.php`
- `database/migrations/2026_04_30_000061_extend_quests_for_admin_management.php`
- `resources/views/admin/gamification/index.blade.php`
- `resources/views/gamification/index.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`
- `routes/web.php`

## Deployment

Neue Migration enthalten:

```bash
composer dump-autoload
php artisan migrate
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Wenn Quest-Icons nicht angezeigt werden:

```bash
php artisan storage:link
```

## Hinweis

Die Xbox-/Achievement-Toasts sind bewusst noch nicht Teil dieser Phase. Phase 61 legt erst die Quest-Verwaltung und Abschluss-Notifications sauber an. Der Toast sollte danach zentral auf Basis von frisch erzeugten `quest_completed`- und `badge_awarded`-Notifications aufgebaut werden.
