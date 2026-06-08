# Patch 161 Laravel: Team Detail + Members API

Basis: aktueller Laravel-Upload `21.zip` plus vorherige API-Patches.

## Neue Route

`GET /api/v1/teams/{team:slug}`

## Response

- `data`: TeamResource
- `members`: aktive Teammitglieder mit Rolle, Status, Joined-At und UserResource
- `viewer`: Membership-Status des eingeloggten Users, `can_manage`, `can_join`

## Keine Änderungen

- Keine Migration
- Kein Composer-Paket
- Kein Team-Join
- Keine Rollenverwaltung
- Kein Team-Edit

## Dateien

- `routes/api.php`
- `app/Http/Controllers/Api/V1/ApiTeamsController.php`
