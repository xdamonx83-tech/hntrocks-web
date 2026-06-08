# Patch 238 – Cup Leaderboard Mobile wiederherstellen

## Ziel
Patch 237 hat die mobile Leaderboard-Ausgabe auf der Cup-Seite unbeabsichtigt wieder entfernt. Patch 238 stellt den mobilen Leaderboard-Block und die dazugehörige CSS-Klasse wieder her, ohne die neuen Admin-Funktionen aus Patch 237 zu entfernen.

## Geänderte Dateien
- `resources/views/cups/show.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Umsetzung
- `hh-cup-leaderboard-table` an der Desktop-Tabelle wieder ergänzt
- Mobile Card-Ansicht `hh-cup-mobile-leaderboard` wieder eingefügt
- Mobile Tab-JS aus dem früheren Mobile-Fix wiederhergestellt
- CSS für mobile Leaderboard-Karten mitgeliefert

## Nicht geändert
- manuelles Nachtragen aus Patch 237
- Rescore-Funktion aus Patch 237
- Controller
- Routes
- Models
- Datenbank
