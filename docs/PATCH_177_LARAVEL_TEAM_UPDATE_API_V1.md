# Patch 177 – Laravel Team Update API v1

## Inhalt

Ergänzt die mobile Team-Bearbeiten-API.

Neue Route:

```text
POST /api/v1/teams/{team:slug}
```

Nur aktive Owner/Officer dürfen ändern.

Gespeicherte Felder:

- name
- tagline
- description
- platform
- playstyle
- region
- language
- visibility
- recruitment_status

Bewusst nicht enthalten:

- Avatar Upload
- Cover Upload
- Löschen/Archivieren
- Rollen-/Mitgliederverwaltung

Diese Bereiche bleiben getrennte, kleinere API-Schritte.
