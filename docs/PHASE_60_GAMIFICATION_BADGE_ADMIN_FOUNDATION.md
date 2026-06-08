# Phase 60 – Gamification Badge Admin Foundation

## Ziel

Diese Phase baut die erste echte Admin-Verwaltung für Badges auf:

- Badges im Admin anlegen
- eigene Badge-Icons hochladen
- Badges bearbeiten und aktiv/inaktiv setzen
- Badges als manuell markierbar machen
- Badges manuell an Nutzer vergeben
- Badge-Vergabe optional als Notification auslösen
- Badge-Icons im Profil, in der Mitgliederliste und auf der Gamification-Seite anzeigen

## Geänderte Bereiche

- Admin-Routen unter `/admin/gamification`
- Badge-Modell erweitert
- Badge-Pivot `badge_user` erweitert
- Admin-Gamification-Controller neu
- Admin-Gamification-View neu
- Admin-Navigation erweitert
- Profil/Members/Gamification-Anzeige für hochgeladene Badge-Icons vorbereitet

## Deployment

Diese Phase enthält eine neue Migration.

```bash
cd /home/users/hunthub/www/social.hunthub.online
composer dump-autoload
php artisan migrate
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Falls Badge-Icons über `Storage::disk('public')` nicht erreichbar sind, muss einmalig der Storage-Link existieren:

```bash
php artisan storage:link
```

## Testpfade

- `/admin/gamification`
- `/gamification`
- `/profile`
- `/members`

## Nächster sinnvoller Schritt

Phase 60c: Quest-Admin-CRUD und danach Achievement-/Quest-Toast im Xbox-Stil.
