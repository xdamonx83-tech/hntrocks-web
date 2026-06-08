# Phase 32 – Profil-Vikinger-Vorbereitung

Basis: `31_hunthub_referrals_500_blade_fix.zip`

## Ziel

Nach bestätigter Stabilitätsprüfung der Kernrouten wurde die Profilanzeige optisch näher an das Vikinger-Profiltemplate (`profile-timeline.html`) geführt, ohne Profilbearbeitung, Uploads, Auth, Feed, Teams oder Gamification strukturell umzubauen.

## Geändert

- `app/Http/Controllers/Profile/ProfileController.php`
  - lädt Profilzähler für Posts, Teams, Badges, Moments und Kommentare
  - lädt bis zu fünf sichtbare Profil-Timeline-Beiträge mit denselben Relationen wie der Feed
  - lädt aktuelle Badges und aktive Teams für Seitenwidgets

- `resources/views/profile/show.blade.php`
  - ersetzt die alte eigene Profil-Shell durch Vikinger-nahe Struktur
  - nutzt Profile Header, Section Navigation, 3-6-3 Grid, Widget-Boxen und Feed-Post-Cards
  - erhält öffentliche Profilroute `/u/{username}` und eigene Profilroute `/profile`
  - erhält Profilbearbeiten-Link, Profilfortschritt, Avatar/Cover, Bio, Spielerdaten und Social Links

- `public/assets/vikinger/css/hunthub-vikinger-start.css`
  - minimale Ergänzungen für reale Hunthub-Bilder, Badge-Token, Teamliste und mobile Profilansicht

## Keine Änderung

- keine Migration
- keine neuen Models
- keine neuen Routen
- keine Composer-Abhängigkeiten
- keine Änderung an Feed-, Team-, LFG-, Moments-, Cups- oder Admin-Logik
