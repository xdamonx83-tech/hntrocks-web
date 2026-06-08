# Patch 237 – Web Cup Manual Score & Rescore Tool

## Ziel
Admins sollen Cup-Einreichungen ohne phpMyAdmin korrigieren können.

## Geändert
- `routes/web.php`
- `app/Http/Controllers/Cups/CupSubmissionController.php`
- `app/Services/Cups/CupSubmissionAnalysisService.php`
- `resources/views/cups/show.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Neu
- Admin-Formular pro Einreichung für manuelle Punktekorrektur:
  - Kills
  - Bounty-Token
  - Gesamtpunkte
  - optionale Admin-Notiz
- Rescore-Button pro Einreichung, der den gespeicherten Screenshot erneut per KI auswertet.
- Rescore ignoriert die aktuelle Submission bei Duplicate-Checks, damit sie sich nicht selbst als Duplikat blockiert.
- Team-/Leaderboard-Summen werden nach manueller Korrektur und Rescore neu berechnet.

## Hinweise
- Manuelle Korrektur setzt die Einreichung auf `approved_manual`.
- Leeres Punktefeld berechnet automatisch nach aktueller Cup-Formel.
- Eingetragene Gesamtpunkte überschreiben die automatische Formel bewusst.
- Rescore benötigt weiterhin funktionierenden OpenAI-Zugriff.
