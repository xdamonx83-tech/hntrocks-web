# Teams-Basis

Dieser Schritt ersetzt den bisherigen Teams-Platzhalter durch ein echtes erstes Team-Modul.

## Enthalten

- Teams-Liste unter `/teams`
- Team erstellen unter `/teams/create`
- Team anzeigen unter `/teams/{slug}`
- Team bearbeiten unter `/teams/{slug}/edit`
- Team archivieren durch Owner
- Team-Avatar und Team-Cover
- Filter nach Plattform, Spielstil, Region und Recruiting-Status
- öffentliche/private Teams
- Recruiting offen/geschlossen
- Owner-/Officer-/Member-Rollen vorbereitet
- Beitrittsanfragen senden
- Beitrittsanfragen annehmen/ablehnen
- Team verlassen für normale Mitglieder

## Neue Tabellen

- `teams`
- `team_members`

## Bewusst noch nicht enthalten

- Team-Newsfeed
- Team-LFG
- Team-Einladungslinks
- detaillierte Rollenverwaltung
- Team-Mediengalerie
- Team-Benachrichtigungen

Diese Punkte kommen in separaten Phasen, damit die Basis stabil bleibt.
