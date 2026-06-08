# 051 – API: Message Attachments

## Enthalten
- API-Messages akzeptieren optional Medien-Anhänge per Multipart.
- `POST /api/v1/messages/{conversation}` akzeptiert:
  - `body` nullable, aber Pflicht wenn keine Anhänge vorhanden sind
  - `attachments[]` bis 4 Dateien
  - erlaubte Typen: JPEG, PNG, WebP, GIF, MP4, MOV, WebM
- Medien werden über den bestehenden `MediaService` im Kontext `messages` gespeichert.
- Anhänge werden an das Message-Model via `media_assets.attachable_type/attachable_id` gekoppelt.
- API-Message-Payload liefert `attachments` mit URL, Thumbnail-URL, Typ, MIME, Größe, Breite/Höhe.

## Keine Migration nötig
Es wird die vorhandene `media_assets`-Tabelle mit polymorpher Verknüpfung genutzt.
