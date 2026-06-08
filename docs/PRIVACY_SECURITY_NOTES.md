# Datenschutz- und Sicherheitsgrundlage

Stand: ZIP 18

## Enthalten

- Datenschutz-Einstellungen unter `/settings/privacy`
- Blockierte Nutzer unter `/settings/privacy/blocks`
- Sicherheitsbereich unter `/settings/security`
- Passwortänderung mit aktuellem Passwort
- Sicherheitsereignisse für Login, Logout, Passwortänderung, Export und Löschvormerkung
- JSON-Datenexport
- 14-Tage-Löschvormerkung mit Widerruf
- Tabellen für Datenschutz, Blockierungen, Sicherheitslog und Löschvormerkungen

## Neue Tabellen

- `user_privacy_settings`
- `user_blocks`
- `user_security_events`
- `account_deletion_requests`

## Erweiterte Tabelle

- `users.last_login_at`
- `users.last_login_ip`

## Bewusste Grenzen dieser Basis

Die Löschvormerkung anonymisiert/löscht noch nicht automatisch. Das kommt später als eigener sicherer CLI/Queue-Job, damit keine produktiven Daten versehentlich entfernt werden.

Blockierungen sind als Datenmodell und UI vorhanden. Die Durchsetzung in allen Interaktionsmodulen wird später pro Modul ergänzt, damit keine vorhandenen Flows unkontrolliert kaputtgehen.
