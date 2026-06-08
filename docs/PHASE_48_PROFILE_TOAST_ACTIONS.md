# Phase 48 – Profile Toast + kompakte Friend-Actions

Ziel: Profil-Friend-Feedback und Header-Actions sauberer machen, ohne das Friend-System technisch umzubauen.

Änderungen:
- Statusmeldung auf der Profilseite wird als Floating-Toast angezeigt statt als großer Alert über dem Profil.
- Ausgehende Freundschaftsanfrage nutzt nur noch einen kompakten Button `Gesendet`.
- Eingehende Freundschaftsanfrage nutzt kompakte Icon-Buttons für Annehmen/Ablehnen.
- Bestehende Aktionen bleiben funktional: Anfrage senden, zurückziehen, annehmen, ablehnen, Freundschaft entfernen, Nachrichten.
- CSS positioniert die Friend-Actions im Profilheader rechts, damit sie nicht mehr hinter dem zentralen Avatar verschwinden.

Keine neuen Routen, keine Migration, keine neuen Klassen.
