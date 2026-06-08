# Patch 528 – SEO Cups & Loadout-Challenges Content v1

## Ziel

Cups und Loadout-Challenges sollen nicht nur technisch indexierbar sein, sondern als echte öffentliche Content-Seiten mehr Suchwert bekommen.

## Änderungen

- Cup-Übersicht: noindex für gefilterte Such-/Statusseiten, Canonical bleibt auf der Hauptübersicht.
- Cup-Übersicht: sichtbarer SEO-Introblock mit interner Verlinkung zu Hall of Fame und Loadout-Challenges.
- Cup-Übersicht: JSON-LD jetzt als Graph mit CollectionPage, BreadcrumbList und ItemList der sichtbaren Cups.
- Cup-Detailseite: Untersektionen wie Regeln/Leaderboard/Submit bleiben canonical auf der Hauptseite und werden noindex,follow, damit keine Duplicate-Content-Unterseiten indexiert werden.
- Cup-Detailseite: Event-JSON-LD erweitert und die VirtualLocation sauber gehalten.
- Loadout-Übersicht: noindex für gefilterte Statusseiten, Canonical bleibt auf der Hauptübersicht.
- Loadout-Übersicht: sichtbarer SEO-Erklärblock mit interner Verlinkung zu Cups und Moment der Woche.
- Loadout-Übersicht: JSON-LD jetzt als Graph mit CollectionPage, BreadcrumbList und ItemList der sichtbaren Challenges.
- Loadout-Detailseite: CreativeWork-JSON-LD erweitert um Breadcrumb, Hunt: Showdown-Bezug, mainEntityOfPage, kostenlose Zugänglichkeit und Interaktionszählung der angenommenen Einreichungen.
- CSS-Version auf v=528 erhöht.

## Geänderte Dateien

- resources/views/themes/socialite/cups/index.blade.php
- resources/views/themes/socialite/cups/show.blade.php
- resources/views/themes/socialite/loadout-challenges/index.blade.php
- resources/views/themes/socialite/loadout-challenges/show.blade.php
- resources/views/themes/socialite/partials/head.blade.php
- resources/lang/de/ui.php
- resources/lang/en/ui.php
- public/assets/socialite/css/hnt-app-palette.css
