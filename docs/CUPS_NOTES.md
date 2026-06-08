# Hunthub Cups / Turniere — Basis 15

Dieses Modul ist die erste technische Grundlage für Hunthub-Cups.

## Enthalten

- Cup-Übersicht unter `/cups`
- Cup erstellen, bearbeiten und archivieren
- Cup-Detailseite mit Regeln, Daten und Leaderboard
- Cup-Team anmelden
- Invite-Link pro Cup-Team
- Beitritt per Invite-Link
- Ergebnis-Einreichung mit Screenshot
- 20-Minuten-Cooldown pro Cup-Team zwischen Einreichungen
- manuelle Prüfung durch Cup-Ersteller
- Annahme/Ablehnung von Ergebnissen
- Leaderboard auf Basis angenommener Einreichungen
- XP-Hooks und erste Cup-Badges/Quest
- API-Vorbereitung über `/api/v1/cups`

## Scoring in dieser Basis

Nur erfolgreiche Extraktionen zählen.

- Punkte = Hunter-Kills + maximal 1 Bonuspunkt für Bounty-Token
- Ohne Extraktion: 0 Punkte
- Ohne Kill: kein Bounty-Bonus
- Maximal 4 Bounty-Token können angegeben werden

Das entspricht der aktuellen gewünschten Richtung: Bounty soll Kämpfe ergänzen, aber keinen reinen Bounty-Rush ohne Fights belohnen.

## Noch nicht enthalten

- OCR / automatische Screenshot-Auswertung
- Manipulationsprüfung
- Admin-Panel für globale Cup-Verwaltung
- Shortdomain-Links
- detaillierte Match-Historie pro Spieler
- bracket/turnierbaum
- automatische Gewinner-Auswertung
- Push-Benachrichtigungen

Diese Punkte bleiben spätere Ausbaustufen.
