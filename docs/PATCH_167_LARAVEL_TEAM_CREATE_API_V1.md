# Patch 167 — Laravel Team erstellen API v1

Basis: Patch 166 Laravel/API.

## Neue mobile API

```text
POST /api/v1/teams
```

## Felder

```text
name required, max 80
tagline optional, max 140
description optional, max 2500
platform optional, max 40
playstyle optional, max 60
region optional, max 60
language optional, max 40
visibility required: public/private
recruitment_status required: open/closed
```

## Verhalten

- erstellt ein aktives Team
- erzeugt einen eindeutigen Slug aus dem Teamnamen
- setzt den eingeloggten Nutzer als Owner
- erstellt den Owner als aktives Teammitglied
- feuert das vorhandene Gamification-Event `team_created`
- gibt Teamdetails inklusive Mitglieder und Viewer-Status zurück

## Zusätzlich

`GET /api/v1/teams` zeigt jetzt nicht nur öffentliche aktive Teams, sondern auch private Teams, bei denen der eingeloggte Nutzer Owner oder aktives Mitglied ist. Das entspricht eher der Web-Logik und verhindert, dass ein privat erstelltes Team nach Reload aus der nativen App verschwindet.

## Nicht enthalten

- Team-Avatar-Upload
- Team-Cover-Upload
- Team bearbeiten/löschen
- Anfrageverwaltung
