# Patch 505 – Feed Top Spacing after Stories Removal

## Ziel
Nach dem Entfernen der Stories-Leiste saß der Feed-Composer zu nah am Header. Dieser Patch erhöht nur den oberen Abstand des Feed-Inhalts.

## Änderungen
- Feed-Hauptcontainer erhält `mt-5 md:mt-7`.
- Stories bleiben entfernt.
- Filter bleiben einzeilig.
- Keine Routen, Controller, Models, Datenbank oder Assets geändert.

## Dateien
- `resources/views/themes/socialite/feed/live.blade.php`

## Installation
```bash
php artisan optimize:clear
php artisan view:clear
```
