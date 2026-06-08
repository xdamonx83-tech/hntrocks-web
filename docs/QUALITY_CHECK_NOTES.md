# Phase 21 – Qualitäts- und Konsistenzcheck

Dieser Schritt ist bewusst kein neues Funktionsmodul, sondern eine Stabilisierung der bisherigen Basis.

## Geändert

- Blade-Inline-Direktiven in Header, Sidebar, Mobile-Navigation und mehreren Modulansichten bereinigt.
- `routes/console.php` um zwei Prüfbefehle erweitert:
  - `php artisan hunthub:health`
  - `php artisan hunthub:audit-routes`
- Produktionsbeispiel `.env.production.example` ergänzt.
- Roadmap-Doku auf den Stand nach Phase 21 aktualisiert.

## Warum

Nach den ersten 20 Phasen existieren viele Module. Bevor Design oder Spezialfunktionen weiter ausgebaut werden, muss die Basis leichter prüfbar sein:

- Schreibrechte für `storage/` und `bootstrap/cache/`
- vorhandener `public/storage` Link
- Datenbankverbindung
- Kern-Tabellen
- wichtige Routen
- weniger riskante Blade-Inline-Abfragen

## Nach dem Upload ausführen

```bash
cd /home/users/hunthub/www/social.hunthub.online
composer dump-autoload
php artisan view:clear
php artisan optimize:clear
php artisan hunthub:audit-routes
php artisan hunthub:health
```

## Produktivbetrieb

Auf der Live-Subdomain sollte in `.env` mindestens gelten:

```env
APP_ENV=production
APP_DEBUG=false
LOG_LEVEL=warning
APP_URL=https://social.hunthub.online
```

Wenn `APP_DEBUG=true` bleibt, zeigt Laravel bei Fehlern ausführliche technische Details. Das ist für Entwicklung hilfreich, für Live-Betrieb aber nicht sinnvoll.

## Hinweis zu `public/storage`

Wenn der Health-Check `public/storage` bemängelt:

```bash
rm -rf public/storage
php artisan storage:link
```

## Hinweis zu Rechten

Wenn `storage/` oder `bootstrap/cache/` nicht beschreibbar sind:

```bash
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Der Benutzername `hunthub` muss natürlich zu deinem echten Servernutzer passen.
