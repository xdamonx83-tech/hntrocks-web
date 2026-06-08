# Patch 97 – Feed Uploadlimits folgen wieder der .env

Dieser Patch korrigiert die Uploadlimit-Logik aus Patch 96.

## Enthalten

- Feed- und Team-Feed-Uploadlimits werden nicht mehr hart im Code auf 100 MB beziehungsweise 20 Dateien gekappt.
- Die `.env` ist wieder führend.
- Der Code liefert nur sinnvolle Defaults, falls keine `.env`-Werte gesetzt sind:
  - `HH_UPLOAD_FEED_MEDIA_MB=100`
  - `HH_UPLOAD_FEED_MEDIA_COUNT=12`
  - `HH_UPLOAD_TEAM_FEED_MEDIA_MB=100`
  - `HH_UPLOAD_TEAM_FEED_MEDIA_COUNT=12`
- Composer-Hinweis und clientseitige Prüfung aus Patch 96 bleiben erhalten und nutzen weiterhin die Werte aus `config('hunthub.upload_limits...')`.

## Empfohlene .env-Werte für Launch

```env
HH_UPLOAD_FEED_MEDIA_MB=100
HH_UPLOAD_FEED_MEDIA_COUNT=12
HH_UPLOAD_TEAM_FEED_MEDIA_MB=100
HH_UPLOAD_TEAM_FEED_MEDIA_COUNT=12
```

Wenn später andere Werte gewünscht sind, können sie direkt in der `.env` geändert werden. Danach muss der Laravel-Config-Cache geleert werden.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
php artisan config:clear
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Nicht geändert

- keine Mail-Konfiguration
- keine Migration
- kein Composer
- keine Controller-Änderung
- keine View-Änderung
- keine JS-/CSS-Änderung
