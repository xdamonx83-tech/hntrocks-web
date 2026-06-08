# Auth Basis

Dieses Paket enthält eine bewusst schlanke eigene Auth-Schicht ohne Laravel Breeze/Jetstream.

Enthalten:

- Registrierung
- Login per E-Mail oder Benutzername
- Logout
- Passwort-Reset-Routen und Views
- User-Tabelle
- Password-Reset-Tokens
- Sessions-Tabelle für spätere Umstellung auf `SESSION_DRIVER=database`
- einfacher Account-Platzhalter

Noch nicht enthalten:

- E-Mail-Verifizierung
- Social Login
- Rollen/Rechte
- Zwei-Faktor-Login
- Profilbearbeitung
- API Token Auth/Sanctum

Diese Punkte folgen bewusst später, damit die Basis kontrollierbar bleibt.
