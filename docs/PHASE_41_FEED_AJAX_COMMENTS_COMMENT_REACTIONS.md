# Phase 41 – Feed: AJAX-Kommentare und Kommentar-Reaction-Auswahl

Basis: Phase 40.

## Änderungen

- Kommentare werden per AJAX abgeschickt, ohne Feed-Reload.
- Root-Kommentare werden nach dem Absenden direkt in die Kommentar-Liste eingefügt.
- Tree-Replies werden direkt unter dem Zielkommentar eingefügt.
- Der Kommentarzähler im Beitrag wird nach AJAX-Kommentar aktualisiert.
- Kommentar-Reactions haben jetzt dieselbe Reaction-Auswahl wie Post-Reactions.
- Kommentar-Reaction-Button zeigt nach Auswahl den gewählten Reaction-Typ statt nur Like.

## Bewusst nicht geändert

- Keine neue Migration.
- Keine neue Route.
- Keine Profil-/Friend-Funktion.
- Keine Änderung an Feed-Post-Reactions oder Widget-Logik außer Nutzung vorhandener JS-Helfer.

## Geänderte Dateien

- app/Http/Controllers/Feed/FeedCommentController.php
- resources/views/feed/partials/post-card.blade.php
- resources/views/feed/partials/comment-item.blade.php
- public/assets/vikinger/js/hunthub-start.js
- public/assets/vikinger/css/hunthub-vikinger-start.css
