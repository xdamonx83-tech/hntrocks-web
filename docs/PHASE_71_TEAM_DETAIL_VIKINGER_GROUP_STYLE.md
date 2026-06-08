# Phase 71 – Team-Detailseite im Vikinger Group-Stil

## Basis
- Aktueller Stand: `3.zip` + akzeptierte Phasen 56 bis 70
- Phase 70 bleibt die aktuelle Team-Create/Edit-Modal-Basis

## Geänderte Dateien
- `app/Http/Controllers/Teams/TeamController.php`
- `resources/views/teams/show.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Inhalt
- Team-Detailseite optisch näher an Vikinger `group-timeline.html` / `group-info.html`
- Group-artiger Header mit Cover, Hexagon-Teamavatar, Stats und Action-Buttons
- Section-Navigation für Timeline, Info, Mitglieder, Team-LFG und Anfragen
- Team-Info als Vikinger-Widget
- Mitgliederliste mit Hexagon-Avataren
- Team-LFG-Vorschau mit offenen Beiträgen
- Beitrittsanfragen für Owner/Officer im rechten Widgetbereich
- Join-Box für Gäste/Nichtmitglieder bei offenen Teams

## Nicht geändert
- Keine Migration
- Kein Composer
- Keine Routenänderung
- Keine Änderung an Team Create/Edit Modal aus Phase 70
- Keine Header-/Chat-/Gamification-Änderung

## Deployment
```bash
cd /home/users/hunthub/www/social.hunthub.online
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Danach wegen CSS hart neu laden.

## Test
- `/teams/{team-slug}`
- `/teams/manage`
- `/teams/{team-slug}/edit`
- Beitrittsanfrage als Nichtmitglied testen
- Annehmen/Ablehnen als Owner/Officer testen
