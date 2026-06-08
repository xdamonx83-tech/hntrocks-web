# Mitglieder / Spielerfinden

Stand: ZIP 05

## Enthalten

- `/members` nutzt jetzt einen echten Controller statt einer Platzhalter-View.
- Sichtbar sind Profile mit `profile_visibility = public` oder `registered` sowie immer das eigene Profil.
- Filter:
  - Suche nach Name, Username, Bio, Headline oder Discord
  - Plattform
  - Spielstil
  - Region
  - Sprache
  - nur LFG-offene Spieler
- Karten verlinken auf `/u/{username}`.
- Pagination ist bewusst einfach und ohne externe Komponenten umgesetzt.

## Keine neuen Tabellen

Dieses Modul verwendet die vorhandenen Tabellen `users` und `user_profiles` aus ZIP 04.

## Optionaler Testinhalt

`php artisan db:seed` legt neben dem Admin weitere Beispielspieler an bzw. aktualisiert deren Profile.
