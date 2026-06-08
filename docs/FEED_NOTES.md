# Hunthub Feed / Wall Basis

ZIP 06 baut die erste echte Wall-Grundlage.

## Neue Tabellen

- `feed_posts`
- `feed_post_media`
- `feed_comments`
- `feed_reactions`
- `feed_bookmarks`

## Neue Routen

- `GET /feed`
- `POST /feed`
- `PUT /feed/{post}`
- `DELETE /feed/{post}`
- `POST /feed/{post}/comments`
- `DELETE /feed/comments/{comment}`
- `POST /feed/{post}/reaction`
- `POST /feed/{post}/bookmark`

## MVP-Funktionen

- Textbeitrag erstellen
- bis zu 4 Bilder/Videos anhängen
- Beitrag bearbeiten
- Beitrag löschen
- Kommentare erstellen/löschen
- Like toggeln
- Beitrag speichern toggeln
- einfache Sichtbarkeit: öffentlich, Follower, privat

## Bewusst noch nicht enthalten

- echte Follower-Filterlogik
- Reactions-Auswahl
- Mentions
- Hashtags
- Shares/Reposts
- Reports/Moderation
- Team-Posts
- Fancybox/Plyr/echte Vikinger-Detailoptik
