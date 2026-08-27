#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NO="400"
HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
MAIN_SHA="18d4febb5be55c9a5b2fc8b10233bf3d23ffb4ac"
FEATURE_BRANCH="agent/app-remote-backgrounds"
FEATURE_HEAD="3ea6b434e13eec0d4553d12b9e1b5efd7b5f1fc9"
STAMP="$(date +%Y%m%d-%H%M%S)"
WT="$HUB/.worktrees/${PATCH_NO}-remote-app-backgrounds-${STAMP}"
DB_FILE="$HUB/.worktrees/${PATCH_NO}-remote-app-backgrounds-${STAMP}.sqlite"
LOG="$HUB/${PATCH_NO}_test_remote_app_backgrounds_${STAMP}.log"

EXPECTED_FILES=(
  "app/Http/Controllers/Admin/AdminAppRemoteConfigController.php"
  "app/Http/Controllers/Api/V1/AppRemoteConfigController.php"
  "app/Http/Middleware/AuthenticateApiToken.php"
  "app/Services/AppConfig/AppRemoteConfigService.php"
  "resources/views/admin/app-remote-config/index.blade.php"
  "tests/Feature/AppRemoteConfigAppearanceTest.php"
  "tests/Feature/AppRemoteConfigPublicReadTest.php"
)

mkdir -p "$(dirname "$LOG")"
exec > >(tee -a "$LOG") 2>&1

cleanup() {
  set +e
  git -C "$REPO" worktree remove --force "$WT" >/dev/null 2>&1 || true
  git -C "$REPO" worktree prune >/dev/null 2>&1 || true
  rm -f "$DB_FILE" >/dev/null 2>&1 || true
  set -e
}
trap cleanup EXIT

fail() {
  echo
  echo "FEHLER: $*" >&2
  exit 2
}

step() {
  printf '\n[%s/10] %s ...\n' "$1" "$2"
}

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 400 - TEST REMOTE APP BACKGROUNDS
============================================================
- testet NUR den Backend-Feature-Branch agent/app-remote-backgrounds
- bestehender Endpoint /api/v1/app/remote-config bleibt erhalten
- Auth-Hintergrund + Feed-Hintergrund: URL + Version
- Guest-Read fuer Login/Register vor Auth wird geprueft
- Feed-Card-Dismiss bleibt geschuetzt
- Upload-Regeln JPG/JPEG/PNG/WebP, max. 8 MB werden geprueft
- Versionslogik und Cache-Invalidierung werden gegen isoliertes SQLite geprueft
- KEINE PRODUKTIONS-DB / KEINE MIGRATION AUF PRODUKTION / KEIN DEPLOY
- main wird NICHT veraendert / KEIN MERGE / KEIN PUSH DURCH DIESES SCRIPT
============================================================
HEAD

step 1 "Produktives Laravel-Repo und exakte Git-Basis verifizieren"
[[ -d "$REPO/.git" || -f "$REPO/.git" ]] || fail "$REPO ist kein Git-Checkout"
git -C "$REPO" fetch origin main "$FEATURE_BRANCH" --prune
MAIN_NOW="$(git -C "$REPO" rev-parse origin/main)"
FEATURE_NOW="$(git -C "$REPO" rev-parse "origin/$FEATURE_BRANCH")"
MERGE_BASE="$(git -C "$REPO" merge-base origin/main "origin/$FEATURE_BRANCH")"
echo "origin/main:  $MAIN_NOW"
echo "Feature-Head: $FEATURE_NOW"
echo "Merge-Base:   $MERGE_BASE"
[[ "$MAIN_NOW" == "$MAIN_SHA" ]] || fail "origin/main ist $MAIN_NOW, erwartet $MAIN_SHA"
[[ "$FEATURE_NOW" == "$FEATURE_HEAD" ]] || fail "Feature-Head ist $FEATURE_NOW, erwartet $FEATURE_HEAD"
[[ "$MERGE_BASE" == "$MAIN_SHA" ]] || fail "Feature basiert nicht exakt auf main"

step 2 "Feature-Diff auf exakt sieben erwartete Dateien begrenzen"
mapfile -t CHANGED < <(git -C "$REPO" diff --name-only "$MAIN_SHA..$FEATURE_HEAD" | sort)
mapfile -t EXPECTED_SORTED < <(printf '%s\n' "${EXPECTED_FILES[@]}" | sort)
printf 'Scope:\n'; printf '  %s\n' "${CHANGED[@]}"
[[ ${#CHANGED[@]} -eq ${#EXPECTED_SORTED[@]} ]] || fail "Erwartet ${#EXPECTED_SORTED[@]} Dateien, gefunden ${#CHANGED[@]}"
[[ "$(printf '%s\n' "${CHANGED[@]}")" == "$(printf '%s\n' "${EXPECTED_SORTED[@]}")" ]] || fail "Feature-Scope stimmt nicht"
git -C "$REPO" diff --check "$MAIN_SHA..$FEATURE_HEAD"
echo "Feature-Scope: OK"

step 3 "Isolierten Worktree auf dem Feature-Head anlegen"
mkdir -p "$(dirname "$WT")"
git -C "$REPO" worktree add --detach "$WT" "$FEATURE_HEAD"
[[ "$(git -C "$WT" rev-parse HEAD)" == "$FEATURE_HEAD" ]] || fail "Worktree steht nicht auf Feature-Head"
mkdir -p "$WT/storage/framework/cache/data" "$WT/storage/framework/sessions" "$WT/storage/framework/views" "$WT/storage/logs" "$WT/bootstrap/cache"
echo "Worktree: $WT"

step 4 "PHP-Laufzeit und isolierte Dependencies vorbereiten"
command -v php >/dev/null || fail "php fehlt"
PHP_VERSION="$(php -r 'echo PHP_VERSION;')"
echo "PHP: $PHP_VERSION"
php -r 'exit(version_compare(PHP_VERSION, "8.3.0", ">=") ? 0 : 1);' || fail "PHP >= 8.3 erforderlich"
php -m | grep -qi '^pdo_sqlite$' || fail "pdo_sqlite fehlt; Test wird bewusst NICHT gegen die Produktions-DB ausgefuehrt"
[[ -d "$REPO/vendor" && -f "$REPO/vendor/autoload.php" ]] || fail "Produktives vendor/ fehlt"
cp -a "$REPO/vendor" "$WT/vendor"
[[ -f "$WT/vendor/autoload.php" ]] || fail "Isoliertes vendor/autoload.php fehlt"
echo "Dependencies aus produktivem vendor/ in den Worktree kopiert."

step 5 "PHP-Syntax und Remote-Config-Marker pruefen"
for file in \
  app/Http/Controllers/Admin/AdminAppRemoteConfigController.php \
  app/Http/Controllers/Api/V1/AppRemoteConfigController.php \
  app/Http/Middleware/AuthenticateApiToken.php \
  app/Services/AppConfig/AppRemoteConfigService.php \
  tests/Feature/AppRemoteConfigAppearanceTest.php \
  tests/Feature/AppRemoteConfigPublicReadTest.php; do
  php -l "$WT/$file" >/dev/null || fail "PHP-Syntaxfehler: $file"
done
SERVICE="$WT/app/Services/AppConfig/AppRemoteConfigService.php"
ADMIN="$WT/app/Http/Controllers/Admin/AdminAppRemoteConfigController.php"
API="$WT/app/Http/Controllers/Api/V1/AppRemoteConfigController.php"
MIDDLEWARE="$WT/app/Http/Middleware/AuthenticateApiToken.php"
VIEW="$WT/resources/views/admin/app-remote-config/index.blade.php"
grep -Fq "'appearance' => [" "$SERVICE" || fail "appearance Defaults fehlen"
grep -Fq "'auth_background' => [" "$SERVICE" || fail "auth_background fehlt"
grep -Fq "'feed_background' => [" "$SERVICE" || fail "feed_background fehlt"
grep -Fq 'withAppearanceRevisions' "$SERVICE" || fail "Versionslogik fehlt"
grep -Fq 'invalidateActiveConfigCache' "$SERVICE" || fail "Cache-Invalidierung fehlt"
grep -Fq "'auth_background_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']" "$ADMIN" || fail "Auth-Upload-Regel fehlt"
grep -Fq "'feed_background_file' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']" "$ADMIN" || fail "Feed-Upload-Regel fehlt"
grep -Fq "storeAs('app-backgrounds'" "$ADMIN" || fail "app-backgrounds Storage fehlt"
grep -Fq "Storage::disk('public')->url" "$ADMIN" || fail "Public-URL-Erzeugung fehlt"
grep -Fq "request->is('api/v1/app/remote-config')" "$MIDDLEWARE" || fail "Guest-Read-Ausnahme fehlt"
grep -Fq "? \$remoteConfig->visibleFeedCards" "$API" || fail "Authenticated Feed-Cards fehlen"
grep -Fq ": []" "$API" || fail "Guest Feed-Cards Fallback fehlt"
grep -Fq 'Remote App Hintergründe' "$VIEW" || fail "Admin-UI fehlt"
echo "Syntax/Marker: OK"

step 6 "Laravel mit strikt isolierter SQLite-Testumgebung booten"
touch "$DB_FILE"
export APP_ENV=testing
export APP_DEBUG=false
export APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='
export APP_URL='https://hnt.rocks'
export DB_CONNECTION=sqlite
export DB_DATABASE="$DB_FILE"
export DB_FOREIGN_KEYS=false
export CACHE_STORE=array
export SESSION_DRIVER=array
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array
export LOG_CHANNEL=stderr
export FILESYSTEM_DISK=local
cd "$WT"
php artisan about --only=environment >/dev/null || fail "Laravel konnte isoliert nicht booten"
php artisan route:list --path=api/v1/app/remote-config | grep -Fq 'api/v1/app/remote-config' || fail "Bestehende Remote-Config-Route fehlt"
echo "Laravel Test-Boot + Route: OK"

step 7 "Remote-Config-Vertrag, Versionierung und URL-Sicherheit ausfuehren"
SMOKE="$WT/.patch400_remote_background_smoke.php"
cat > "$SMOKE" <<'PHP'
<?php

use App\Http\Controllers\Api\V1\AppRemoteConfigController;
use App\Http\Middleware\AuthenticateApiToken;
use App\Models\AppRemoteConfig;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function expectTrue(bool $value, string $message): void
{
    if (! $value) {
        fwrite(STDERR, "SMOKE FAIL: {$message}\n");
        exit(2);
    }
}

Schema::dropIfExists('app_remote_configs');
Schema::create('app_remote_configs', function (Blueprint $table): void {
    $table->id();
    $table->string('key')->unique();
    $table->boolean('is_active')->default(true);
    $table->json('config_json')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->unsignedBigInteger('created_by')->nullable();
    $table->unsignedBigInteger('updated_by')->nullable();
    $table->timestamps();
});

/** @var AppRemoteConfigService $service */
$service = app(AppRemoteConfigService::class);
$defaults = $service->defaults();
expectTrue($defaults['appearance']['auth_background']['url'] === null, 'Auth URL Default muss null sein');
expectTrue($defaults['appearance']['auth_background']['version'] === 0, 'Auth Version Default muss 0 sein');
expectTrue($defaults['appearance']['feed_background']['url'] === null, 'Feed URL Default muss null sein');
expectTrue($defaults['appearance']['feed_background']['version'] === 0, 'Feed Version Default muss 0 sein');
expectTrue($defaults['features']['feed_remote_cards_enabled'] === true, 'Bestehende Feature-Flags muessen erhalten bleiben');
expectTrue($defaults['android']['min_version_code'] === 8, 'Bestehende Android Config muss erhalten bleiben');

$authOnly = $service->normalizeConfig([
    'appearance' => [
        'auth_background' => ['url' => '/storage/app-backgrounds/auth.webp', 'version' => 4],
    ],
]);
expectTrue($authOnly['appearance']['auth_background']['url'] === '/storage/app-backgrounds/auth.webp', 'Auth URL muss erhalten bleiben');
expectTrue($authOnly['appearance']['auth_background']['version'] === 4, 'Auth Version muss erhalten bleiben');
expectTrue($authOnly['appearance']['feed_background']['url'] === null, 'Feed bleibt optional');

$feedOnly = $service->normalizeConfig([
    'appearance' => [
        'feed_background' => ['url' => '/storage/app-backgrounds/feed.png', 'version' => 7],
    ],
]);
expectTrue($feedOnly['appearance']['auth_background']['url'] === null, 'Auth bleibt optional');
expectTrue($feedOnly['appearance']['feed_background']['url'] === '/storage/app-backgrounds/feed.png', 'Feed URL muss erhalten bleiben');
expectTrue($feedOnly['appearance']['feed_background']['version'] === 7, 'Feed Version muss erhalten bleiben');

$both = $service->normalizeConfig([
    'features' => ['messages_enabled' => false],
    'appearance' => [
        'auth_background' => ['url' => '/storage/app-backgrounds/auth.jpg', 'version' => 2],
        'feed_background' => ['url' => 'https://hnt.rocks/storage/app-backgrounds/feed.webp', 'version' => 3],
    ],
]);
expectTrue($both['appearance']['auth_background']['url'] === '/storage/app-backgrounds/auth.jpg', 'Auth in Beide-Konfiguration');
expectTrue($both['appearance']['feed_background']['url'] === 'https://hnt.rocks/storage/app-backgrounds/feed.webp', 'HTTPS hnt.rocks Feed URL erlaubt');
expectTrue($both['features']['messages_enabled'] === false, 'Bestehender Wert darf nicht ueberschrieben werden');
expectTrue($both['features']['feed_remote_cards_enabled'] === true, 'Fehlender bestehender Key wird weiter mit Default ergaenzt');

$unsafe = $service->normalizeConfig([
    'appearance' => [
        'auth_background' => ['url' => 'https://example.com/storage/app-backgrounds/auth.webp', 'version' => 9],
        'feed_background' => ['url' => 'http://hnt.rocks/storage/app-backgrounds/feed.webp', 'version' => 10],
    ],
]);
expectTrue($unsafe['appearance']['auth_background']['url'] === null, 'Externer Host muss abgelehnt werden');
expectTrue($unsafe['appearance']['feed_background']['url'] === null, 'HTTP muss abgelehnt werden');
expectTrue($unsafe['appearance']['auth_background']['version'] === 9, 'Version bleibt auch bei normalisiertem Fallback eindeutig');

$next = $defaults;
$next['appearance']['auth_background']['url'] = '/storage/app-backgrounds/auth-v1.webp';
$revision1 = $service->withAppearanceRevisions($defaults, $next);
expectTrue($revision1['appearance']['auth_background']['version'] === 1, 'Erster Auth-Wechsel muss Version 1 erzeugen');
expectTrue($revision1['appearance']['feed_background']['version'] === 0, 'Feed-Version darf dabei nicht steigen');

$next2 = $revision1;
$next2['appearance']['feed_background']['url'] = '/storage/app-backgrounds/feed-v1.webp';
$revision2 = $service->withAppearanceRevisions($revision1, $next2);
expectTrue($revision2['appearance']['auth_background']['version'] === 1, 'Unveraenderter Auth-Hintergrund behaelt Version');
expectTrue($revision2['appearance']['feed_background']['version'] === 1, 'Erster Feed-Wechsel muss Version 1 erzeugen');

$next3 = $revision2;
$next3['appearance']['auth_background']['url'] = null;
$revision3 = $service->withAppearanceRevisions($revision2, $next3);
expectTrue($revision3['appearance']['auth_background']['version'] === 2, 'Entfernen des Auth-Hintergrunds muss Version erhoehen');

Cache::flush();
$service->invalidateActiveConfigCache();
AppRemoteConfig::query()->create([
    'key' => AppRemoteConfigService::DEFAULT_KEY,
    'is_active' => true,
    'config_json' => $revision2,
    'published_at' => now(),
]);
$cachedBefore = $service->activeConfig();
expectTrue($cachedBefore['appearance']['auth_background']['url'] === '/storage/app-backgrounds/auth-v1.webp', 'Cache Prime fehlgeschlagen');

$changed = $revision2;
$changed['appearance']['auth_background']['url'] = '/storage/app-backgrounds/auth-v2.webp';
$changed['appearance']['auth_background']['version'] = 2;
AppRemoteConfig::query()->where('key', AppRemoteConfigService::DEFAULT_KEY)->update(['config_json' => json_encode($changed)]);
$stillCached = $service->activeConfig();
expectTrue($stillCached['appearance']['auth_background']['url'] === '/storage/app-backgrounds/auth-v1.webp', 'Cache muss vor Invalidierung alten Stand liefern');
$service->invalidateActiveConfigCache();
$afterInvalidation = $service->activeConfig();
expectTrue($afterInvalidation['appearance']['auth_background']['url'] === '/storage/app-backgrounds/auth-v2.webp', 'Cache-Invalidierung muss neuen Stand sofort liefern');
expectTrue($afterInvalidation['appearance']['auth_background']['version'] === 2, 'Neue Version muss nach Invalidierung sichtbar sein');

$request = Request::create('/api/v1/app/remote-config', 'GET');
$response = app(AppRemoteConfigController::class)->show($request, $service);
$payload = $response->getData(true);
expectTrue($response->getStatusCode() === 200, 'Guest Controller Response muss 200 sein');
expectTrue($payload['feed_cards'] === [], 'Guest Remote Config darf keine user-spezifischen Feed Cards liefern');
expectTrue($payload['config']['appearance']['auth_background']['url'] === '/storage/app-backgrounds/auth-v2.webp', 'Guest muss Appearance Config erhalten');

$middleware = app(AuthenticateApiToken::class);
$guestGet = Request::create('/api/v1/app/remote-config', 'GET');
$guestGetResponse = $middleware->handle($guestGet, fn () => response('allowed', 200));
expectTrue($guestGetResponse->getStatusCode() === 200, 'GET Remote Config muss vor Login erlaubt sein');
$guestDismiss = Request::create('/api/v1/app/remote-feed-cards/example/dismiss', 'POST');
$guestDismissResponse = $middleware->handle($guestDismiss, fn () => response('should-not-run', 200));
expectTrue($guestDismissResponse->getStatusCode() === 401, 'Dismiss muss ohne Token geschuetzt bleiben');

echo "Remote background service/controller/middleware smoke: OK\n";
PHP
php "$SMOKE"
rm -f "$SMOKE"

step 8 "Upload-Validierung mit echten Temporaerdateien pruefen"
VALID_PNG="$WT/.patch400-valid.png"
INVALID_TXT="$WT/.patch400-invalid.txt"
printf '%s' 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=' | base64 -d > "$VALID_PNG"
printf 'not an image' > "$INVALID_TXT"
VALIDATE="$WT/.patch400_upload_validation.php"
cat > "$VALIDATE" <<'PHP'
<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$rules = ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'];
$valid = new UploadedFile(__DIR__.'/.patch400-valid.png', 'auth.png', 'image/png', null, true);
$invalid = new UploadedFile(__DIR__.'/.patch400-invalid.txt', 'payload.txt', 'text/plain', null, true);

if (Validator::make(['file' => $valid], ['file' => $rules])->fails()) {
    fwrite(STDERR, "SMOKE FAIL: gueltiges PNG wurde abgelehnt\n");
    exit(2);
}
if (! Validator::make(['file' => $invalid], ['file' => $rules])->fails()) {
    fwrite(STDERR, "SMOKE FAIL: ungueltige Textdatei wurde akzeptiert\n");
    exit(2);
}

echo "Upload validation smoke: OK\n";
PHP
php "$VALIDATE"
rm -f "$VALIDATE" "$VALID_PNG" "$INVALID_TXT"

step 9 "Feature-Testquellen und Flutter-Kompatibilitaetsvertrag statisch pruefen"
grep -Fq 'test_remote_config_without_backgrounds_returns_optional_fallback_contract' "$WT/tests/Feature/AppRemoteConfigAppearanceTest.php" || fail "Fallback-Test fehlt"
grep -Fq 'test_admin_uploads_both_backgrounds_and_versions_increment' "$WT/tests/Feature/AppRemoteConfigAppearanceTest.php" || fail "Upload-/Versionstest fehlt"
grep -Fq 'test_admin_update_invalidates_cached_active_config_immediately' "$WT/tests/Feature/AppRemoteConfigAppearanceTest.php" || fail "Cache-Test fehlt"
grep -Fq 'test_existing_remote_config_endpoint_is_readable_before_login' "$WT/tests/Feature/AppRemoteConfigPublicReadTest.php" || fail "Guest-Read-Test fehlt"
grep -Fq 'test_remote_feed_card_dismiss_stays_protected_without_token' "$WT/tests/Feature/AppRemoteConfigPublicReadTest.php" || fail "Dismiss-Schutz-Test fehlt"
echo "Feature-Testquellen: vorhanden"
echo "Flutter-Vertrag: additive config.appearance.* Felder; bestehende Felder unveraendert."

step 10 "Abschluss zusammenfassen"
cat <<EOF
============================================================
PATCH 400 ZUSAMMENFASSUNG
============================================================
Ergebnis:                         ERFOLGREICH
Main Basis:                       $MAIN_SHA
Feature Branch:                   $FEATURE_BRANCH
Feature Head:                     $FEATURE_HEAD
Scope:                            7 Dateien
Bestehender Endpoint:             /api/v1/app/remote-config
Guest Read vor Login:             JA
Guest Feed Cards:                 []
Dismiss ohne Token:               401 / GESCHUETZT
Auth Background:                  URL + Version / optional
Feed Background:                  URL + Version / optional
Upload:                           JPG/JPEG/PNG/WebP / max 8 MB
Storage:                          public/app-backgrounds
URL Sicherheit:                   HNT/App-Host HTTPS oder /storage/app-backgrounds
Version bei URL-Wechsel:          JA
Version bei Clear:                JA
Cache:                            5 Minuten + Admin invalidiert sofort
Bestehende Config-Felder:         ERHALTEN
Produktions-DB benutzt:           NEIN
Migration ausgefuehrt:            NEIN
Main Push/Merge:                  NEIN
Deploy:                           NEIN
Flutter-Dateien:                  UNVERAENDERT
Log-Datei:                        $LOG
============================================================
EOF

echo
echo "ERFOLGREICH"
