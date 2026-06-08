# Phase 64 – Gamification Vikinger Visual Rework

## Ziel
Die öffentliche Gamification-Seite `/gamification` wurde optisch näher an die Vikinger-Referenzen `badges.html` und `quests.html` gezogen.

## Geändert
- `resources/views/gamification/index.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Inhalt
- Section Banner im Vikinger-Stil
- Level-/Badge-/Quest-/Profil-Übersicht als Vikinger-Widget-Karten
- Badges als Vikinger-nahe Badge-Stat-Cards
- Locked/Unlocked-Zustände
- Quests als Vikinger-nahe Quest-Cards mit Cover, Icon, XP und Fortschritt
- XP-Verlauf als saubere Widget-Liste
- DE/EN-Texte ergänzt

## Deployment
Keine Migration, kein composer install und kein composer dump-autoload nötig.

```bash
cd /home/users/hunthub/www/social.hunthub.online
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

## Test
- `/gamification`
- `/admin/gamification`
- Badge/Quest mit Icon anlegen und auf `/gamification` prüfen.
