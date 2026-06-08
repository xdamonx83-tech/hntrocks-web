# Patch 63 – Cup multilingual content + solo username lock

## Inhalt

Dieser Patch erweitert Cup-Inhalte ohne Datenbankmigration über das bestehende `cups.settings` JSON-Feld.

Neue Struktur:

```json
{
  "content": {
    "locales": {
      "de": {
        "summary": "...",
        "description": "...",
        "rules": "...",
        "scoring_rules": "...",
        "prizes": {
          "first": "...",
          "second": "...",
          "third": "..."
        },
        "prize_note": "...",
        "cashout_note": "...",
        "hall_of_fame_note": "..."
      },
      "en": {
        "summary": "...",
        "description": "...",
        "rules": "..."
      }
    }
  }
}
```

Die Cup-Detailansicht, Cup-Karten, Regeln, Scoring, Preise und Hinweise lesen die Inhalte passend zu `app()->getLocale()`.

Fallback-Reihenfolge:

1. gewählte Sprache (`de` oder `en`)
2. Deutsch
3. alte/flache Content-Werte aus früheren Patches
4. bestehende `summary` / `rules` Spalten
5. Default-Texte aus `ui.php`, falls vorhanden

Dadurch bleiben bestehende Cups ohne Migration nutzbar.

## Admin-Formular

Das Cup-Formular zeigt die Content-Felder jetzt zweispaltig für Deutsch und Englisch an:

- Kurzbeschreibung
- Beschreibung
- Regeln
- Scoring-Regeln
- Platz 1–3 Preise
- Preishinweis
- Barauszahlungs-Hinweis
- Hall-of-Fame-Hinweis

Deutsch wird beim Bearbeiten mit alten vorhandenen Inhalten vorbelegt. Englisch bleibt leer, falls noch keine englische Fassung gepflegt wurde.

## Solo-Cups

Bei Solo-Leaderboards kann kein eigener Teilnehmer-/Anzeigename mehr eingetragen werden.

Die Teilnahme nutzt automatisch den Profil-Username des eingeloggten Users. Falls kein Username vorhanden ist, wird auf Name bzw. eine User-ID-Fallback-Bezeichnung zurückgegriffen.

Die öffentliche Anzeige in Solo-Leaderboards nutzt ebenfalls bevorzugt den Profil-Username.

## Keine Migration

Es wird keine Migration benötigt.
