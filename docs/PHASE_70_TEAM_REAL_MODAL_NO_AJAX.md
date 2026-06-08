# Phase 70 – Team Create/Edit als echtes Modal ohne AJAX-Speichern

## Ziel
Team erstellen und Team bearbeiten sollen von `/teams/manage` aus als echte Overlay-Modals geöffnet werden, ohne die bestehende Laravel-Speicherlogik riskant auf AJAX umzubauen.

## Geändert
- `resources/views/teams/manage.blade.php`
- `resources/views/teams/partials/account-team-card.blade.php`
- `resources/views/teams/partials/team-form.blade.php`
- `public/assets/vikinger/js/hunthub-start.js`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

## Verhalten
- Klick auf „Team erstellen“ in `/teams/manage` öffnet ein echtes Modal per JavaScript.
- Klick auf „Verwalten“ bei einem Team öffnet das passende Edit-Modal per JavaScript.
- Speichern bleibt ein normales Laravel-Formular-Submit.
- Bei Erfolg leitet Laravel wie bisher weiter.
- Bei Validierungsfehlern kehrt Laravel nach `/teams/manage` zurück und öffnet das betroffene Modal automatisch wieder.
- Ohne JavaScript bleiben `/teams/create` und `/teams/{team}/edit` weiterhin als Fallback-Seiten erreichbar.

## Kein AJAX-Speichern
Bewusst kein AJAX-Speichern in dieser Phase. Dadurch bleiben Controller, Routes, Validation und Redirect-Verhalten stabil.

## Keine Datenbankänderung
Keine Migration nötig.
