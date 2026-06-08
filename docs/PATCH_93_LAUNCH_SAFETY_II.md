# Patch 93 — Launch Safety II

Basis: aktueller Upload `20.zip`.

Ziel: kleiner Launch-Safety-Patch ohne Feature-Ausbau, ohne Migration, ohne Mail-Änderung.

## Geänderte Bereiche

- Fehlender UI-Key `ui.login_title` in DE/EN ergänzt.
- Social-Login-Buttons auf Login/Register werden nur noch angezeigt, wenn der jeweilige Provider laut `config/social.php` aktiviert und bei OAuth-Providern vollständig mit Client-ID und Client-Secret konfiguriert ist.
- Rechtstexte minimal geschärft:
  - lokale Fonts/Icon-Fonts nach Patch 92 erwähnt,
  - Social Login präzisiert,
  - KI-Übersetzungen als nutzerinitiierte Funktion präzisiert,
  - Upload-/KI-Prüfung als automatisierte Vorprüfung mit manueller Prüfung eingeordnet,
  - Cup-/Hall-of-Fame-/Preis-Hinweise präzisiert,
  - vorläufige Top-Platzierungen bis zur manuellen Prüfung erwähnt.

## Nicht geändert

- Keine Mail-Konfiguration.
- Keine `.env`-Änderung.
- Keine Migration.
- Keine Controller-/Model-/Service-Änderung.
- Kein Header-/Feed-/Cup-System-Rework.
- Keine Composer-Abhängigkeiten.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 93_hnt_launch_safety_ii.zip -d .
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Prüfung danach

- Login-Seite öffnen: Seitentitel darf nicht mehr `ui.login_title` zeigen.
- Login/Register prüfen: Es sollen nur aktiv konfigurierte Social-Provider sichtbar sein.
- Impressum/Datenschutz/Nutzungsbedingungen/Netiquette öffnen.
- Cup-Seite und Cup-Detailseite stichprobenartig prüfen.
