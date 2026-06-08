# Patch 102 — Cup Bounty-first Scoring

## Zweck

Dieser Patch stellt die Cup-Wertung vom bisherigen Kill-first-System auf eine Bounty-first-Wertung um.

Neue Standardwertung:

- Nur erfolgreiche Extraktionen können Punkte bekommen.
- Nur Runs mit mindestens 1 extrahiertem Bounty-/Trophy-Token zählen.
- Bounty-/Trophy-Token sind das primäre Ziel: 2 Punkte pro Token.
- Hunter-Kills sind Zusatzpunkte: 1 Punkt pro Kill.
- Kills ohne Bounty-Extract zählen 0 Punkte.

## Neue .env-Werte optional

Diese Werte sind optional. Ohne Angabe gelten die Defaults:

```env
HH_CUP_POINTS_PER_BOUNTY_TOKEN=2
HH_CUP_POINTS_PER_KILL=1
HH_CUP_REQUIRE_EXTRACT_FOR_SCORE=true
HH_CUP_REQUIRE_BOUNTY_FOR_SCORE=true
```

## Geänderte Dateien

- `config/hunthub.php`
- `app/Models/CupSubmission.php`
- `app/Models/Cup.php`
- `app/Http/Controllers/Cups/CupController.php`
- `app/Services/Cups/CupSubmissionAnalysisService.php`
- `resources/views/cups/show.blade.php`
- `resources/views/cups/hall-of-fame.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Technische Änderungen

- `CupSubmission::calculatePoints()` nutzt jetzt konfigurierbare Punktewerte.
- KI-Prompt wurde auf Bounty-first-Scoring umgeschrieben.
- KI prüft Bounty-/Trophy-Token als scoring-kritisches Feld.
- Zweite Fokusprüfung für das Bounty-/Trophy-Token-Feld wurde ergänzt.
- Unsichere Bounty-Erkennung führt zur manuellen Prüfung statt zur automatischen Wertung.
- Leaderboard-Tiebreaker priorisiert bei gleicher Punktzahl Bounty-Token vor Kills.
- Tabellen zeigen Token vor Kills, damit die neue Wertungslogik sichtbarer ist.
- DE/EN-Texte wurden auf die neue Wertung angepasst.

## Wichtig

Bestehende bereits gewertete Test-Einreichungen werden nicht automatisch rückwirkend neu berechnet. Für Live-Tests am besten alte Testeinreichungen löschen/zurücksetzen oder neue Screenshots einreichen. Alternativ können review-pflichtige Einreichungen erneut manuell freigegeben werden, damit die neue Formel greift.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 102_hnt_cup_bounty_first_scoring.zip -d .

php artisan config:clear
php artisan view:clear
php artisan optimize:clear

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Testfälle

- Kein Extract: 0 Punkte.
- Extract + 0 Bounty + Kills: 0 Punkte.
- Extract + 1 Bounty + 0 Kills: 2 Punkte.
- Extract + 1 Bounty + 3 Kills: 5 Punkte.
- Extract + 2 Bounty + 0 Kills: 4 Punkte.
- Extract + 2 Bounty + 4 Kills: 8 Punkte.

## Hinweis zu Screenshots

Ein echter Summary-Screenshot mit Bounty-Token ist sehr hilfreich, um die Fokus-Crops bei Bedarf nach dem Test feinzujustieren. Dieser Patch enthält bereits eine robuste erste Umsetzung, aber die echte Hunt-UI kann je nach Auflösung/Sprache leicht abweichen.
