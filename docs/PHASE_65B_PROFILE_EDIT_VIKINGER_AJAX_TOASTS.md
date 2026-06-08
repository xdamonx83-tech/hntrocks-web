# Phase 65b – Profile Edit im Vikinger Account-Hub Stil

Basis:
- `3.zip` als aktuelle Laravel-Projektwahrheit
- `HTML Template.zip`, Referenzdatei `hub-profile-info.html`

Ziel:
- Profil bearbeiten optisch an Vikinger `hub-profile-info.html` angleichen
- bestehende Profil-Funktion erhalten
- AJAX-Speichern mit Toasts und Inline-Validierungsfehlern ergänzen

Geändert:
- `resources/views/profile/edit.blade.php`
- `app/Http/Controllers/Profile/ProfileController.php`
- `public/assets/vikinger/js/hunthub-start.js`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

Nicht geändert:
- Header
- Chat-Dock
- Sidebar global
- Gamification-Logik
- Datenbank/Migrationen

Deployment:
```bash
cd /home/users/hunthub/www/social.hunthub.online
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/social.hunthub.online
chmod -R 775 storage bootstrap/cache
```

Tests:
- `/profile/edit` öffnen
- Name/Headline ändern und speichern
- falsche URL bei Steam/Twitch/YouTube testen
- Avatar/Cover-Datei auswählen und Vorschau prüfen
- nach Speichern keinen Reload erwarten
- Toast und Inline-Fehler prüfen
