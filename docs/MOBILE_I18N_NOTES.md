# Hunthub Phase 20: Mobile & i18n Grundlage

## Ziel

Diese Phase baut keine fertige Vikinger-1:1-Optik, sondern stabilisiert die technische Oberfläche:

- mobile Navigation
- mobile Offcanvas-Sidebar
- mobile Bottom-Navigation
- Sprache DE/EN per Session
- erste Layout-Strings über Laravel-Translation-Dateien
- API-Bootstrap liefert Locale-Informationen für eine spätere App

## Neue / geänderte Dateien

- `app/Http/Controllers/LocaleController.php`
- `app/Http/Middleware/SetLocale.php`
- `bootstrap/app.php`
- `routes/web.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`
- `resources/views/layouts/app.blade.php`
- `resources/views/partials/head.blade.php`
- `resources/views/partials/header.blade.php`
- `resources/views/partials/sidebar.blade.php`
- `resources/views/partials/mobile-nav.blade.php`
- `resources/views/partials/footer.blade.php`
- `public/assets/vikinger/css/hunthub-vikinger-start.css`
- `public/assets/vikinger/js/hunthub-start.js`
- `app/Http/Controllers/Api/V1/ApiBootstrapController.php`

## Sprachumschaltung

Neue Route:

```text
/language/de
/language/en
```

Die Sprache wird in der Session gespeichert und durch `SetLocale` pro Request gesetzt.

## Mobile Verhalten

Auf kleinen Viewports:

- Header bleibt kompakt
- Burger öffnet die Sidebar als Offcanvas
- Backdrop schließt die Sidebar
- Escape-Taste schließt die Sidebar
- Footer wird mobil ausgeblendet
- Bottom-Navigation zeigt Feed, Teams, Moments, Chats, Meldungen

## Wichtig

Das ist bewusst nur die technische Grundlage. Die tatsächliche 1:1-Umsetzung der Vikinger-Optik kommt später, wenn alle Kernmodule funktional stabil sind.
