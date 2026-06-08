# Moments / Reels Basis

ZIP 14 fuegt die erste technische Moments-Grundlage hinzu.

## Enthalten

- Tabelle `moments`
- Tabelle `moment_comments`
- Tabelle `moment_reactions`
- Tabelle `moment_bookmarks`
- Video-Upload ueber den zentralen `MediaService`
- optionaler Cover-Upload
- 9:16-Viewer als technische Basis
- Deep-Link-Route `/moments/r/{id}`
- Likes
- Kommentare
- Speichern/Bookmarks
- einfache Sichtbarkeit: public, registered, private
- gespeicherte Trim-Werte: Start/Ende in Sekunden
- API-Vorbereitung: `/api/v1/moments`
- XP-Hooks fuer Moment erstellen, liken, speichern und kommentieren
- Badge/Quest: erster Moment

## Bewusst noch nicht enthalten

- echte ffmpeg-Kompression
- echtes serverseitiges Schneiden
- automatische Thumbnail-Erzeugung aus Video
- TikTok-artiger Swipe-Feed
- For-You-Algorithmus
- Following-Feed
- Moderation/AI-Pruefung
- Fullscreen Mobile-Feinschliff

Diese Punkte sollen spaeter separat gebaut werden, damit die Grundlage stabil bleibt.
