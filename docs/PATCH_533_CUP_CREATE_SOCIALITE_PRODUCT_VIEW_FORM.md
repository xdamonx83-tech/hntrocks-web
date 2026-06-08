# Patch 533 – Cup Create Socialite Product View Form

## Ziel

Die bisherige `/cups/create`-Maske nutzte noch das alte App/Vikinger-Layout. Diese Version stellt die Cup-Erstellung auf die aktive Socialite-Struktur um und orientiert sich optisch an `product-view-2.html`.

## Änderungen

- `resources/views/cups/create.blade.php` nutzt jetzt `themes.socialite.layouts.app`.
- `resources/views/cups/edit.blade.php` nutzt ebenfalls Socialite, weil beide Seiten dasselbe Cup-Formular verwenden.
- `resources/views/cups/partials/cup-form.blade.php` wurde optisch auf eine Product-View-2-artige Struktur umgebaut:
  - linker Hauptbereich mit Hero-/Preview-Karte
  - Setup-, Zeitraum-, Regelprofil- und Content-Sektionen
  - rechte Sticky-Sidebar mit Schnellübersicht und Speichern-Button
  - keine alte `hh-page-header`/`hh-card`-Shell mehr
  - vorhandene Feldnamen, Controller-Logik, Routen und Validierung bleiben unverändert

## Nicht geändert

- Keine Route geändert.
- Kein Controller geändert.
- Kein Model geändert.
- Keine Migration.
- Keine Cup-Scoring-Logik.
- Keine Android-Änderung.

## Prüfung

Die Blade-Dateien wurden über den Laravel BladeCompiler kompiliert und die erzeugten PHP-Dateien mit `php -l` geprüft.

`php artisan view:cache` konnte im Container nicht ausgeführt werden, weil die lokale PHP-Umgebung weiterhin `DOMDocument` nicht geladen hat.
