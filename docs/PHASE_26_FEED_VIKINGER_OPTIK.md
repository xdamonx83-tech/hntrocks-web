# Phase 26 – Newsfeed Vikinger-Optik Basis

Dieser Schritt zieht den Newsfeed näher an `newsfeed.html` aus dem Vikinger-Template.

## Geändert

- `resources/views/feed/index.blade.php`
- `resources/views/feed/partials/post-card.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`
- `docs/ROADMAP.md`

## Inhalt

- Vikinger `section-banner` für den Feed
- Vikinger `grid grid-3-6-3 mobile-prefer-content`
- linke Feed-Spalte mit Profilfortschritt, Level und XP
- mittlere Feed-Spalte mit `quick-post` Composer
- Feed-Posts als `widget-box no-padding`
- Post-Header mit Vikinger-User-Status-Struktur
- Medienanzeige passend in der Post-Box
- Post-Optionen für Like, Kommentar und Speichern im Vikinger-Stil
- Kommentarbereich stärker an Vikinger angepasst
- rechte Feed-Spalte mit Feed-Status und nächsten Schritten

## Bewusst noch nicht enthalten

- endgültige Reactions-Auswahl wie im Template
- Polls
- Blog-Post-Typ
- echte Following/Friends-Feed-Logik
- finale Detailoptik für Profil, Teams, LFG und Messages

Die Funktionslogik bleibt unverändert. Es gibt keine Migration.
