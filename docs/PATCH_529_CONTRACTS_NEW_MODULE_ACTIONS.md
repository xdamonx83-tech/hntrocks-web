# Patch 529 – HNT-Aufträge mit neuen Modulen verknüpft

Dieser Patch erweitert die bestehende Quest-/XP-/Weekly-Contract-Logik um Aktionen aus den neueren Modulen.

## Neue Contract-Aktionen

- `cup_idea_submitted` – Nutzer reicht eine Cup-Idee ein
- `cup_idea_voted` – Nutzer stimmt für eine Cup-Idee ab
- `loadout_challenge_submission_created` – Nutzer reicht eine Loadout-Challenge ein
- `loadout_challenge_submission_accepted` – Admin nimmt eine Loadout-Challenge-Einreichung an
- `moment_of_week_selected` – ein Moment des Nutzers wird als Moment der Woche aktiv gesetzt

## Technisches Verhalten

- Die neuen Aktionen erscheinen im Adminbereich bei HNT-Aufträgen als auswählbare Aktionen.
- Fortschritt läuft über die vorhandene `GamificationService`-/`QuestProgress`-Logik.
- Für Cup-Ideen-Votes wird nur ein neuer Vote belohnt. Unvote selbst löst nichts aus. Re-Votes auf dieselbe Idee werden durch die vorhandene `oncePerSource`-Logik nicht erneut belohnt.
- Loadout-Challenge-Einreichungen lösen jetzt bereits beim Einreichen XP/Contract-Fortschritt aus. Die Annahme durch Admin bleibt zusätzlich als eigene Aktion erhalten.
- Moment der Woche löst XP/Contract-Fortschritt nur aus, wenn der Spotlight aktiv und als aktiv markiert ist.

## Nicht umgesetzt

- Approved-Outbound-Link-Klicks wurden bewusst nicht als Contract-Aktion verknüpft, weil Klicks zu leicht farmbar sind.
- Keine Migration, keine neue Route, keine Android-Änderung.
