# Patch 194 Laravel – Member Discovery API v1

## Ziel

Die bestehende mobile `/api/v1/members`-Route wird für die native Android-Mitgliedersuche nutzbar gemacht.

## Änderung

`GET /api/v1/members` liefert jetzt ein strukturiertes JSON mit:

- `data`: gefundene Mitglieder
- `meta`: Pagination-Informationen
- `filters`: aktive Filter

Jeder Eintrag enthält:

- `user`: UserResource mit Profilfeldern
- `viewer`: Beziehung des eingeloggten Nutzers zum gefundenen Profil

## Unterstützte Filter

- `q`
- `platform`
- `playstyle`

## Datenschutz/Privatsphäre

Private Profile werden in der Entdeckung nicht angezeigt, außer es handelt sich um das eigene Profil.
