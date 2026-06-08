# Patch 507 – Legal Pages Socialite Blog-Read Style

## Ziel
Impressum, Datenschutz, Nutzungsbedingungen und Konto-Löschung optisch an das aktuelle Socialite/HNT-App-Design angleichen. Als Referenz wurde `blog-read.html` aus dem Socialite-Template genutzt: Artikelansicht mit großem Hero, Content-Card und rechter Navigationsspalte.

## Geändert
- Vier Legal-Seiten auf das Socialite-App-Layout umgestellt.
- Alter `layouts.app`-Look ersetzt durch `themes.socialite.layouts.app`.
- Blog-Read-artige Artikelstruktur ergänzt:
  - großer dunkler Legal-Hero
  - Kicker/Seitentyp
  - Zusammenfassung
  - Stand-Datum im Hero
  - Hauptartikel links
  - Legal-Navigation rechts
  - Kontaktbox rechts
- HNT-App-Palette für rechtliche Artikel ergänzt.
- CSS-Version von `v=506` auf `v=507` erhöht.

## Dateien
- `resources/views/legal/impressum.blade.php`
- `resources/views/legal/datenschutz.blade.php`
- `resources/views/legal/nutzungsbedingungen.blade.php`
- `resources/views/legal/account-deletion.blade.php`
- `resources/views/themes/socialite/partials/head.blade.php`
- `public/assets/socialite/css/hnt-app-palette.css`

## Nicht geändert
- Keine Routen
- Keine Controller
- Keine Datenbank
- Keine Migration
- Keine Android-Dateien
- Netiquette und Child-Safety wurden bewusst nicht angefasst, weil der Auftrag nur die vier genannten Seiten betraf.

## Installation
```bash
php artisan optimize:clear
php artisan view:clear
```

Danach im Browser hart neu laden.
