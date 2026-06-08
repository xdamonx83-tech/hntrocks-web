# Patch 179 - Laravel LFG-Anfragen verwalten API v1

## Inhalt

Neue mobile API-Routen:

- `POST /api/v1/lfg/{post}/applications/{application}/accept`
- `POST /api/v1/lfg/{post}/applications/{application}/reject`

## Verhalten

- Nur der Ersteller eines LFG-Beitrags darf Anfragen verwalten.
- Es werden nur `pending`-Anfragen verarbeitet.
- Beim Annehmen wird die Anfrage auf `accepted` gesetzt.
- Beim Annehmen wird `slots_filled` erhöht.
- Wenn keine Slots mehr frei sind, wird der LFG-Beitrag auf `full` gesetzt.
- Beim Ablehnen wird die Anfrage auf `rejected` gesetzt.
- Bestehende Benachrichtigungs-/Gamification-Logik wird genutzt.
- Beim Annehmen wird wie in der Web-App eine LFG-Konversation/Systemnachricht erzeugt.

## Resource-Erweiterung

`LfgPostResource` liefert für den Besitzer jetzt:

- `pending_applications`
- `viewer.can_manage`

Die LFG-Liste lädt außerdem eigene private LFG-Beiträge mit, damit der Nutzer sie in der App verwalten kann.

Keine Migration.
Kein Composer install.
