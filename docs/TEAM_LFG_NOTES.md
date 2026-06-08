# Team-LFG Basis

ZIP 09 ergänzt das Team-Recruiting als eigenes Laravel-Modul auf Basis der vorhandenen Team-Struktur.

## Enthalten

- Team-LFG Übersicht unter `/team-lfg`
- Team-LFG erstellen, anzeigen, bearbeiten, archivieren
- Zwei Gesuchtypen:
  - `team_seeks_players`: Team sucht Spieler
  - `player_seeks_team`: Spieler sucht Team
- Bewerbungen auf Team-Gesuche
- Team-Einladungen auf Spieler-Gesuche
- Annahme einer Bewerbung fügt den Spieler dem Team hinzu
- Annahme einer Team-Einladung fügt den suchenden Spieler dem einladenden Team hinzu
- Rollenprüfung über bestehende Team-Mitgliedschaften: Owner/Officer dürfen Team-Gesuche verwalten
- Filter nach Typ, Plattform, Spielstil, Region, Status und Voice
- Seeder-Beispiele, wenn die Tabelle vorhanden ist

## Neue Tabellen

- `team_lfg_posts`
- `team_lfg_applications`

## Technische Regel

Team-LFG bleibt getrennt vom globalen LFG. Globales LFG ist für einzelne Runden, Team-LFG ist für Recruiting und Team-Beitritte.
