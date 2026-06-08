# Patch 494 – Socialite sichtbare Texte i18n Cleanup v1

Scope:
- Nur sichtbare Socialite-Oberflächen.
- Keine Vikinger-Views.
- Keine Routen, Controller, Models oder Datenbankänderungen.
- Keine Funktionslogik bewusst umgebaut; nur sichtbare Labels, Buttons, Platzhalter, JS-Statusmeldungen und Modaltexte auf Sprachkeys gesetzt.

Geändert:
- Feed Live: Filter, Composer-Preview, Widgets, Kommentar-/Post-Leerzustände, Post-Menüs, Poll-/Media-Texte.
- Feed-Kommentare: Like/Liked/Reply und JS-Texte übersetzbar.
- Globaler Socialite-Composer/Tail: Report-Modal, GIF-Status, Report-Status, Kommentar-AJAX-Texte, Confirm-Texte.
- Moments Create/Viewer/Empty: sichtbare Labels, Buttons, Status- und Modaltexte.
- Profil Show/Edit: sichtbare Profiltexte, Crop-Modal/Status, Cover-/Avatar-Texte, leere Zustände.
- Messages: sichtbare Such-/Buttonlabels.
- Sidebar/Header: sichtbare Menütexte, Shortcuts und Badges/Quests.
- LFG/Team-LFG/Teams: sichtbare Classic/Preview/Cancel/Media-Labels.
- App-Beta: sichtbares Website-Label.
- DE/EN-Sprachkeys ergänzt.

Bewusst nicht angefasst:
- Vikinger-Altviews.
- Socialite-Demo-Preview `resources/views/themes/socialite/feed/index.blade.php`, da sie eine Design-/Preview-Seite ist und nicht die normale Live-Oberfläche.
- Minified/vendor JS wie UIkit/Simplebar.

Installation:
```bash
php artisan optimize:clear
php artisan view:clear
```
