# Patch 516 – Cup-Ideenwand mit Voting v1

## Zweck

Öffentliche Cup-Ideenwand für kommende HNT.rocks Cups. Nutzer können Ideen einreichen und per Vote unterstützen. Admins können Ideen verwalten, hervorheben und mit einem internen Status versehen.

## Neue Seiten

- `/cup-ideas`
- `/admin/cup-ideas`

## Funktionen

### Öffentlich/Nutzer

- Ideen einreichen
- Kategorie auswählen
- Optional Bezug zu einem Cup setzen
- Ideenliste mit Filter nach Kategorie/Status
- Sortierung nach beliebt oder neu
- Pro Nutzer ein Vote pro Idee
- Vote kann durch erneuten Klick zurückgenommen werden
- Sichtbare Status: Neu, In Prüfung, Geplant, Kommt bald, Umgesetzt, Abgelehnt

### Admin

- Alle Ideen einsehen
- Nach Status/Kategorie filtern
- Status ändern
- Idee hervorheben
- interne Admin-Notiz speichern

## Migration

Neue Tabellen:

- `cup_ideas`
- `cup_idea_votes`

## Geänderte Dateien

- `database/migrations/2026_05_22_000516_create_cup_ideas_tables.php`
- `app/Models/CupIdea.php`
- `app/Models/CupIdeaVote.php`
- `app/Http/Controllers/CupIdeas/CupIdeaController.php`
- `app/Http/Controllers/Admin/AdminCupIdeaController.php`
- `routes/web.php`
- `resources/views/themes/socialite/cup-ideas/index.blade.php`
- `resources/views/themes/socialite/partials/sidebar.blade.php`
- `resources/views/themes/socialite/partials/head.blade.php`
- `resources/views/admin/cup-ideas/index.blade.php`
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

V1 enthält bewusst keine Kommentare unter Ideen. Das Voting bleibt dadurch übersichtlich. Kommentare oder Admin-Freigabe vor Veröffentlichung können später ergänzt werden.
