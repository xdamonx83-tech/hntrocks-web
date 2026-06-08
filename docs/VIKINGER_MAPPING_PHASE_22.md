# Phase 22 – Vikinger-Struktur-Mapping

Stand: Phase 22

Dieser Schritt verändert bewusst noch nicht die globale Optik der produktiven Module. Er legt zuerst verbindlich fest, welche Vikinger-HTML-Dateien als visuelle Referenz für welche Hunthub-Module gelten.

## Grundentscheidung

- Hunthub bleibt funktional Laravel-basiert.
- Controller, Models, Migrationen und Services sind die technische Wahrheit.
- Vikinger ist die visuelle und strukturelle Referenz.
- Marketplace-/Store-Funktionen werden nicht übernommen.
- Vikinger-Groups werden fachlich als Hunthub-Teams interpretiert.
- Der Design-Umbau erfolgt lokal und schrittweise, nicht als großer Blind-Rewrite.

## Neue interne Übersicht

Für Admins gibt es jetzt eine Mapping-Übersicht:

```text
/admin/vikinger-mapping
```

Dort sind pro Modul hinterlegt:

- Hunthub-Bereich
- aktuelle Route
- Vikinger-Referenzdateien
- Ziel-Views in Laravel
- Status
- Hinweise für den späteren 1:1-Umbau

## Neue zentrale Mapping-Konfiguration

```text
config/vikinger.php
```

Diese Datei ist ab jetzt die verbindliche Mapping-Quelle für die Vikinger-Designphase.

## Bewusst nicht gemacht

- Keine globale Einbindung von `styles.min.css` aus dem Template.
- Keine automatische Übernahme der Demo-JavaScript-Dateien.
- Keine Marketplace-Funktion.
- Kein Umbau aller Views auf einmal.
- Keine Änderung der Datenbank.

Der Grund: Die offizielle Vikinger-CSS/JS-Struktur greift stark in Layout, Header, Sidebar, Dropdowns, Popups und Demo-Widgets ein. Wenn sie global blind geladen wird, kann sie bestehende funktionierende Module brechen. Deshalb kommt zuerst das Mapping, danach die Shell.

## Nächster Schritt

Phase 23 sollte die echte Vikinger-Shell bauen:

1. `layouts/app.blade.php` an Vikinger-Grundstruktur angleichen.
2. `partials/header.blade.php` auf Vikinger-Header-Struktur umbauen.
3. `partials/sidebar.blade.php` auf Vikinger-Sidebar-Struktur umbauen.
4. `partials/head.blade.php` gezielt um benötigte Vikinger-Assets erweitern.
5. Produktive Seiten noch nicht vollständig optisch umbauen, sondern nur sauber in der neuen Shell laufen lassen.

Danach erst Phase 24: Feed-Optik.
