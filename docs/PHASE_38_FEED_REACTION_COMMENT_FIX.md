# Phase 38 – Feed Reaction/Kommentar Hotfix

Basis: Phase 37.

Geändert:
- `app/Http/Controllers/Feed/FeedReactionController.php`
- `resources/views/feed/partials/post-card.blade.php`
- `public/assets/vikinger/js/hunthub-start.js`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

Ziel:
- Reaction-Picker öffnet per Hover über React.
- Reaction-Klicks laufen per Fetch/AJAX ohne Seitenreload.
- Hauptbutton toggelt Like.
- Picker-Buttons setzen/ändern den Reaction-Typ.
- Kommentar-Composer und Kommentaritems sind lokal als Grid gehärtet, damit Avatare nicht mehr in Input/Text rutschen.
- Reply im Kommentar setzt `parent_id`, fokussiert den Composer und setzt optional `@Name`.

Bewusst nicht enthalten:
- keine neue Migration
- keine neuen Tabellen
- keine echte Kommentar-Reaction-Tabelle
- kein Add-Friend-System

Deployment:
```bash
cd /home/users/hunthub/www/social.hunthub.online
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Kein `composer install` und keine Migration nötig.
