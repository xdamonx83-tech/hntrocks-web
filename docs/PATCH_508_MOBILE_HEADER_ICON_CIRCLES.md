# Patch 508 – Mobile Header Icon Circles

- Fixiert die mobilen Header-Aktionsbuttons auf echte 52x52px-Kreise.
- Betrifft Plus, Notifications und Messages im Socialite-Header.
- Profilavatar bleibt unverändert.
- CSS-Version auf v=508 erhöht.
- Keine Routen, Controller, DB oder Android-Dateien geändert.

Installation:

```bash
php artisan optimize:clear
php artisan view:clear
```

Danach Browser hart neu laden.
