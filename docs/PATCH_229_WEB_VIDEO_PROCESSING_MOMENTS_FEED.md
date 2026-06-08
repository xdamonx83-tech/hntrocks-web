# Patch 229 – Web Video Processing für Feed + Moments

## Ziel

Videos sollen nicht dauerhaft in Originalgröße liegen bleiben. Feed/Team-Feed und Moments nutzen jetzt denselben serverseitigen ffmpeg-Worker.

## Was geändert wurde

- Bestehender Feed-Transcoding-Job wurde erweitert statt durch neue Klassen ersetzt.
- Feed- und Team-Feed-Videos werden weiterhin im Seitenverhältnis optimiert.
- Moments-Videos werden auf 9:16 normalisiert und als MP4 H.264/AAC ausgegeben.
- Nach erfolgreicher Komprimierung wird die Originaldatei gelöscht.
- Für Videos wird ein Thumbnail erzeugt, wenn ffmpeg das Bild erzeugen kann.
- Moments bekommen `processing_status = processing/ready/failed` passend zur Verarbeitung.
- Neue Jobs werden erst nach der HTTP-Antwort dispatcht, damit Feed-Relationen/Moment-Datensätze sauber angelegt sind, bevor der Worker läuft.
- Die API liefert `processing_status` für Moments mit aus.

## Standardwerte

Feed/Team-Feed:

- Maximal 1280x1280
- 30 FPS
- H.264/AAC MP4
- CRF 24
- Preset medium
- Audio 128k

Moments:

- 720x1280
- 9:16 Crop
- 30 FPS
- H.264/AAC MP4
- CRF 24
- Preset medium
- Audio 128k

## .env Optionen

Allgemein:

```env
HH_VIDEO_FFMPEG_BINARY=ffmpeg
HH_VIDEO_FFPROBE_BINARY=ffprobe
HH_VIDEO_TRANSCODING_QUEUE=media
```

Feed:

```env
HH_FEED_VIDEO_TRANSCODING_ENABLED=true
HH_FEED_VIDEO_MAX_WIDTH=1280
HH_FEED_VIDEO_MAX_HEIGHT=1280
HH_FEED_VIDEO_FPS=30
HH_FEED_VIDEO_CRF=24
HH_FEED_VIDEO_PRESET=medium
HH_FEED_VIDEO_AUDIO_BITRATE=128k
HH_FEED_VIDEO_DELETE_ORIGINAL=true
```

Moments:

```env
HH_MOMENT_VIDEO_TRANSCODING_ENABLED=true
HH_MOMENT_VIDEO_WIDTH=720
HH_MOMENT_VIDEO_HEIGHT=1280
HH_MOMENT_VIDEO_FPS=30
HH_MOMENT_VIDEO_CRF=24
HH_MOMENT_VIDEO_PRESET=medium
HH_MOMENT_VIDEO_AUDIO_BITRATE=128k
HH_MOMENT_VIDEO_DELETE_ORIGINAL=true
```

## Queue

Der bestehende Artisan-Befehl wurde erweitert:

```bash
php artisan hnt:feed-video-transcode-pending --limit=20
php artisan hnt:feed-video-transcode-pending --sync --limit=5
```

Der Name bleibt aus Kompatibilitätsgründen erhalten, verarbeitet jetzt aber Feed-, Team-Feed- und Moments-Videos.

## Wichtig

Für produktive Nutzung sollte `QUEUE_CONNECTION=database` oder `redis` gesetzt sein und ein Queue-Worker laufen. Bei `sync` wird ffmpeg direkt im Request ausgeführt und kann Uploads spürbar verlangsamen.
