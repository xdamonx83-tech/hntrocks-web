# Patch 193 Laravel: Friends List API v1

Basis: Patch 192 Laravel.

## Neue Route
GET /api/v1/friends

## Antwort
- friends
- incoming_requests
- outgoing_requests
- counts

Die Antwort nutzt bestehendes Friendship-Model und vorhandene User-/Profile-Daten.
Keine Migration. Kein Composer install.
