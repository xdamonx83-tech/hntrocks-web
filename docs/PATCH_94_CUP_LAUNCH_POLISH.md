# Patch 94 — Cup Launch Polish

## Zweck
Kleiner Launch-Patch für den Bayou Blood Cup. Der Patch macht Ranglisten-, Preis-, Fairplay- und Upload-Hinweise sichtbarer und vereinheitlicht den Submission-Cooldown über `config/hunthub.php`.

## Geänderte Dateien
- `config/hunthub.php`
- `app/Http/Controllers/Cups/CupController.php`
- `app/Http/Controllers/Cups/CupSubmissionController.php`
- `resources/views/cups/show.blade.php`
- `resources/views/cups/hall-of-fame.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Inhalt
- Cup-Submission-Cooldown wird zentral über `config('hunthub.cups.submission_cooldown_minutes')` gelesen.
- Default-Cooldown ohne ENV ist 30 Minuten.
- Bayou-Blood-Cup-Seite zeigt sichtbare Hinweise zu manueller Prüfung, Fairplay, vorläufigem Leaderboard, Preisprüfung und Upload-Regeln.
- Submit-Tab zeigt das aktuell konfigurierte Uploadlimit und den Cooldown an.
- Hall of Fame erklärt, dass nur bestätigte Ergebnisse dauerhaft angezeigt werden und Korrekturen möglich sind.

## Nicht enthalten
- Keine Migration.
- Kein Composer.
- Keine Mail-Änderung.
- Kein Admin-Rework.
- Kein neues Cup-Feature wie Teamwertung, MMR-Tiers oder VOD-Pflicht.
- Keine Änderung an `.env`.

## Empfohlene ENV-Werte vor Launch
Diese Werte nicht über den Patch setzen, sondern auf dem Server bewusst prüfen:

```env
HH_CUP_SUBMISSION_COOLDOWN_MINUTES=30
HH_UPLOAD_CUP_SUBMISSION_SCREENSHOT_MB=10
HH_UPLOAD_FEED_MEDIA_MB=50
HH_UPLOAD_FEED_MEDIA_COUNT=12
```
