# Phase 46 – Profil + Friend-System Basis

Umgesetzt auf aktuellem Stand nach Phase 45.

## Enthalten

- Neue Tabelle `friendships`
- Neues Model `App\Models\Friendship`
- Neuer Controller `App\Http\Controllers\Friends\FriendshipController`
- Routen für Anfrage senden, annehmen, ablehnen und entfernen
- Profilheader zeigt echten Friend-Status:
  - Add Friend +
  - Anfrage gesendet
  - Annehmen / Ablehnen
  - Freunde / Entfernen
- Notifications bei neuer Freundschaftsanfrage und angenommener Anfrage
- Profil-Stats zeigen Freunde statt Trust

## Bewusst nicht enthalten

- kein kompletter Members-Rework
- keine AJAX-Friend-Buttons
- keine Freundesliste als eigene Seite
- keine Änderungen an Feed/Sidebar
