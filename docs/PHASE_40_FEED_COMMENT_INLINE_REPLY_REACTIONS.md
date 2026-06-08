# Phase 40 – Feed: Inline-Reply pro Kommentar + Kommentar-Reactions

Basis: Phase 39.

## Ziel
- Reply auf einen Kommentar öffnet ein eigenes Antwortfeld direkt unter genau diesem Kommentar.
- Antworten bleiben auf eine Ebene beschränkt und werden weiter als Child-Kommentar mit `parent_id` gespeichert.
- `React!` auf Kommentaren funktioniert per AJAX ohne Reload.

## Geändert
- Neue Tabelle `feed_comment_reactions` für Kommentar-Reactions.
- Neuer Controller `FeedCommentReactionController`.
- Neue Route `feed.comments.reactions.toggle`.
- `FeedComment` um `reactions()` und `viewerReaction()` erweitert.
- Feed-Query lädt Kommentar-Reactions mit.
- `post-card.blade.php` zeigt Inline-Replies pro Root-Kommentar.
- JS behandelt Kommentar-Reactions und Inline-Reply-Composer.
- CSS stabilisiert das eingeblendete Kommentar-Antwortfeld.

## Deployment
Neue Migration und neue PHP-Klassen vorhanden:

```bash
cd /home/users/hunthub/www/social.hunthub.online
composer dump-autoload
php artisan migrate
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Kein `composer install`, weil keine neue Composer-Abhängigkeit hinzugefügt wurde.
