# Phase 57b – Friend Requests aus normalen Notifications herausfiltern

Basis: aktueller stabiler Stand nach Phase 55 plus akzeptierte Phasen 56 und 57.

Ziel:
- Eingehende Freundschaftsanfragen werden nicht mehr in den normalen Notifications angezeigt.
- Offene Freundschaftsanfragen bleiben ausschließlich im Friend-Requests-Dropdown sichtbar und bearbeitbar.
- Angenommene Freundschaftsanfragen bleiben weiterhin normale Notifications.

Technische Umsetzung:
- `UserNotification::scopeStandard()` ergänzt.
- `User::unreadNotifications()` zählt ebenfalls keine reinen Freundschaftsanfragen mehr.
- Typ `friend_request` wird aus normalen Notification-Listen und Counts ausgeschlossen.
- Header, Sidebar, Mobile-Nav, Notifications-Seite und API-Notification-Counts nutzen Standard-Notifications.
- Neue Freundschaftsanfragen erzeugen keine normale `friend_request`-Notification mehr.

Nicht geändert:
- Keine Migration.
- Keine Composer-Abhängigkeit.
- Kein Umbau von Messages oder weiteren Header-Dropdowns.
