# Patch 192 Laravel — Friend Actions API v1

Basis: Patch 191 Laravel.

## Neue API-Routen
- POST /api/v1/users/{user:username}/friend
- POST /api/v1/friends/{friendship}/accept
- POST /api/v1/friends/{friendship}/decline
- POST /api/v1/friends/{friendship}/remove

## Verhalten
- Nutzt bestehendes Friendship-Model und bestehende Web-Regeln.
- Gibt nach jeder Aktion wieder Public-Profile-Daten zurück.
- Sendet Benachrichtigung bei neuer Anfrage und angenommener Anfrage.
