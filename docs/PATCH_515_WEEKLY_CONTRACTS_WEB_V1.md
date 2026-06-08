# Patch 515: Weekly Contracts / HNT-Aufträge Web v1

## Ziel
Erstes Web-Modul für wöchentliche HNT-Aufträge. Die App wird bewusst noch nicht angepasst.

## Umsetzung
- Neue Nutzerseite `/contracts`
- Neue Adminseite `/admin/contracts`
- HNT-Aufträge basieren auf der vorhandenen Quest-/XP-Logik
- Fortschritt zählt automatisch über vorhandene Gamification-Actions
- Neue Quest-Spalten für Wochenauftrag-Status und Laufzeit
- Sidebar-Link im Socialite-Frontend
- Admin-Sidebar-Link in der neuen Admin-Shell

## Geänderte Dateien
- `database/migrations/2026_05_22_000515_extend_quests_for_weekly_contracts.php`
- `app/Models/Quest.php`
- `app/Http/Controllers/Contracts/WeeklyContractController.php`
- `app/Http/Controllers/Admin/AdminWeeklyContractController.php`
- `routes/web.php`
- `resources/views/themes/socialite/contracts/index.blade.php`
- `resources/views/themes/socialite/partials/sidebar.blade.php`
- `resources/views/themes/socialite/partials/head.blade.php`
- `resources/views/admin/contracts/index.blade.php`
- `resources/views/admin/partials/nav.blade.php`
- `resources/views/admin/layouts/app.blade.php`
- `public/assets/socialite/css/hnt-app-palette.css`
- `public/assets/admin/admin.css`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Installation
```bash
composer dump-autoload
php artisan route:clear
php artisan migrate --force
php artisan optimize:clear
php artisan view:clear
```

## Hinweis
Neue Woche = in v1 neuen Auftrag im Adminbereich erstellen oder vorhandenen Auftrag duplizieren/neu anlegen. Automatische Wochenrotation und Android-App-Integration sind spätere Ausbauschritte.
