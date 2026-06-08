# Patch 51: Account Deletion Processing

Dieser Patch macht aus der bisherigen Löschvormerkung einen technisch verarbeitbaren Löschlauf.

## User-Flow

- Nutzer können unter `/settings/security` eine Kontolöschung vormerken.
- Zur Bestätigung muss der eigene Benutzername exakt eingegeben werden.
- Das Passwort ist optional, damit Social-Login-Accounts ohne bekanntes lokales Passwort nicht blockiert werden.
- Die Vormerkung bleibt 14 Tage widerrufbar.
- Admin-Konten werden nicht automatisch gelöscht.

## CLI-Löschlauf

Vorschau:

```bash
php artisan hnt:process-account-deletions --dry-run
```

Echte Verarbeitung fälliger Vormerkungen:

```bash
php artisan hnt:process-account-deletions --force
```

Optional nur für eine User-ID, z. B. für Tests:

```bash
php artisan hnt:process-account-deletions --dry-run --user=123 --include-future
```

## Was verarbeitet wird

Der Löschlauf sammelt vor der Datenbanklöschung bekannte Datei-Referenzen und entfernt danach:

- Account/Login-Daten
- Sessions
- Passwort-Reset-Tokens
- Social-Login-Verknüpfungen
- Profil/Privacy/Notification Settings
- eigene Feed-/Kommentar-/LFG-/Team-LFG-/Moment-/Cup-Daten, soweit sie über bestehende Foreign Keys am User hängen
- Medien-Dateien aus `media_assets`, Profilbilder, Coverbilder, Team-/Cup-Bilder des Nutzers
- nicht-cascadierende Laravel-Notification-Kompatibilitätseinträge
- leere Conversations ohne Teilnehmer

Die tatsächlichen Datenbank-Löschungen laufen primär über die bestehenden Foreign-Key-Regeln des Projekts. Deshalb wurde keine Migration ergänzt.

## Cron-Beispiel

Nach Tests kann der Löschlauf täglich laufen:

```cron
15 3 * * * cd /home/users/hunthub/www/hnt.rocks && php artisan hnt:process-account-deletions --force >> storage/logs/account-deletions.log 2>&1
```
