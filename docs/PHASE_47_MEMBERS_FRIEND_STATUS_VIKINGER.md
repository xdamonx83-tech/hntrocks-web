# Phase 47 – Members Friend Status + Vikinger Cards

## Ziel
Die Mitgliederseite wurde als nächster Social-Fundament-Schritt nach dem Friend-System angepasst.

## Änderungen
- `resources/views/members/index.blade.php` näher an Vikinger `members.html` gebracht:
  - Section Banner
  - Filterbar
  - Vikinger `user-preview` Cards
  - Cover, Hexagon-Avatar, Level-Badge
  - Badge-Zeile
  - Stats
  - Social Links
  - Aktionen
- Mitgliederseite zeigt jetzt Friend-Status und passende Aktionen:
  - Add Friend +
  - Anfrage gesendet / zurückziehen
  - Annehmen / Ablehnen
  - Freunde ✓ / entfernen
  - Nachricht
- Controller lädt pro Seite:
  - Profil
  - Badges
  - Post-/Team-/Badge-Counts
  - Friendships zum aktuellen Viewer
  - Freundezähler
- Filter ergänzt:
  - Alle Spieler
  - Freunde
  - Anfragen

## Deployment
Keine neue Migration, keine neue Klasse, keine Composer-Abhängigkeit.

Empfohlen:

```bash
cd /home/users/hunthub/www/social.hunthub.online

php artisan view:clear
php artisan optimize:clear

chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

## Testpfade
- `/members`
- `/members?relationship=friends`
- `/members?relationship=pending`
- Friend-Button auf einem fremden Member testen
- Pending-Anfrage mit anderem Account prüfen
