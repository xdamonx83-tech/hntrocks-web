# Phase 24 – Vikinger Shell Korrektur

Dieser Schritt korrigiert die globale Vikinger-Shell aus Phase 23, ohne einzelne Module umzubauen.

## Geändert

- Desktop-Sidebar an die echte Vikinger-Struktur angepasst:
  - `#navigation-widget-small` als schmale Icon-Leiste
  - `#navigation-widget` als ausklappbare große Sidebar
- Header-Trigger nutzen jetzt die Vikinger-JS-Struktur statt der alten eigenen Toggle-Logik
- Mobile-Sidebar nutzt jetzt `#navigation-widget-mobile` und den erwarteten Close-Button
- Content-Grid enger an Vikinger ausgerichtet
- Header-Abstände, Suche, Level-Anzeige, Userbereich und Sprachumschalter harmonisiert
- alte doppelte Sidebar-Toggle-Logik in `hunthub-start.js` entschärft
- großflächige 300px-Dauer-Sidebar auf Desktop entfernt
- responsive Header-Breakpoints ergänzt

## Bewusst noch nicht gemacht

- Keine Feed-1:1-Umsetzung
- Keine Mitgliederkarten-1:1-Umsetzung
- Keine Profil-/Team-Detailseiten
- Keine Marketplace-Funktionen
- Keine Store-Assets/Funktionen

## Nächster sinnvoller Schritt

Als nächstes sollte der Newsfeed optisch an `newsfeed.html` angeglichen werden, weil der Feed die zentrale Social-Startseite ist.
