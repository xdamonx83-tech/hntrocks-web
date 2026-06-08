# Patch 511 · Admin Dashboard v2

## Ziel
Nach der neuen Admin-Shell bekommt `/admin` ein echtes Dashboard mit Übersicht, Aufgaben und aktuellen Admin-relevanten Daten.

## Änderungen
- Admin-Startseite optisch neu aufgebaut.
- Große KPI-Karten für Mitglieder, aktive Nutzer, offene Reports und Cup-Feedback.
- 7-Tage-Aktivitätsdiagramm ohne externe JS-Abhängigkeit.
- Bereiche für neue Reports, Cup-Feedback, Feed-Content, Cup-Einreichungen und neue Nutzer.
- System-/Aufgabenliste mit offenen Cup-Einreichungen, App-Beta-Anfragen, Feed/Moments-Aktivität.
- Roadmap-Ideen sichtbar im Adminbereich, damit die nächsten Module greifbar bleiben.
- Eigene CSS-Erweiterung im Admin-Layer.

## Dateien
- app/Http/Controllers/Admin/AdminDashboardController.php
- resources/views/admin/index.blade.php
- resources/views/admin/layouts/app.blade.php
- public/assets/admin/admin.css

## Installation
```bash
php artisan optimize:clear
php artisan view:clear
```

Keine Migration. Keine Routenänderung. Keine Socialite/Vikinger-Frontend-Änderung.
