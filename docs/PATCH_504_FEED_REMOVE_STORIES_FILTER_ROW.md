# Patch 504 - Feed Stories entfernen und Filter einzeilig

## Ziel
- Die obere Stories-/Avatar-Leiste im Socialite-Feed entfernen.
- Die Feed-Filter dauerhaft in einer Zeile halten.

## Änderungen
- `resources/views/themes/socialite/feed/live.blade.php`
  - Stories-Slider vollständig entfernt.
  - Filterleiste von `flex-wrap` auf `flex-nowrap` geändert.
  - Horizontales Scrollen für kleinere Breiten erlaubt, damit die Filter in einer Reihe bleiben.
  - Keine Controller, Routen, Datenbank oder Assets geändert.

## Installation
```bash
php artisan optimize:clear
php artisan view:clear
```
