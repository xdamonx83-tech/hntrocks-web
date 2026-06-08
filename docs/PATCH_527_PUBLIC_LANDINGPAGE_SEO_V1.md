# Patch 527 – Public Landingpage / SEO Homepage v1

## Ziel

`/` soll für Gäste keine reine Login-Weiterleitung mehr sein, sondern eine indexierbare öffentliche Startseite mit klarer Erklärung von HNT.rocks. Eingeloggte Nutzer werden weiterhin in den Feed geleitet.

## Änderungen

- Root-Route `/` nutzt jetzt `LandingPageController`.
- Gäste sehen eine öffentliche Socialite/HNT-Landingpage.
- Eingeloggte Nutzer werden weiter nach `/feed` geleitet.
- Neue öffentliche Landing-View mit:
  - Hero-Bereich
  - HNT-/Hunter-Card-Teaser
  - dynamischen Plattform-Statistiken
  - Feature-Karten für Cups, Loadout-Challenges und Moments
  - aktuelle Cups, Loadout-Challenges und öffentliche Moments
  - Login/Register/App-Beta-Verlinkung
- Eigenes schlankes Public-Socialite-Layout ohne App-Sidebar.
- SEO-Daten für die Startseite:
  - Title
  - Meta Description
  - Canonical
  - OpenGraph/Twitter über bestehendes SEO-Partial
  - JSON-LD `WebPage`
- Dynamische Sitemap nimmt `/` mit hoher Priorität auf.
- Deutsche und englische UI-Texte ergänzt.
- CSS-Version auf `v=527` erhöht.

## Geänderte Dateien

- `routes/web.php`
- `app/Http/Controllers/Marketing/LandingPageController.php`
- `app/Http/Controllers/Seo/SitemapController.php`
- `resources/views/themes/socialite/layouts/public.blade.php`
- `resources/views/themes/socialite/landing/index.blade.php`
- `resources/views/themes/socialite/partials/head.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`
- `public/assets/socialite/css/hnt-app-palette.css`

## Prüfung

- PHP-Lint für geänderte PHP-Dateien: sauber.
- PHP-Lint für geänderte Blade-Dateien: sauber.
- PHP-Lint für Sprachdateien: sauber.
- `php artisan route:list` konnte im lokalen Container weiterhin nicht geprüft werden, weil `mbstring` fehlt.

## Installation

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip -o /pfad/zur/527_hntrocks_public_landingpage_seo_v1.zip -d /home/users/hunthub/www/hnt.rocks

composer dump-autoload
php artisan route:clear
php artisan optimize:clear
php artisan view:clear

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```
