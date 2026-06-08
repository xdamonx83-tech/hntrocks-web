# Medien-/Upload-System – Schritt 11

## Ziel

Dieser Schritt führt eine zentrale Mediengrundlage ein, damit Feed, Profil, Teams, Nachrichten, Moments und Cups später nicht jeweils eigene Upload-Logik bekommen.

## Neue Tabelle

- `media_assets`

## Erweiterte Tabelle

- `feed_post_media.media_asset_id`

## Neue Dateien

- `app/Models/MediaAsset.php`
- `app/Services/MediaService.php`
- `app/Http/Controllers/Media/MediaController.php`
- `resources/views/media/index.blade.php`

## Neue Route

- `GET /media`
- `POST /media`
- `DELETE /media/{asset}`

## Bereits angebunden

- Feed-Medien werden zusätzlich als `media_assets` gespeichert.
- Profil-Avatar und Profil-Cover laufen ab jetzt über `MediaService`.
- Team-Avatar und Team-Cover laufen ab jetzt über `MediaService`.

## Bewusst noch nicht enthalten

- Bildzuschnitt/Crop
- Thumbnail-Generierung über Queue
- Video-Kompression
- FFmpeg-Verarbeitung
- Moderation/KI-Prüfung
- Medienauswahl-Modal für bestehende Medien
- Anhänge in privaten Nachrichten

Diese Punkte folgen später, wenn die Funktionsbasis stabil steht.
