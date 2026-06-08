# Hunthub Gamification Basis (ZIP 12)

## Ziel

Dieses Modul legt die erste saubere Grundlage für Level, XP, Badges und Quests. Es ist bewusst noch kein endgültiges Balancing, sondern ein stabiler technischer Kern.

## Neue Bereiche

- `/gamification` zeigt Level, XP, Trust-Wert, Profilfortschritt, Badges, Quests und XP-Verlauf.
- Header zeigt das aktuelle Nutzerlevel.
- Seeder legt Standard-Badges und Start-Quests an.

## Neue Tabellen

- `xp_events`
- `badges`
- `badge_user`
- `quests`
- `quest_user`

Die Tabelle `users` bekommt zusätzlich:

- `xp_total`
- `level`
- `trust_score`
- `last_xp_at`

## Erste XP-Hooks

XP wird vergeben für:

- Account erstellen
- Profil pflegen / vollständig machen
- Feed-Beitrag erstellen
- Kommentar schreiben
- Kommentar erhalten
- Like geben
- Like erhalten
- LFG erstellen
- LFG-Bewerbung senden
- LFG-Bewerbung angenommen
- Team erstellen
- Team-Beitritt anfragen
- Team-Beitritt angenommen
- Team-LFG erstellen
- Team-LFG-Bewerbung / Einladung senden
- Team-LFG angenommen
- Medium hochladen
- Quest abschließen

## Balancing

Die aktuellen Werte sind MVP-Werte. Später sollten sie in eine Admin-Konfiguration oder Config-Datei ausgelagert werden.

## Wichtig

XP wird pro Quelle nur einmal vergeben, soweit ein konkretes Quellobjekt vorhanden ist. Beispiel: Ein Feed-Beitrag gibt nur einmal `feed_post_created` für genau diesen Beitrag.
