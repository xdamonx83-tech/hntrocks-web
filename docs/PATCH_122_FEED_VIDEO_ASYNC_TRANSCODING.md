# Patch 122 — Feed Video Async Transcoding

## Ziel

Feed-Videos werden nach dem Upload asynchron per Queue mit ffmpeg in eine web-/app-tauglichere MP4-Datei transcodiert. Dadurch müssen Web und Android-App nicht mehr dauerhaft die hochgeladene Originaldatei von 90–100 MB ausliefern.

## Verhalten

- Nur Feed-Videos (`context = feed`, `type = video`) werden verarbeitet.
- Der Upload bleibt funktional; das Originalvideo ist während der Verarbeitung weiterhin abrufbar.
- Das MediaAsset bekommt währenddessen `status = processing` und Metadaten unter `metadata.feed_transcoding`.
- Nach erfolgreichem ffmpeg-Lauf wird die Datei durch eine optimierte MP4 ersetzt.
- Standard: max. 1280px Breite, H.264, AAC, `+faststart`, CRF 28.
- Bei Fehlern bleibt das Originalvideo erhalten und das MediaAsset wird wieder `ready` gesetzt. Der Fehler steht in den Metadaten und im Laravel-Log.

## Neue/optionale ENV-Werte

```env
QUEUE_CONNECTION=database
HH_FEED_VIDEO_TRANSCODING_ENABLED=true
HH_FEED_VIDEO_TRANSCODING_QUEUE=media
HH_FEED_VIDEO_FFMPEG_BINARY=ffmpeg
HH_FEED_VIDEO_MAX_WIDTH=1280
HH_FEED_VIDEO_CRF=28
HH_FEED_VIDEO_PRESET=veryfast
HH_FEED_VIDEO_AUDIO_BITRATE=128k
HH_FEED_VIDEO_DELETE_ORIGINAL=true
HH_FEED_VIDEO_TRANSCODING_TIMEOUT=900
```

Wichtig: Wenn `QUEUE_CONNECTION=sync` bleibt, läuft der Job nicht wirklich im Hintergrund.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 122_hnt_feed_video_async_transcoding.zip -d .

composer dump-autoload
php artisan migrate
php artisan config:clear
php artisan optimize:clear
```

Danach `.env` setzen:

```env
QUEUE_CONNECTION=database
```

Und einen Worker starten:

```bash
php artisan queue:work database --queue=media,default --sleep=2 --tries=1 --timeout=1200
```

Für einen ersten manuellen Test ohne dauerhaft laufenden Worker:

```bash
php artisan hnt:feed-video-transcode-pending --sync --limit=5
```

Für später sollte der Worker dauerhaft per Supervisor/KeyHelp/Service laufen.

## Geänderte Dateien

- `config/hunthub.php`
- `app/Services/MediaService.php`
- `app/Jobs/TranscodeFeedVideo.php`
- `app/Http/Resources/Api/FeedPostMediaResource.php`
- `routes/console.php`
- `database/migrations/2026_05_10_000122_create_queue_tables_for_feed_video_transcoding.php`

## Kein Bestandteil

- Kein Android-App-Patch
- Kein Web-Design-Rework
- Keine Änderung an Mail
- Keine Änderung an Cup-Scoring
- Keine sofortige Verarbeitung alter Videos außer über den Artisan-Befehl
