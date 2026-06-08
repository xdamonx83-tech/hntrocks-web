# Patch 514 – Admin Content Target Links

## Ziel
In der Admin-Inhaltsmoderation sollen nicht nur Medien eine Vorschau bzw. einen Ziel-Link haben. Auch Feed-Beiträge, Moments, Teams, LFG, Team-LFG, Cups und Cup-Einreichungen sollen direkt aus der jeweiligen Zeile heraus im Frontend geöffnet werden können.

## Geändert
- `/admin/content` erhält pro moderierbarem Inhalt einen direkten Button zum verknüpften Inhalt.
- Ziel-Links ergänzt für:
  - Feed-Beiträge → `feed.show`
  - Moments → `moments.show`
  - Teams → `teams.show`
  - LFG → `lfg.show`
  - Team-LFG → `team-lfg.show`
  - Cups → `cups.show`
  - Cup-Einreichungen → Cup-Unterseite `submissions`
- Medien behalten weiterhin ihre vorhandene Datei-/Ziel-Vorschau aus Patch 513.
- Moderationszeilen wurden optisch leicht aufgeräumt: links Inhalt/Meta, rechts Öffnen-Button und Statusformular.
- CSS-Version auf `v=514` erhöht.

## Dateien
- `resources/views/admin/content/index.blade.php`
- `resources/views/admin/content/partials/moderation-card.blade.php`
- `resources/views/admin/layouts/app.blade.php`
- `public/assets/admin/admin.css`

## Installation
```bash
php artisan optimize:clear
php artisan view:clear
```

Keine Migration. Keine Routenänderung. Keine Controlleränderung. Keine Frontend/Socialite-Änderung. Keine Android-Dateien.
