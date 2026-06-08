# Patch 163 Laravel - LFG Create API v1

Mobile API-Erweiterung für Spieler-LFG-Erstellung.

## Neue Route
- `POST /api/v1/lfg`

## Felder
- `title` required, max 120
- `body` nullable, max 2800
- `platform` nullable
- `playstyle` nullable
- `region` nullable
- `language` nullable
- `preferred_time` nullable
- `experience_level` nullable
- `voice_required` boolean
- `slots_total` required, 2 bis 4
- `visibility` required: `public` oder `private`
- `expires_at` optional, Datum in Zukunft

## Verhalten
- Erstellt LFG mit `status=open` und `slots_filled=1`.
- Nutzt vorhandenes Gamification-Event `lfg_post_created`.
- Synchronisiert Mentions über vorhandenen MentionService.
- Gibt `lfg` und `data` mit `LfgPostResource` zurück.

## Wichtig
Diese `routes/api.php` ist kumulativ für die mobilen Profil-, Team- und LFG-Routen aus den Patches 152-163, damit keine vorherigen API-Routen zurückfallen.
