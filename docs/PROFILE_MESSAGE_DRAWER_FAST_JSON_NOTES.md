# Patch 61 – Profile Message Drawer Fast JSON

## Zweck

Patch 60 öffnete den Drawer zwar sofort, wartete aber weiterhin zu lange auf die Serverantwort. Ursache war, dass der JSON-Endpunkt beim Öffnen vom Profil zu viel Nebenarbeit erledigte: Trigger-HTML für die Chatliste, Unread-Count-Berechnung und teilweise zusätzliche Relation-/Message-Abfragen.

## Änderungen

- Der Desktop-JSON-Endpunkt `/messages/with/{user}` liefert nur noch das wirklich benötigte Conversation-Panel.
- Kein `trigger_html` mehr im JSON-Response für den Profil-Klick.
- Kein globaler `unreadMessagesCount()` mehr beim Öffnen des Profil-Drawers.
- Die letzten 30 Nachrichten werden einmal im Controller geladen und an das Panel übergeben.
- Das Panel-Partial nutzt vorhandene `$hhChatMessages` und `$hhChatPartner`, wenn sie übergeben wurden, und fällt sonst auf die bisherige Logik zurück.

## Wirkung

Der Drawer öffnet weiterhin sofort. Die echte Conversation sollte deutlich schneller den Ladezustand ersetzen.

## Migration

Keine Migration nötig.
