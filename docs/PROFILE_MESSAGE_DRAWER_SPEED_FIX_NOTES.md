# Patch 60 – Profile Message Drawer Speed Fix

## Zweck

Patch 59 öffnete den Desktop-Chat-Drawer erst nach Abschluss des AJAX-Requests. Wenn die Conversation-Erstellung oder das Laden langsam war, wirkte der Klick wie eingefroren.

## Änderungen

- Der rechte Chat-Drawer öffnet auf Desktop jetzt sofort mit einem Ladezustand.
- Der echte Chat ersetzt den Ladezustand, sobald die Antwort da ist.
- Bei Fehlern bleibt ein sichtbarer Fallback-Link zur normalen Messages-Seite stehen.
- Die Suche nach einer bestehenden privaten Conversation wurde serverseitig optimiert: statt alle passenden Conversations zu laden und anschließend in PHP zu filtern, wird die bestehende private Unterhaltung direkt über `conversation_participants` gesucht.

## Geänderte Dateien

- `app/Http/Controllers/Messages/MessageController.php`
- `public/assets/vikinger/js/hunthub-start.js`

## Migration

Keine Migration nötig.
