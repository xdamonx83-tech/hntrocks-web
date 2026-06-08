# Phase 36 – Feed sauber Richtung Vikinger newsfeed.html

Basis: Phase 35 / stabiler Rollback-Stand.

Geändert:
- `resources/views/feed/index.blade.php`
- `resources/views/feed/partials/post-card.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

Ziel:
- Feed-Layout stärker an `newsfeed.html` angleichen.
- Feed-Karten nicht mehr durch standardmäßig offene Kommentare zerreißen.
- Kommentare nur noch über `#comments-{id}`/Comment-Link öffnen.
- Linke Widgets näher an Vikinger: Profilfortschritt, Featured Badges, Members, Open Quests.
- Rechte Widgets näher an Vikinger: Stats Box, Reactions Received, Friends Activity, Groups.
- Keine neuen Tabellen, keine Migration, keine neue Route.

Bewusst nicht enthalten:
- echte Friend-Request-Funktion
- neuer Kommentar-Detailmodus
- neue Reaktionstypen jenseits bestehendem Like-System
