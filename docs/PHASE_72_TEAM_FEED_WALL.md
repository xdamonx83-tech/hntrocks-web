# Phase 72 – Team Feed / Team Wall

## Ziel

Die Team-Detailseite erhält eine echte Team-Timeline im Stil einer Gruppen-Wall. Teammitglieder können direkt auf der Teamseite Updates posten. Die alte Team-Discussion-Richtung wird dafür nicht weiter ausgebaut.

## Geänderte Dateien

- `routes/web.php`
- `app/Http/Controllers/Teams/TeamController.php`
- `app/Http/Controllers/Teams/TeamFeedController.php`
- `app/Http/Controllers/Feed/FeedController.php`
- `app/Http/Controllers/Feed/FeedCommentController.php`
- `app/Http/Controllers/Feed/FeedReactionController.php`
- `app/Http/Controllers/Feed/FeedBookmarkController.php`
- `app/Http/Controllers/Feed/FeedCommentReactionController.php`
- `app/Models/FeedPost.php`
- `app/Models/Team.php`
- `database/migrations/2026_05_01_000072_add_team_id_to_feed_posts_table.php`
- `resources/views/teams/show.blade.php`
- `resources/views/feed/partials/post-card.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Technische Umsetzung

- `feed_posts` bekommt ein optionales `team_id`.
- Normale globale Feed-Posts bleiben `team_id = null`.
- Team-Posts werden als normale FeedPosts mit `visibility = team` gespeichert.
- Der globale Feed blendet Team-Posts aus.
- Die Team-Detailseite lädt die letzten 10 Team-Posts.
- Teammitglieder können neue Team-Posts inklusive Medien anhängen.
- Kommentare, Reactions und Bookmarks nutzen die bestehende Feed-Logik weiter.
- Private Teams bleiben geschützt: Team-Posts privater Teams sind nur für aktive Teammitglieder sichtbar.
- Öffentliche Teams können von eingeloggten Nutzern gelesen werden; posten dürfen nur aktive Teammitglieder.

## Deployment

Neue Migration enthalten:

```bash
cd /home/users/hunthub/www/social.hunthub.online
composer dump-autoload
php artisan migrate
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

`composer install` ist nicht nötig.

## Testpfade

- `/teams/{team-slug}`
- `/feed`
- `/teams/manage`

## Tests

1. Als Teammitglied `/teams/{team-slug}` öffnen.
2. Team-Update posten.
3. Prüfen, ob der Post in der Team-Timeline erscheint.
4. Prüfen, ob der Post nicht im globalen `/feed` erscheint.
5. Kommentar und Reaction im Team-Post testen.
6. Private Teamseite als Nichtmitglied prüfen.
