# Patch 532 - Cup-Regelprofile, Mini-Cup-Limits und KI-Prompt-Auswahl

Dieser Patch erweitert das vorhandene Cup-System, ohne das alte Regelwerk zu entfernen.

## Neu

- Cup-Erstellung/Bearbeitung hat nun ein Regelprofil:
  - Klassischer Bounty-Cup
  - Konsolen-Mini-Cup mit Fairness-Regeln
- Cup-Erstellung/Bearbeitung hat nun ein KI-Prüfprofil:
  - klassische Match-Summary
  - Match-Summary + Plattformhinweise
- Plattform-Gate über erlaubte Plattformen:
  - PC
  - PlayStation
  - Xbox
- Teilnahmebedingungen pro Cup:
  - 100 % Profil erforderlich
  - Mindestanzahl Community-Aktionen
- Upload- und Wertungslimits pro Cup:
  - maximale Uploads pro Teilnehmer
  - maximale gewertete Runs pro Teilnehmer
- Leaderboard kann nur die besten X gültigen Runs zählen.
- KI-Prompt für Konsolen-Cups versucht zusätzlich sichtbare Plattformhinweise zu erkennen.
- Plattform-Unklarheit oder Plattform-Mismatch blockiert nicht blind, sondern geht in manuelle Prüfung.

## Bewusste Entscheidung

Boss-Kill-Bonus und 1v3-/Risiko-Bonus wurden noch nicht eingebaut. Das Feedback ist wichtig, aber diese Regeln sind schwer zuverlässig automatisch zu prüfen und sollten erst in einem eigenen Scoring-Ausbau kommen.

## Empfohlene Einstellung für den Konsolen-Mini-Cup

- Regelprofil: Konsolen-Mini-Cup · Fairness-Regeln
- KI-Prompt: Match-Summary + Konsolenhinweise
- Erlaubte Plattformen: PlayStation + Xbox
- Max. Uploads pro Teilnehmer: 7
- Max. gewertete Runs: 5
- 100 % Profil voraussetzen: ja
- Mindest-Community-Aktionen: 1

## Geänderte Dateien

- app/Models/Cup.php
- app/Http/Controllers/Cups/CupController.php
- app/Http/Controllers/Cups/CupTeamController.php
- app/Http/Controllers/Cups/CupSubmissionController.php
- app/Services/Cups/CupSubmissionAnalysisService.php
- resources/views/cups/partials/cup-form.blade.php
- resources/views/themes/socialite/cups/show.blade.php
- resources/lang/de/ui.php
- resources/lang/en/ui.php
