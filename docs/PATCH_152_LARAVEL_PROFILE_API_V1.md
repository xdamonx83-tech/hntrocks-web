# Patch 152 - Laravel Profile API v1

Basis: aktueller Laravel-Upload 21.zip.

## Neue Route

POST /api/v1/me/profile

Auth: Bearer Token über bestehendes api.token Middleware-Setup.

## Speichert

- name
- headline
- bio
- platform
- playstyle
- region
- language
- hunt_role
- discord_name
- is_lfg_available

## Bewusst nicht enthalten

- Avatar-Upload
- Cover-Upload
- Username ändern
- E-Mail ändern

## Antwort

Gibt dieselbe Grundstruktur wie GET /api/v1/me zurück:

- user
- counts
