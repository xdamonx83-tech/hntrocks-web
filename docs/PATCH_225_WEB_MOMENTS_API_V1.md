# Patch 225 - Moments API V1

## Ziel

Die Android-App soll Moments nicht als Platzhalter oder WebView anzeigen, sondern später nativ als Reels-/TikTok-artigen Feed laden können.

## Geändert

- `routes/api.php`
- `app/Http/Controllers/Api/V1/ApiMomentsController.php`
- `app/Http/Resources/Api/MomentResource.php`
- `app/Http/Resources/Api/MomentCommentResource.php`

## Neue API-Routen

- `GET /api/v1/moments`
- `GET /api/v1/moments/{moment}`
- `POST /api/v1/moments/{moment}/like`
- `POST /api/v1/moments/{moment}/bookmark`
- `GET /api/v1/moments/{moment}/comments`
- `POST /api/v1/moments/{moment}/comments`
- `DELETE /api/v1/moments/comments/{comment}`

## Nicht enthalten

- kein Android-Video-Player
- kein Moments-Upload in der App
- keine Migration
- keine Web-Optik-Änderung
