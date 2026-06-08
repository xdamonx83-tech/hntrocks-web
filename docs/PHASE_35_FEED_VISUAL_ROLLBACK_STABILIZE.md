# Phase 35 – Feed Visual Rollback / Stabilisierung

Dieser Patch setzt die fehlerhaften visuellen Feed-Änderungen aus Phase 33/34 zurück.

Ziel:
- `/feed` wieder aus der verschlimmerten Darstellung holen
- Profil-Timeline wieder auf den vorherigen stabileren Feed-Partial-Stand zurücksetzen
- keine neuen Routen, Tabellen, Controller, Models oder Migrationen
- keine neue Friend-Request-Logik erfinden

Geänderte Dateien:
- resources/views/feed/index.blade.php
- resources/views/feed/partials/post-card.blade.php
- resources/views/profile/show.blade.php
- public/assets/vikinger/css/hunthub-vikinger-start.css

Basis:
- 31_hunthub_referrals_500_blade_fix.zip
- 32_hunthub_profile_vikinger_prep.zip

Hinweis:
Dieser Patch ist bewusst ein Stabilisierungs-/Rollback-Patch. Der eigentliche 1:1-Nachbau des Vikinger-Feeds sollte danach anhand der echten HTML-Blöcke neu und kontrolliert erfolgen, nicht durch weitere CSS-Überlagerungen auf dem kaputten Stand.
