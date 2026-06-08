# hnt.rocks Datenexport

Der Datenexport unter `GET /settings/security/export` erzeugt einen JSON-Export für den eingeloggten Nutzer.

## Enthaltene Bereiche

- Account-Stammdaten ohne Passwort-Hash und ohne Remember-Token
- Profil, Privacy- und Notification-Einstellungen
- Social-Login-Verknüpfungen inklusive gespeicherter Provider-Profil-Rohdaten
- API-Token-Metadaten ohne Token-Hash
- Sicherheitsereignisse und Session-Metadaten ohne Session-Payload
- Medien-Metadaten und Datei-Referenzen ohne eingebettete Binärdateien
- Feed-Beiträge, Kommentare, Reaktionen und Bookmarks
- Mentions als Ersteller und erwähnter Nutzer
- LFG und Team-LFG inklusive eigener Bewerbungen und Bewerbungen auf eigene Posts
- eigene Teams und eigene Team-Mitgliedschaften
- Nachrichten-Konversationen, an denen der Nutzer beteiligt ist
- Benachrichtigungen
- Freundschaften und Blockierungen
- Moments
- Cups, Cup-Teams, Cup-Mitgliedschaften und Cup-Einreichungen
- Gamification-Daten wie XP, Badges und Quest-Fortschritt
- Referrals und Giveaway-Einträge
- Reports, die vom Nutzer erstellt wurden oder den Nutzer bzw. seine Inhalte betreffen
- verknüpfte Visitor-Tracking-Ereignisse

## Bewusst nicht enthalten

- Passwort-Hash
- Remember-Token
- Session-Payload
- Password-Reset-Token
- API-Token-Hash
- Binärdaten hochgeladener Dateien

## Hinweise

Der Export ist als strukturierter technischer JSON-Export gedacht. Er ersetzt keine anwaltliche Prüfung, ob alle konkreten Betroffenenrechte und Lösch-/Aufbewahrungsfristen für den Livebetrieb vollständig abgebildet sind.
