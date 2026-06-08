# Phase 74 – Team Detail Widgets Polish

## Basis
- Team-Detailseite auf dem Stand nach Phase 73b.
- Die neu hochgeladenen `hunthub-vikinger-start.css` und `styles.min.css` wurden als neue CSS-Wahrheit übernommen.
- Projekt-spezifische CSS-Ergänzungen ab Phase 59 wurden wieder auf die hochgeladene CSS-Basis gelegt, damit Header, Chat, Profil, Team-Modal und Feed-Medienvorschau nicht zurückfallen.

## Änderungen
- Team-Info-Widget näher an Vikinger `Group Info` angepasst.
- Teambeschreibung wurde in das Team-Info-Widget verschoben.
- Mitglieder-Widget nutzt Vikinger-ähnliche Filterzeile und User-Status-Reihen.
- Mitglieder-Avatare nutzen wieder das kleine Vikinger-Hexagon mit Level-Badge.
- Add-Friend-Button erscheint nur, wenn der Betrachter mit dem Mitglied noch nicht befreundet ist und keine offene Anfrage besteht.
- Neues Team-Organizer-Widget für Owner/Officer.
- Controller liefert eine Friendship-Map für die Team-Mitglieder, damit die View nicht raten muss.

## Keine Änderungen
- Keine Migration.
- Keine neuen Models.
- Kein Composer-Install nötig.
- Feed-, Header-, Chat- und Team-Modal-Logik werden nicht umgebaut.
