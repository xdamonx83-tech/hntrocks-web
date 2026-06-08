# Patch 202 - Web/API Profile Section Endpoints

Ziel: Vollere Profil-Unterseiten für die Android-App bereitstellen, ohne die bestehende Web-Optik oder bestehende API-Endpunkte zu ändern.

Neue API-Endpunkte:
- GET /api/v1/me/profile-sections/{section}
- GET /api/v1/users/{username}/profile-sections/{section}

Unterstützte Sections:
- badges
- friends
- quests
- posts
- teams
- lfg

Geändert:
- routes/api.php
- app/Http/Controllers/Api/V1/ApiMembersController.php

Wichtig:
- Keine Migration.
- Keine neuen Models/Controller.
- Keine Änderung an bestehenden /api/v1/me oder /api/v1/users/{username} Antworten.
- Sichtbarkeitslogik privater Profile bleibt erhalten.
