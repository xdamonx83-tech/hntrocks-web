# Patch 166 — Laravel Team-LFG Apply/Invite API v1

## Inhalt

Ergänzt die mobile Team-LFG-Aktionsroute:

- `POST /api/v1/team-lfg/{post}/apply`

Die Route nutzt die vorhandenen Team-LFG-Modelle und Statuswerte.

## Unterstützte Fälle

### `team_seeks_players`

Ein Spieler kann sich auf ein Team-Gesuch bewerben.

- optionales Feld: `message`, max. 900 Zeichen
- blockiert geschlossene/volle Beiträge
- blockiert Team-Manager, bestehende Mitglieder und doppelte Anfragen
- erstellt `team_lfg_applications` mit `status=pending`
- vergibt das vorhandene Gamification-Event `team_lfg_application_sent`
- sendet eine vorhandene Benachrichtigung vom Typ `team_lfg_application`

### `player_seeks_team`

Ein Owner/Officer kann den Spieler aus einem verwaltbaren Team einladen.

- Pflichtfeld: `team_id`
- optionales Feld: `message`, max. 900 Zeichen
- blockiert eigene Beiträge
- blockiert Teams, die der Nutzer nicht verwalten darf
- blockiert Teams, in denen der Spieler bereits Mitglied ist
- blockiert doppelte Team-Einladungen
- erstellt `team_lfg_applications` mit `status=pending`

## API-Response

Die Route gibt zurück:

- `message`
- `application`
- `team_lfg`
- `data`

`TeamLfgPostResource` enthält zusätzlich ein `viewer`-Objekt:

- `is_owner`
- `can_apply`
- `can_invite`
- `application`

## Geänderte Dateien

- `routes/api.php`
- `app/Http/Controllers/Api/V1/ApiTeamLfgController.php`
- `app/Http/Resources/Api/TeamLfgPostResource.php`

## Server

Keine Migration.
Kein Composer install.

Nach Upload:

```bash
php artisan optimize:clear
php artisan route:clear
```
