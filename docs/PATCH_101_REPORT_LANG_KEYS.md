# Patch 101 – Report-Sprachkeys nachziehen

## Zweck

Der Pre-Launch-Check aus Patch 100 meldete zwei fehlende UI-Sprachkeys:

- `ui.report_cup`
- `ui.report_moment`

Patch 101 ergänzt ausschließlich diese fehlenden Keys in Deutsch und Englisch.

## Geänderte Dateien

- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Nicht geändert

- keine Routen
- keine Controller
- keine Views
- keine CSS-/JS-Dateien
- keine Mail-Konfiguration
- keine Migration
- kein Composer

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 101_hnt_report_lang_keys.zip -d .

php artisan view:clear
php artisan optimize:clear
php artisan hnt:prelaunch-check

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```
