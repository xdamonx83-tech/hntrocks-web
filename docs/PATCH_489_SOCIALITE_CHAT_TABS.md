# Patch 489 – Socialite Chat Tabs v1

## Ziel

Der alte feste Socialite-Demo-Chat unten rechts wurde entfernt. Private Konversationen aus dem Header-Messages-Dropdown öffnen jetzt als Facebook-/Instagram-ähnliche Chat-Tabs unten rechts.

## Umsetzung

- Neue JSON-Route `messages.chat-tab` für private Konversationen.
- Header-Dropdown-Konversationen bleiben als normale Links nutzbar, werden bei aktiviertem JavaScript aber als Chat-Tab geöffnet.
- Mehrere Tabs können parallel geöffnet werden.
- Tabs können minimiert, geschlossen oder in der vollständigen Nachrichtenansicht geöffnet werden.
- Nachrichten werden im Tab per AJAX gesendet.
- Der bestehende MessageController und die bestehende `messages.store`-Logik bleiben die Grundlage.
- Keine Migration, keine neue Tabelle, keine Änderung am bestehenden Message-Schema.

## Geänderte Dateien

- `app/Http/Controllers/Messages/MessageController.php`
- `routes/web.php`
- `resources/views/themes/socialite/partials/header.blade.php`
- `resources/views/themes/socialite/partials/tail.blade.php`
- `resources/views/themes/socialite/partials/chat-tabs.blade.php`
- `resources/views/themes/socialite/messages/partials/chat-tab.blade.php`
- `public/assets/socialite/js/hnt-socialite-chat-tabs.js`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Hinweise

V1 ist bewusst nur für private Konversationen im Header-Dropdown gedacht. LFG-/Team-LFG-Konversationen bleiben in der vollständigen Messages-Seite, damit die Tabs nicht direkt mit Kontext-Chats überladen werden.
