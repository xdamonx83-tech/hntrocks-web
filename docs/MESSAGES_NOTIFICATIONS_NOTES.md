# Nachrichten & Benachrichtigungen – ZIP 10

Dieser Schritt ergänzt die erste echte Kommunikationsbasis.

## Neue Bereiche

- `/messages` – private Direktnachrichten
- `/notifications` – zentrale Benachrichtigungen

## Neue Tabellen

- `conversations`
- `conversation_participants`
- `messages`
- `user_notifications`

## Aktuelle Funktionen

### Nachrichten

- neue Unterhaltung starten
- bestehende Unterhaltung öffnen
- Nachrichten senden
- private 1:1-Konversationen
- ungelesene Nachrichten zählen
- beim Öffnen einer Konversation wird sie als gelesen markiert

### Benachrichtigungen

- Notification Center
- ungelesene Benachrichtigungen zählen
- einzelne Benachrichtigung öffnen/lesen
- alle Benachrichtigungen als gelesen markieren
- Header-/Sidebar-Badges für Nachrichten und Benachrichtigungen

### Erste Notification-Hooks

- neue Direktnachricht
- neuer Feed-Kommentar
- neuer Feed-Like
- neue LFG-Bewerbung
- LFG-Bewerbung angenommen/abgelehnt
- neue Team-Beitrittsanfrage
- Team-Beitrittsanfrage angenommen/abgelehnt

## Bewusst noch nicht enthalten

- Realtime/WebSocket
- Push-Benachrichtigungen
- Gruppenchats
- Team-Chat
- Medien in Nachrichten
- Vollständiger Vikinger-Drawer

Das kommt später, sobald die Grunddaten sauber stehen.
