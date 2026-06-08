# Patch 138 – Android Comment Reactions

## Inhalt

- Ergänzt eine Mobile-API für Feed-Kommentar-Reaktionen.
- Android-Kommentar-Sheet kann jetzt Kommentare mit den gleichen Reaction-Typen wie Feed-Posts reagieren.
- Reaktion wird lokal aktualisiert, ohne den gesamten Feed neu zu laden.
- Kommentar-Sheet bleibt offen.

## Neue API-Route

POST /api/v1/feed/comments/{comment}/reaction

Parameter:

- type: like, love, dislike, happy, funny, wow, angry, sad
- mode: set oder toggle

## Geänderte Dateien

- routes/api.php
- app/Http/Controllers/Api/V1/ApiFeedEngagementController.php
- app/src/main/java/rocks/hnt/app/data/HntApiClient.kt
- app/src/main/java/rocks/hnt/app/data/HntRepository.kt
- app/src/main/java/rocks/hnt/app/ui/AppViewModel.kt
- app/src/main/java/rocks/hnt/app/ui/HntApp.kt

## Deployment Laravel

composer dump-autoload
php artisan optimize:clear
php artisan route:clear
php artisan view:clear

## Android

ZIP direkt in den Android-Projektordner entpacken und danach Gradle Sync/Clean/Rebuild ausführen.
