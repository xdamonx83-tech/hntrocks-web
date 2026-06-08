# Patch 104 — Mobile API MVP Foundation

Ziel: Erste echte API-Basis für die spätere native Android-App schaffen, ohne WebView, ohne neue DB-Struktur und ohne bestehende Web-Funktionen umzubauen.

## Neue/erweiterte API-Endpunkte

Alle Endpunkte liegen hinter bestehender Bearer-Token-Auth (`api.token`), außer bestehende Login/Register/Health-Endpunkte.

- `GET /api/v1/bootstrap`
  - ergänzt App-Endpunktübersicht und Uploadlimits für den Android-Client.
- `GET /api/v1/feed`
  - lädt Feed-Posts mit Autor, Medien, Shared-Post-Grunddaten, Counts und Viewer-State.
- `GET /api/v1/feed/{post}`
  - lädt einen einzelnen sichtbaren Feed-Post.
- `POST /api/v1/feed`
  - erstellt einen Feed-Post mit Text und optionalen Medien.
  - nutzt bestehende Feed-Uploadlimits, MediaService, Medienmoderation, AI-Disclosure, Gamification und Mentions.
- `GET /api/v1/cups/{slug}`
  - lädt Cup-Details inklusive Regeln, Beschreibung, Scoring, Preisen, Leaderboard und Viewer-Team/Submissions.
- `POST /api/v1/notifications/{notification}/read`
  - markiert eine eigene Notification als gelesen.
- `POST /api/v1/notifications/read-all`
  - markiert alle eigenen Standard-Notifications als gelesen.

## Geänderte Dateien

- `routes/api.php`
- `routes/console.php`
- `app/Http/Controllers/Api/V1/ApiBootstrapController.php`
- `app/Http/Controllers/Api/V1/ApiFeedController.php`
- `app/Http/Controllers/Api/V1/ApiCupsController.php`
- `app/Http/Controllers/Api/V1/ApiNotificationController.php`
- `app/Http/Resources/Api/CupResource.php`
- `app/Http/Resources/Api/FeedPostResource.php`
- `app/Http/Resources/Api/CupLeaderboardEntryResource.php`
- `app/Http/Resources/Api/CupSubmissionResource.php`

## Nicht enthalten

- keine Migration
- kein Composer-Paket
- kein Mail-Umbau
- kein WebView
- kein Chat
- kein Push
- keine Cup-Screenshot-Submission per API
- keine native Android-App-Dateien

## Hinweise

Da neue PHP-Klassen hinzugefügt wurden, sollte nach dem Entpacken `composer dump-autoload` ausgeführt werden. Eine Migration ist nicht nötig.
