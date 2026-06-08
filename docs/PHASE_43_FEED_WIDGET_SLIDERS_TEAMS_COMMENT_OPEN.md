# Phase 43 – Feed Widget-Slider, echte Teamliste, Kommentaröffnung ohne Sprung

## Ziel
Feed bleibt Fokus. Dieser Patch baut keine Profil-/Friend-Funktion um.

## Änderungen

- Featured-Badges-Widget und Reactions-Received-Widget bekommen echte lokale Slider-Logik über die vorhandenen Pfeile.
- Reactions-Received zeigt weiterhin echte Werte aus `feed_reactions`.
- Das Teams-Widget zeigt bis zu 5 öffentliche Teams, die Mitglieder suchen (`visibility=public`, `recruitment_status=open`, `status=active`).
- Klick auf `Comment`/Kommentarzähler öffnet den Kommentarbereich ohne `#comments`-Ankersprung.
- Der Composer wird ohne Scrollsprung fokussiert.

## Deployment

Keine Migration. Kein composer install.

Empfohlen:

```bash
cd /home/users/hunthub/www/social.hunthub.online
composer dump-autoload
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

`composer dump-autoload` ist hier nur wegen der Controller-Änderung sinnvoll. Es wurde keine neue Klasse angelegt.

## Test

- `/feed`
- Featured-Badges-Pfeile klicken
- Reactions-Received-Pfeile klicken
- Teams-Widget auf öffentliche suchende Teams prüfen
- Comment-Klick auf Postkarte prüfen: Kommentarbereich öffnet, Seite springt nicht mehr zu hoch
