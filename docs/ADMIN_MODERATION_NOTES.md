# Admin & Moderation Basis

Stand: ZIP 16

## Enthalten

- Admin-Dashboard unter `/admin`
- Nutzerverwaltung unter `/admin/users`
- Reports unter `/admin/reports`
- Inhaltsmoderation unter `/admin/content`
- neue Admin-Spalte `users.is_admin`
- neue Nutzerstatus-Spalte `users.status`
- Sperrstatus mit `users.suspended_at`
- generische Report-Tabelle `reports`
- Report-Erstellung über `POST /reports`
- Adminrechte für lokalen Startnutzer `admin@hunthub.local` im Seeder

## Wichtig

Der Adminzugriff wird aktuell im Controller über `auth()->user()->isAdmin()` geprüft.
Später kann daraus eine eigene Middleware/Policy-Schicht werden.

## Erste Statuslogik

Nutzer:
- `active`
- `suspended`

Reports:
- `open`
- `in_review`
- `resolved`
- `rejected`

Feed/Moments:
- `published`
- `hidden`
- `removed`

Medien:
- `ready`
- `hidden`
- `quarantined`
- `removed`

Teams:
- `active`
- `archived`
- `suspended`

Cups:
- `planned`
- `active`
- `finished`
- `archived`

## Noch offen

- Vollständige Report-Buttons in allen Frontend-Modulen
- Admin-Detailseiten je Inhalt
- Audit-Log
- Rollen/Rechte feiner als `is_admin`
- automatische Medienprüfung
- Admin-Benachrichtigungen
- Bulk-Aktionen
