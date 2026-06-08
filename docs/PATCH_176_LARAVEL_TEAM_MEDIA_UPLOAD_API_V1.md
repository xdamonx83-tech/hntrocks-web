# Patch 176 – Laravel Team Avatar/Cover Upload API v1

Basis: letzter Laravel-API-Stand aus Patch 167.

## Neue Routen

- POST /api/v1/teams/{team:slug}/avatar
- POST /api/v1/teams/{team:slug}/cover

## Felder

- avatar: jpg/jpeg/png/webp, Limit aus hunthub.upload_limits.team_avatar_kb
- cover: jpg/jpeg/png/webp, Limit aus hunthub.upload_limits.team_cover_kb

## Rechte

Nur aktive Teammitglieder mit Rolle owner oder officer dürfen Team-Bilder ändern.

## Verhalten

- nutzt bestehenden MediaService
- nutzt bestehende Medienmoderation
- nutzt bestehende Upload-Limits
- löscht den alten Datei-Pfad vom public-Disk
- speichert avatar_path bzw. cover_path am Team
- gibt danach Teamdaten mit members und viewer zurück

## Nicht enthalten

- Team bearbeiten.
- Bild-Crop.
- separate Medienverwaltung.
- Migrationen.
