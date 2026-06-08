# Patch 203 Web/API — LFG detail endpoints for app notifications

Basis: 22.zip plus accepted patch 202 web API profile sections.

## Ziel
Die Android-App kann LFG- und Team-LFG-Beiträge per ID gezielt laden, damit Notification-Deep-Links nicht nur zur Liste führen.

## Neue Endpunkte
- GET /api/v1/lfg/{post}
- GET /api/v1/team-lfg/{post}

## Schutzlogik
- LFG: sichtbar für öffentliche Beiträge oder Besitzer.
- Team-LFG: sichtbar für öffentliche Beiträge, Besitzer oder verwaltende Team-Mitglieder.

## Nicht geändert
Keine Migrationen, keine neuen Controller, keine Models, keine Tabellen, keine UI-Änderungen.
