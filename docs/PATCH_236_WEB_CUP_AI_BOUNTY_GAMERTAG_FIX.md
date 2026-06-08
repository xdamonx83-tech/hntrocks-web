# Patch 236 – Cup AI Bounty Token & Gamertag False-Review Fix

## Ziel
Mehrere gültige Hunt: Showdown Mission-Summary-Screens wurden falsch gewertet:
- Extract wurde erkannt, aber Bounty Token wurde als 0/null erkannt, obwohl rechts in der Bounty-Token-Zeile +1/+2/+3/+4 sichtbar ist.
- Gamertags wurden häufiger als „nicht sicher lesbar“ markiert, obwohl sie im Bloodline-Bereich sichtbar sind.

## Geänderte Dateien
- `app/Services/Cups/CupSubmissionAnalysisService.php`
- `app/Http/Controllers/Cups/CupSubmissionController.php`
- `app/Http/Controllers/Api/V1/ApiCupsController.php`

## Änderungen
### Bounty Token
- Der alte Bounty-Fokus-Crop enthielt teilweise auch Hunter-Progress-XP unter der Bounty-Zeile.
- Der Bounty-Check nutzt jetzt zusätzlich engere Row-/Value-Crops.
- Der Prompt wurde präzisiert: Image 3 = enge Bounty-Token-Zeile, Image 4 = enger Wert-Crop.
- Normale Hunt-Kompression/Grain soll nicht als Manipulation gewertet werden.

### Gamertag
- Zusätzlicher enger Crop für den Bloodline-Progress-/Gamertag-Bereich.
- Prompt wurde weniger überstreng formuliert.
- Default-Gamertag-Confidence wurde von 0.72 auf 0.62 gesenkt.
- Ein nur unsicher lesbarer Gamertag blockiert eine sonst gültige Submission standardmäßig nicht mehr.
- Echte Mismatches werden weiterhin zur Prüfung markiert, sobald ein zuverlässiger abweichender Gamertag erkannt wird.

## Optionaler Strict-Modus
Wer weiterhin unsicher lesbare Gamertags blockieren will, kann setzen:

```
HH_CUP_GAMERTAG_UNREADABLE_BLOCKS=true
```

Standard ist bewusst `false`, damit gültige Runs nicht wegen zu strenger Namens-Erkennung mit 0 Punkten landen.
