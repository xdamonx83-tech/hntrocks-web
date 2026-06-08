# Phase 58d — Chat-Dock Button und Message-Notifications

Geändert:
- Der grüne Chat-Dock-Button wurde näher am Vikinger-Original ausgerichtet.
- Im geschlossenen Zustand ist nur das Burger-Icon im sichtbaren grünen Bereich zentriert.
- Private Nachrichten erzeugen keine normalen UserNotifications vom Typ `message_new` mehr.
- Die eigentliche Message-Unread-Logik bleibt erhalten, weil sie über Conversations/Messages läuft.

Keine Migrationen. Keine neuen Klassen. Keine Composer-Abhängigkeiten.
