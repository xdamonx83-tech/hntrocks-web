# Patch 530 — Loadout-Challenge Starter-Content

Dieser Patch ergänzt einen Laravel-Seeder mit 24 fertig formulierten Loadout-Challenges für HNT.rocks.

## Datei

- `database/seeders/HntLoadoutChallengeSeeder.php`

## Ausführung

```bash
cd /home/users/hunthub/www/hnt.rocks
composer dump-autoload
php artisan db:seed --class="Database\\Seeders\\HntLoadoutChallengeSeeder"
php artisan optimize:clear
php artisan view:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Inhalt

Der Seeder nutzt `updateOrCreate` anhand des Slugs und kann erneut ausgeführt werden. Bestehende Challenge-Slugs werden aktualisiert, neue werden angelegt.

Enthalten sind unter anderem:

- Romero-Ritus
- Free-Hunter-Schwur
- Kein Long Ammo
- Pistolero
- Bow im Bayou
- Springfield-Predigt
- Ein Schuss, ein Gebet
- Shotgun-Beichte
- Messer zwischen den Zähnen
- Leiser Vertrag
- Budget-Blutpakt
- Kein Feuer aus der Tasche
- Vetterli-Waldläufer
- Nagant-Nachtwache
- Bossjäger ohne Komfort
- Solo gegen den Sumpf
- Kein Scope, kein Problem
- Kontraband-Kodex
- Bounty oder nichts
- Clutch oder Untergang
- Rivalenvertrag
- Kein Luxus im Bayou
- Extraktion bei Nacht
- Moment-Macher

## Hinweise

- Der Seeder prüft, ob die Tabelle `loadout_challenges` existiert.
- Keine Migrationen enthalten.
- Keine Routen, Controller, Views, CSS oder JS geändert.
- Alle Challenges werden initial als `active` angelegt.
- Start-/Enddaten bleiben leer und können im Adminbereich manuell gesetzt werden.
