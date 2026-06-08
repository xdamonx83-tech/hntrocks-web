# Patch 496: API Cup Feedback für Android v1

## Änderung
- Neue API-Endpunkte für die native Android-App:
  - `GET /api/v1/cup-feedback`
  - `POST /api/v1/cup-feedback`
- Die API nutzt die bestehende Tabelle `cup_feedback_entries` aus Patch 487.
- Keine Migration.
- Keine Web-Views.
- Keine Änderung an Cup-Wertung, Cup-Submissions oder Admin-Logik.

## Dateien
- `app/Http/Controllers/Api/V1/ApiCupFeedbackController.php`
- `routes/api.php`

## Installation
```bash
composer dump-autoload
php artisan route:clear
php artisan optimize:clear
php artisan view:clear
```
