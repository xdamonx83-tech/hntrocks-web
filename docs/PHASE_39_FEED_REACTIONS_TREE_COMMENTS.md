# Phase 39 – Feed: Reaction-Typen, echte Sidebar-Reaction-Werte, 1-Stufen-Tree-Kommentare

Basis: Phase 38.

## Inhalt

- Feed-Reaction-Button zeigt nach Auswahl den tatsächlich gewählten Reaction-Typ an.
- Reaction-Picker setzt `type` per AJAX weiter ohne Reload.
- Reactions-Received-Widget nutzt echte `feed_reactions.type`-Werte der Beiträge des eingeloggten Nutzers statt Demo-/Mischwerte.
- Kommentar-Replies werden als echte 1-Stufen-Tree-Kommentare über `feed_comments.parent_id` gespeichert und angezeigt.
- Replies auf Replies werden absichtlich auf die Root-Antwort reduziert, damit keine tiefe, unkontrollierte Thread-Struktur entsteht.
- Kommentar-Reply-Composer kann unter den Zielkommentar wandern und mit Cancel zurückgesetzt werden.

## Geänderte Dateien

- `app/Http/Controllers/Feed/FeedController.php`
- `app/Http/Controllers/Feed/FeedCommentController.php`
- `app/Http/Controllers/Feed/FeedReactionController.php`
- `resources/views/feed/index.blade.php`
- `resources/views/feed/partials/post-card.blade.php`
- `public/assets/vikinger/js/hunthub-start.js`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Deployment

Keine neuen Klassen, keine neuen Tabellen, keine Migrationen, keine Composer-Abhängigkeiten.

```bash
cd /home/users/hunthub/www/social.hunthub.online
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```
