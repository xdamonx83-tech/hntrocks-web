# Phase 63 – Profil/Sidebar echte Badges & Quests

## Ziel

Die Gamification-Daten aus Phase 60–62 werden sichtbarer in den normalen Nutzerflächen.

## Enthalten

- Sidebar nutzt echte Nutzer-Badges statt statischer Vikinger-Demo-Badges.
- Sidebar zeigt einen kleinen Quest-Fortschritts-Pill mit erledigten Quests.
- Profilheader zählt erledigte Quests.
- Profilseite bekommt ein echtes Quest-Widget mit Fortschritt, XP, Icon und Status.
- Keine neue Migration.

## Deployment

Nur View/CSS/Controller-Änderungen. Kein composer install nötig.

```bash
php artisan view:clear
php artisan optimize:clear
```
