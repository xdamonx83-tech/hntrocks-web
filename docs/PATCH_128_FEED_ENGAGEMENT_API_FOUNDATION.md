# Patch 128 — Feed Engagement API Foundation

Ziel: Native Android-App auf Feed-Reactions und Kommentare vorbereiten.

## Neue API-Endpunkte

- `GET /api/v1/feed/{post}/comments`
- `POST /api/v1/feed/{post}/comments`
- `POST /api/v1/feed/{post}/reaction`

Alle Endpunkte liegen hinter `api.token` und verwenden die bestehende Feed-Sichtbarkeitslogik `canBeViewedBy()`.

## Geänderte Dateien

- `routes/api.php`
- `app/Http/Controllers/Api/V1/ApiFeedEngagementController.php`
- `app/Http/Resources/Api/FeedCommentResource.php`

## Nicht enthalten

- Keine Migration
- Keine Composer-Abhängigkeit
- Keine Android-UI-Änderung
- Keine WebView
- Noch keine Comment-Reaction-API

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 128_hnt_feed_engagement_api_foundation.zip -d .
composer dump-autoload
php artisan optimize:clear
php artisan route:clear
php artisan view:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Test

```bash
curl https://hnt.rocks/api/v1/feed/POST_ID/comments \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN"
```

```bash
curl -X POST https://hnt.rocks/api/v1/feed/POST_ID/reaction \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d "type=like"
```

```bash
curl -X POST https://hnt.rocks/api/v1/feed/POST_ID/comments \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -d "body=Android API Kommentar Test"
```
