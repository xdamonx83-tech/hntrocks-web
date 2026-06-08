# Patch 105 – Mobile Cup Submission API

## Ziel

Dieser Patch erweitert die vorhandene Laravel-API für die spätere native Android-App. Er ergänzt die Cup-Teilnahme und Cup-Screenshot-Einreichung als JSON/API-Flow, ohne die bestehende Web-UI umzubauen.

## Neue API-Endpunkte

Alle Endpunkte liegen hinter `api.token` und erwarten:

```http
Accept: application/json
Authorization: Bearer <TOKEN>
```

### Cup registrieren / teilnehmen

```http
POST /api/v1/cups/{slug}/register
```

Für Solo-Leaderboards wie den Bayou Blood Cup wird automatisch ein Teilnehmer-Eintrag für den eingeloggten Nutzer angelegt. Für Team-Cups kann optional/erforderlich `name` übergeben werden.

Antwort enthält unter anderem:

- `registered`
- `team`

### Cup-Screenshot einreichen

```http
POST /api/v1/cups/{slug}/submissions
Content-Type: multipart/form-data
```

Felder:

- `screenshot` – Pflicht, Bilddatei
- `note` – optional

Der API-Flow nutzt dieselbe Grundlogik wie die Web-Einreichung:

- nur Teilnehmer können einreichen
- Cup muss aktiv sein
- Cooldown wird geprüft
- Upload-/Moderationsprüfung bleibt aktiv
- private Speicherung der Screenshots bleibt erhalten
- KI/OCR-Auswertung bleibt aktiv
- Gamertag-Konsistenzprüfung bleibt aktiv
- Team-/Teilnehmer-Totals werden neu berechnet
- Admin-Benachrichtigungen werden gesendet

## Geänderte Dateien

```text
routes/api.php
routes/console.php
app/Http/Controllers/Api/V1/ApiBootstrapController.php
app/Http/Controllers/Api/V1/ApiCupsController.php
app/Http/Resources/Api/CupSubmissionResource.php
docs/PATCH_105_MOBILE_CUP_SUBMISSION_API.md
```

## Nicht enthalten

- keine Migration
- kein Composer-Paket
- keine Android-App-Dateien
- kein WebView
- kein Chat
- kein Push
- keine Änderung an Mail
- keine Änderung am bestehenden Web-Submit-Formular

## Deployment

Da eine bestehende PHP-Klasse deutlich erweitert wird:

```bash
cd /home/users/hunthub/www/hnt.rocks
unzip 105_hnt_mobile_cup_submission_api.zip -d .

composer dump-autoload
php artisan optimize:clear
php artisan route:clear
php artisan view:clear

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Testbefehle

Token holen:

```bash
curl -X POST https://hnt.rocks/api/v1/auth/login \
  -H "Accept: application/json" \
  --data-urlencode "login=DEIN_LOGIN" \
  --data-urlencode "password=DEIN_PASSWORT" \
  --data-urlencode "device_name=Android Test"
```

Cup öffnen:

```bash
curl https://hnt.rocks/api/v1/cups/bayou-blood-cup \
  -H "Accept: application/json" \
  -H "Authorization: Bearer DEIN_TOKEN"
```

Teilnehmen:

```bash
curl -X POST https://hnt.rocks/api/v1/cups/bayou-blood-cup/register \
  -H "Accept: application/json" \
  -H "Authorization: Bearer DEIN_TOKEN"
```

Screenshot einreichen:

```bash
curl -X POST https://hnt.rocks/api/v1/cups/bayou-blood-cup/submissions \
  -H "Accept: application/json" \
  -H "Authorization: Bearer DEIN_TOKEN" \
  -F "screenshot=@/pfad/zum/summary-screen.jpg" \
  -F "note=Android API Test"
```

## Hinweis

Der Cup muss für echte Einreichungen `active` sein. Wenn er noch `planned` ist, antwortet die API bewusst mit einem 422-Fehler.
