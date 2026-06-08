# Patch 178 Laravel: Team-Beitrittsanfragen verwalten API v1

## Inhalt

Neue mobile API-Routen:

- `POST /api/v1/teams/{team:slug}/members/{member}/accept`
- `POST /api/v1/teams/{team:slug}/members/{member}/reject`

## Verhalten

- Nur aktive Owner/Officer dürfen offene Team-Beitrittsanfragen verwalten.
- Es werden nur Pending-Mitgliedschaften verarbeitet.
- Beim Annehmen wird der Nutzer aktives Teammitglied mit Rolle `member`.
- Beim Ablehnen wird der Status auf `declined` gesetzt.
- Bestehende Notifications/Gamification-Logik wird weiterverwendet.
- Team-Detailantwort liefert für Manager zusätzlich `pending_members`.

## Geändert

- `routes/api.php`
- `app/Http/Controllers/Api/V1/ApiTeamsController.php`

Keine Migration. Kein Composer install.
