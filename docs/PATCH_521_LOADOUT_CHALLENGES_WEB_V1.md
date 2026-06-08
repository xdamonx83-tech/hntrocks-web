# Patch 521 – Loadout-Challenges Web v1

## Ziel
Loadout-Challenges als erstes stabiles Web-v1-System für HNT.rocks.

## Enthalten
- öffentliche Übersicht `/loadout-challenges`
- Detailseite `/loadout-challenges/{slug}`
- Nutzer-Einreichung mit Beschreibung, Ergebnis und optionalem Screenshot/Clip
- Admin-Verwaltung `/admin/loadout-challenges`
- Admin-Prüfung von Einreichungen mit Status: pending, accepted, rejected
- XP-Vergabe bei angenommener Einreichung
- Benachrichtigung bei Statusänderung
- Socialite-Sidebar-Link
- Admin-Sidebar-Link
- deutsche und englische UI-Texte
- Upload-Limit `HH_UPLOAD_LOADOUT_CHALLENGE_MEDIA_MB`, Default 100 MB

## Bewusst nicht enthalten
- keine Android-Integration
- keine neue API
- kein automatisches KI-Scoring
- kein Wochenranking
- keine neue Upload-Pipeline, sondern Wiederverwendung von MediaService
