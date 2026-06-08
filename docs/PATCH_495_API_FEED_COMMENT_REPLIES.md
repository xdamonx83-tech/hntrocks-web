# Patch 495: API Feed Comment Replies v1

Ziel: Die Android-App soll Feed-Tree-Comments anzeigen können. Dafür liefert die API `/api/v1/feed/{post}/comments` jetzt Root-Kommentare inklusive direkter Antworten unter `replies` aus.

Geändert:
- `FeedComment` erhält eine `replies()`-Relation.
- API-Kommentarlistung lädt direkte Antworten inklusive Autor, Viewer-Reaction und Reaktionszähler.
- `FeedCommentResource` gibt `replies_count` und `replies` aus, wenn die Relation geladen ist.

Nicht geändert:
- Keine DB-Migration.
- Keine Web-Views.
- Keine Routen.
- Keine bestehende Kommentar-Logik.

Installation:
```bash
composer dump-autoload
php artisan optimize:clear
php artisan view:clear
```
