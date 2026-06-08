# Phase 23 – Vikinger Shell Basis

Dieser Schritt zieht die globale Hunthub-Shell näher an das Vikinger-Template, ohne die einzelnen Module frei umzubauen.

## Geändert

- echte Vikinger-Core-Assets eingebunden:
  - `css/styles.min.css`
  - `css/vendor/bootstrap.min.css`
  - `css/vendor/simplebar.css`
  - `css/vendor/tiny-slider.css`
  - Runtime-JS aus `js/vendor`, `js/global`, `js/header`, `js/sidebar`, `js/content`, `js/form`, `js/utils`
- Marketplace-/Store-HTML wird nicht übernommen
- Marketplace-Bildassets werden nicht kopiert
- globale Layoutstruktur nutzt jetzt Vikinger-Klassen:
  - `header`
  - `navigation-widget`
  - `content-grid`
  - `page-loader`
- Hunthub-spezifische Modul-Views bleiben funktional und werden nur durch eine Shell-Override-Schicht visuell angenähert
- Header wurde auf Vikinger-Struktur umgebaut
- Sidebar wurde auf Vikinger-Menüstruktur umgebaut
- mobile Sidebar bleibt zusätzlich kontrolliert durch `hunthub-start.js`

## Bewusst noch nicht gemacht

- Keine komplette Feed-1:1-Umsetzung von `newsfeed.html`
- Keine Profil-Detailoptik aus `profile-timeline.html`
- Keine Teamseiten-Detailoptik aus `group-timeline.html`
- Keine Marketplace-Funktionen
- Keine Store-Assets/Funktionen

## Nächster sinnvoller Schritt

Nach einem Funktionstest der Shell sollte als erstes der Newsfeed optisch an `newsfeed.html` angeglichen werden, weil er die Startseite und das zentrale Social-Modul ist.
