# Patch 162 Laravel – Team Join API v1

Ergänzt die mobile API minimal um Team-Beitrittsanfragen.

## Neu

- `POST /api/v1/teams/{team:slug}/join`
- optionales Feld `message`, max. 500 Zeichen
- nutzt vorhandene Team-Mitgliedschaftsstruktur `team_members`
- erzeugt oder erneuert eine Pending-Mitgliedschaft
- vergibt vorhandenes Gamification-Event `team_join_requested`
- sendet vorhandene Team-Join-Notification an den Owner
- gibt danach die aktualisierte Team-Detailantwort zurück

## Bewusst nicht enthalten

- Teamverwaltung in der App
- Annahme/Ablehnung von Anfragen in der App
- Rollenänderungen
- Team erstellen/bearbeiten
- Migrationen
