# Patch 58: Auth Legal Footer Fix

## Ziel

Die Legal-Links auf Login/Register/Forgot/Reset waren zwar im Auth-Layout eingebunden, lagen wegen des Vikinger-Auth-Layouts aber optisch oben statt unten bzw. waren im sichtbaren Bereich nicht brauchbar.

## Änderung

Geändert wurde nur:

- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Wirkung

- Auf Desktop wird `partials.auth-legal-footer` im Auth-Layout unten am Viewport fixiert.
- Die Links bleiben sichtbar und überlagern nicht den oberen Login-Bereich.
- Auf kleineren Viewports, auf denen das Vikinger-Landing ohnehin statisch wird, läuft der Footer wieder normal unter dem Inhalt.
- Keine Routen, Controller, Blade-Struktur, Datenbank oder Auth-Logik wurden geändert.

## Test

Ausgeloggt prüfen:

- `/login`
- `/register`
- `/forgot-password`
- `/reset-password/...` falls ein Token vorhanden ist

Erwartung:

- Impressum / Datenschutz / Nutzungsbedingungen / Netiquette / Cookie-Einstellungen sind unten sichtbar.
- Auf Desktop nicht oben über dem Hero.
- Auf Mobile unter dem Formular sichtbar.
