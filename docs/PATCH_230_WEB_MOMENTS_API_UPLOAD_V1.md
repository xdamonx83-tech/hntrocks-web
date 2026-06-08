# Patch 230 – Web/API Moments Upload V1

## Ziel

Bereitet den nativen Android-Moments-Composer vor, ohne die Android-App in diesem Patch anzufassen.

## Änderung

Ergänzt wurde ein authentifizierter API-Endpunkt:

```text
POST /api/v1/moments
```

Der Endpunkt nimmt dieselben Kernfelder wie der bestehende Web-Moments-Upload an:

- `video` – Pflichtdatei, MP4/MOV/WebM
- `cover` – optionales Bild
- `caption` – optional, maximal 220 Zeichen
- `description` – optional, maximal 2000 Zeichen
- `visibility` – optional, `public`, `registered` oder `private`; Standard: `public`
- `trim_start_seconds` – optional
- `trim_end_seconds` – optional

## Verhalten

- nutzt den bestehenden `MediaService`
- nutzt die Upload-Limits aus `config/hunthub.php`
- nutzt die serverseitige Moments-Komprimierung aus Patch 229
- erstellt den Moment mit `processing_status=processing`, wenn das Video in die Queue geht
- verknüpft Video und optionales Cover sauber mit dem Moment
- vergibt weiterhin Gamification für `moment_created`
- liefert direkt eine API-Resource des Moments zurück

## Bewusst nicht geändert

- keine Migration
- keine neuen Models
- keine neue Queue-Logik
- keine Android-Änderung
- kein Upload-Composer in der App
- keine Web-Optik
