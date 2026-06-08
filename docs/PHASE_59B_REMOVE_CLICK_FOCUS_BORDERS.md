# Phase 59b – Remove click focus borders

Geändert:
- public/assets/vikinger/css/hunthub-vikinger-start.css

Ziel:
- Hässliche Browser-Focus-Border/Outline nach Klick auf Header-Icons, Logo, Links, Sidebar-Items und Chat-Dock-Buttons entfernen.
- Formularfelder bleiben bewusst unangetastet, damit Inputs weiterhin ihre vorhandene Focus-Optik behalten.

Deployment:
- php artisan view:clear
- php artisan optimize:clear
- chown/chmod wie üblich
- Browser hart neu laden wegen CSS
