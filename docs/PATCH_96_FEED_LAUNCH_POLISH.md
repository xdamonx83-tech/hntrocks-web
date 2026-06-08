# Patch 96 – Feed-/Video-/Composer Launch-Polish

Dieser Patch bleibt bewusst klein und ändert keine Mail-, DB- oder Composer-Konfiguration.

## Enthalten

- Feed- und Team-Feed-Medienlimits werden launch-sicher gekappt:
  - maximal 100 MB pro Datei
  - maximal 20 Dateien pro Beitrag, auch wenn in der .env noch höhere Werte stehen
- Default für Feed-/Team-Feed-Medien liegt bei 100 MB pro Datei.
- Composer zeigt einen kurzen Upload-Hinweis mit aktuellem Limit und Dateianzahl.
- Clientseitige Prüfung verhindert zu große Dateien und zu viele Dateien schon vor dem Upload.
- KI-Regler bleibt weiterhin nur sichtbar, wenn Medien ausgewählt wurden.
- Upload-Button nutzt i18n statt hartcodiertem deutschen Text.

## Wichtig

Die .env kann weiterhin so gesetzt werden:

```env
HH_UPLOAD_FEED_MEDIA_MB=100
HH_UPLOAD_FEED_MEDIA_COUNT=12
HH_UPLOAD_TEAM_FEED_MEDIA_MB=100
HH_UPLOAD_TEAM_FEED_MEDIA_COUNT=12
```

Höhere Werte werden für Feed/Team-Feed durch diesen Patch auf 100 MB beziehungsweise 20 Dateien gekappt.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
php artisan config:clear
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```
