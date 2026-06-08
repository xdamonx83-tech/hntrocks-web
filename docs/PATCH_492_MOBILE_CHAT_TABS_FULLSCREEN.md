# Patch 492 – Mobile Chat Tabs Fullscreen v1

## Ziel

Die Socialite-Chat-Tabs bleiben auf Desktop als Facebook-ähnliche Tabs unten rechts. Auf Mobile werden geöffnete Chat-Tabs jetzt fullscreen angezeigt.

## Änderungen

- Mobile Chat-Tab-Shell nutzt den gesamten Viewport.
- Chat-Tab auf Mobile: 100vw / 100vh / 100dvh.
- Keine runden Desktop-Ecken und kein Desktop-Schatten auf Mobile.
- Minimize-Button wird auf Mobile ausgeblendet.
- Falls mehrere Tabs offen sind, wird auf Mobile nur der zuletzt geöffnete Tab angezeigt.
- Schließen zeigt den vorherigen offenen Tab wieder an, falls vorhanden.
- Safe-Area-Abstände für iOS/Android Browser unten/oben ergänzt.

## Geänderte Dateien

- resources/views/themes/socialite/partials/chat-tabs.blade.php

## Installation

php artisan optimize:clear
php artisan view:clear

Keine Migration, keine Routenänderung, kein Composer nötig.
