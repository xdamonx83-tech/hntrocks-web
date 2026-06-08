# Patch 526 - SEO Public Sitemap & Indexable Pages v1

## Ziel

Die SEO-Basis aus Patch 523/525 wurde technisch weitergezogen:

- dynamische Sitemap statt statischer Sitemap-Datei
- öffentliche Crawl-Zugänglichkeit für sinnvolle Content-Seiten
- robots.txt an echte öffentliche Seiten angepasst
- strukturierte Daten für Cups, Loadout-Challenges, Cup-Ideen und Moment der Woche ergänzt
- öffentliche Profilrouten für wirklich öffentliche Profile vorbereitet

## Wichtigster Fix

Die alte `public/sitemap.xml` ist statisch. Wenn sie auf dem Server liegen bleibt, liefert der Webserver meistens diese Datei aus, bevor Laravel die neue Route `/sitemap.xml` erreicht.

Deshalb muss bei der Installation ausgeführt werden:

```bash
rm -f public/sitemap.xml
```

Danach übernimmt Laravel die dynamische Sitemap-Route.

## Geänderte Dateien

- `routes/web.php`
- `app/Http/Controllers/Seo/SitemapController.php`
- `app/Http/Controllers/Profile/ProfileController.php`
- `app/Http/Controllers/Moments/MomentOfWeekController.php`
- `resources/views/seo/sitemap.blade.php`
- `resources/views/themes/socialite/partials/head.blade.php`
- `resources/views/themes/socialite/profile/show.blade.php`
- `resources/views/themes/socialite/cups/index.blade.php`
- `resources/views/themes/socialite/cups/show.blade.php`
- `resources/views/themes/socialite/loadout-challenges/index.blade.php`
- `resources/views/themes/socialite/loadout-challenges/show.blade.php`
- `resources/views/themes/socialite/cup-ideas/index.blade.php`
- `resources/views/themes/socialite/moment-of-week/index.blade.php`
- `public/assets/socialite/css/hnt-app-palette.css`
- `public/robots.txt`

## Prüfung

- PHP-Lint für geänderte PHP-/Blade-Dateien ist sauber.
- `php artisan route:list` konnte im Container nicht ausgeführt werden, weil dort `mbstring` fehlt.
- Browser-/Live-Crawl muss nach Installation auf dem Server geprüft werden.
