#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NO="400a"
HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
MAIN_SHA="18d4febb5be55c9a5b2fc8b10233bf3d23ffb4ac"
FEATURE_BRANCH="agent/app-remote-backgrounds"
FEATURE_HEAD="3ea6b434e13eec0d4553d12b9e1b5efd7b5f1fc9"
STAMP="$(date +%Y%m%d-%H%M%S)"
WT="$HUB/.worktrees/${PATCH_NO}-remote-app-backgrounds-${STAMP}"
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
  set -e
}
trap cleanup EXIT

fail() {
  echo
  echo "FEHLER: $*" >&2
  exit 2
}

step() {
  printf '\n[%s/9] %s ...\n' "$1" "$2"
}

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 400a - DB-FREIER TEST REMOTE APP BACKGROUNDS
============================================================
- Ersatz fuer Patch 400 auf Hosts ohne pdo_sqlite
- testet NUR agent/app-remote-backgrounds
- nutzt KEINE Datenbankverbindung
- prueft API-Vertrag, Guest-Read, geschuetzte API, URL-Sicherheit
- prueft Versionslogik, Cache-Invalidierung und Upload-Storage runtime
- prueft Admin-Uploadregeln statisch + Validator runtime
- kompiliert die Admin-Blade im isolierten Worktree
- fuehrt die DB-basierten Feature-Tests NICHT aus
- KEINE PRODUKTIONS-DB / KEINE MIGRATION / KEIN DEPLOY
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
[[ -d "$REPO/vendor" && -f "$REPO/vendor/autoload.php" ]] || fail "Produktives vendor/ fehlt"
cp -a "$REPO/vendor" "$WT/vendor"
[[ -f "$WT/vendor/autoload.php" ]] || fail "Isoliertes vendor/autoload.php fehlt"
echo "pdo_sqlite wird fuer 400a bewusst NICHT benoetigt."

step 5 "PHP-Syntax, Route und Feature-Marker pruefen"
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
grep -Fq "request->is('api/v1/app/remote-config')" "$MIDDLEWARE" || fail "Guest-Read-Ausnahme fehlt"
grep -Fq "? \$remoteConfig->visibleFeedCards" "$API" || fail "Authenticated Feed-Cards fehlen"
grep -Fq 'Remote App Hintergründe' "$VIEW" || fail "Admin-UI fehlt"
echo "Syntax/Marker: OK"

step 6 "Laravel ohne Datenbankzugriff booten und Admin-Blade kompilieren"
export APP_ENV=testing
export APP_DEBUG=false
export APP_KEY='base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA='
export APP_URL='https://hnt.rocks'
export CACHE_STORE=array
export SESSION_DRIVER=array
export QUEUE_CONNECTION=sync
export MAIL_MAILER=array
export LOG_CHANNEL=stderr
export FILESYSTEM_DISK=local
cd "$WT"
php artisan about --only=environment >/dev/null || fail "Laravel konnte nicht booten"
php artisan route:list --path=api/v1/app/remote-config | grep -Fq 'api/v1/app/remote-config' || fail "Bestehende Remote-Config-Route fehlt"
php artisan view:cache >/dev/null || fail "Blade-Kompilierung fehlgeschlagen"
php artisan view:clear >/dev/null || true
echo "Laravel Boot + Route + Blade: OK"

step 7 "DB-freien Runtime-Smoke fuer Vertrag, Guest-Read, Versionen und Cache ausfuehren"
SMOKE="$WT/.patch400a_remote_background_smoke.php"
cat > "$SMOKE" <<'PHP'
<?php

use App\Http\Controllers\Admin\AdminAppRemoteConfigController;
use App\Http\Controllers\Api\V1\AppRemoteConfigController;
use App\Http\Middleware\AuthenticateApiToken;
use App\Models\User;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

/** @var AppRemoteConfigService $service */
$service = app(AppRemoteConfigService::class);
$defaults = $service->defaults();
expectTrue($defaults['appearance']['auth_background']['url'] === null, 'Auth URL Default muss null sein');
expectTrue($defaults['appearance']['auth_background']['version'] === 0, 'Auth Version Default muss 0 sein');
expectTrue($defaults['appearance']['feed_background']['url'] === null, 'Feed URL Default muss null sein');
expectTrue($defaults['appearance']['feed_background']['version'] === 0, 'Feed Version Default muss 0 sein');
expectTrue($defaults['features']['feed_remote_cards_enabled'] === true, 'Bestehende Features muessen erhalten bleiben');
expectTrue($defaults['android']['min_version_code'] === 8, 'Bestehende Android Config muss erhalten bleiben');

$both = $service->normalizeConfig([
    'features' => ['messages_enabled' => false],
    'appearance' => [
        'auth_background' => ['url' => '/storage/app-backgrounds/auth.jpg', 'version' => 2],
        'feed_background' => ['url' => 'https://hnt.rocks/storage/app-backgrounds/feed.webp', 'version' => 3],
    ],
]);
expectTrue($both['appearance']['auth_background']['url'] === '/storage/app-backgrounds/auth.jpg', 'Auth URL fehlt');
expectTrue($both['appearance']['auth_background']['version'] === 2, 'Auth Version fehlt');
expectTrue($both['appearance']['feed_background']['url'] === 'https://hnt.rocks/storage/app-backgrounds/feed.webp', 'Feed URL fehlt');
expectTrue($both['appearance']['feed_background']['version'] === 3, 'Feed Version fehlt');
expectTrue($both['features']['messages_enabled'] === false, 'Bestehender Feature-Wert muss erhalten bleiben');
expectTrue($both['features']['feed_remote_cards_enabled'] === true, 'Fehlende Alt-Felder muessen Default behalten');

$unsafe = $service->normalizeConfig([
    'appearance' => [
        'auth_background' => ['url' => 'https://example.com/storage/app-backgrounds/auth.webp', 'version' => 9],
        'feed_background' => ['url' => 'http://hnt.rocks/storage/app-backgrounds/feed.webp', 'version' => 10],
    ],
]);
expectTrue($unsafe['appearance']['auth_background']['url'] === null, 'Externer Host muss abgelehnt werden');
expectTrue($unsafe['appearance']['feed_background']['url'] === null, 'HTTP muss abgelehnt werden');
expectTrue($unsafe['appearance']['auth_background']['version'] === 9, 'Version muss stabil bleiben');

$next = $defaults;
$next['appearance']['auth_background']['url'] = '/storage/app-backgrounds/auth-v1.webp';
$rev1 = $service->withAppearanceRevisions($defaults, $next);
expectTrue($rev1['appearance']['auth_background']['version'] === 1, 'Auth Revision 1 erwartet');
expectTrue($rev1['appearance']['feed_background']['version'] === 0, 'Feed Revision darf nicht steigen');

$next2 = $rev1;
$next2['appearance']['feed_background']['url'] = '/storage/app-backgrounds/feed-v1.webp';
$rev2 = $service->withAppearanceRevisions($rev1, $next2);
expectTrue($rev2['appearance']['auth_background']['version'] === 1, 'Auth Revision muss stabil bleiben');
expectTrue($rev2['appearance']['feed_background']['version'] === 1, 'Feed Revision 1 erwartet');

$next3 = $rev2;
$next3['appearance']['auth_background']['url'] = null;
$rev3 = $service->withAppearanceRevisions($rev2, $next3);
expectTrue($rev3['appearance']['auth_background']['version'] === 2, 'Auth Clear muss Revision erhoehen');

Cache::put('app_remote_config:active:default', ['sentinel' => true], 300);
expectTrue(Cache::has('app_remote_config:active:default'), 'Cache-Sentinel konnte nicht gesetzt werden');
$service->invalidateActiveConfigCache();
expectTrue(! Cache::has('app_remote_config:active:default'), 'Cache muss sofort invalidiert werden');

$middleware = app(AuthenticateApiToken::class);
$guestRequest = Request::create('/api/v1/app/remote-config', 'GET');
$guestResponse = $middleware->handle($guestRequest, fn () => response()->json(['ok' => true]));
expectTrue($guestResponse->getStatusCode() === 200, 'Remote Config muss vor Login lesbar sein');

$protectedRequest = Request::create('/api/v1/me', 'GET');
$protectedResponse = $middleware->handle($protectedRequest, fn () => response()->json(['should_not_run' => true]));
expectTrue($protectedResponse->getStatusCode() === 401, 'Andere api.token Routen muessen ohne Token geschuetzt bleiben');

$fakeService = new class($service) extends AppRemoteConfigService {
    public function __construct(private AppRemoteConfigService $delegate) {}
    public function activeConfig(): array { return $this->delegate->defaults(); }
    public function visibleFeedCards(User $user, ?int $appVersionCode = null, string $locale = 'de'): Collection
    {
        throw new RuntimeException('visibleFeedCards darf fuer Guest Remote Config nicht aufgerufen werden');
    }
};
$apiRequest = Request::create('/api/v1/app/remote-config', 'GET', ['locale' => 'de']);
$apiRequest->setUserResolver(fn () => null);
$apiResponse = (new AppRemoteConfigController())->show($apiRequest, $fakeService);
$payload = $apiResponse->getData(true);
expectTrue($apiResponse->getStatusCode() === 200, 'Guest Controller Response muss 200 sein');
expectTrue(($payload['config']['appearance']['auth_background']['url'] ?? 'missing') === null, 'Guest API Auth Default fehlt');
expectTrue(($payload['config']['appearance']['feed_background']['url'] ?? 'missing') === null, 'Guest API Feed Default fehlt');
expectTrue(($payload['feed_cards'] ?? null) === [], 'Guest API darf keine user-spezifischen Feed Cards ausliefern');

$invalidUpload = UploadedFile::fake()->create('payload.txt', 12, 'text/plain');
$invalidValidator = Validator::make(
    ['file' => $invalidUpload],
    ['file' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']]
);
expectTrue($invalidValidator->fails(), 'Ungueltiger Upload muss abgelehnt werden');

$pngPath = tempnam(sys_get_temp_dir(), 'hnt-bg-');
file_put_contents($pngPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
$validUpload = new UploadedFile($pngPath, 'auth.png', 'image/png', null, true);
$validValidator = Validator::make(
    ['file' => $validUpload],
    ['file' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192']]
);
expectTrue(!$validValidator->fails(), 'Gueltiges PNG muss akzeptiert werden');

Storage::fake('public');
$storageRequest = Request::create('/admin/app-remote-config', 'POST', [], [], [
    'auth_background_file' => new UploadedFile($pngPath, 'auth.png', 'image/png', null, true),
]);
$adminController = new AdminAppRemoteConfigController();
$reflection = new ReflectionClass($adminController);
$method = $reflection->getMethod('uploadedBackgroundUrl');
$method->setAccessible(true);
$url = $method->invoke($adminController, $storageRequest, 'auth_background_file', 'auth-background');
expectTrue(is_string($url) && str_contains($url, '/storage/app-backgrounds/auth-background-'), 'Upload URL muss app-backgrounds verwenden');
$storedPath = preg_replace('#^.*?/storage/#', '', $url);
expectTrue(is_string($storedPath) && Storage::disk('public')->exists($storedPath), 'Upload-Datei muss im public Storage existieren');
@unlink($pngPath);

echo "DB-freier Runtime-Smoke: OK\n";
PHP
php "$SMOKE" || fail "DB-freier Runtime-Smoke fehlgeschlagen"
rm -f "$SMOKE"

step 8 "DB-basierte Feature-Tests sauber als nicht ausfuehrbar markieren"
if php -m | grep -qi '^pdo_sqlite$'; then
  echo "pdo_sqlite ist inzwischen vorhanden; Patch 400 kann optional zusaetzlich ausgefuehrt werden."
else
  echo "pdo_sqlite: NICHT installiert"
  echo "DB-basierte Feature-Tests wurden deshalb bewusst NICHT ausgefuehrt."
  echo "Stattdessen wurden Vertrag, Middleware, Controller-Guest-Pfad, Validator, Storage, Cache und Versionierung runtime ohne DB geprueft."
fi

step 9 "main unveraendert bestaetigen und Abschluss ausgeben"
git -C "$REPO" fetch origin main >/dev/null
MAIN_AFTER="$(git -C "$REPO" rev-parse origin/main)"
[[ "$MAIN_AFTER" == "$MAIN_SHA" ]] || fail "origin/main hat sich waehrend des Tests geaendert: $MAIN_AFTER"
cat <<SUMMARY
============================================================
PATCH 400a ZUSAMMENFASSUNG
============================================================
Ergebnis:                 ERFOLGREICH
React/Laravel main:       $MAIN_AFTER / UNVERAENDERT
Feature-Branch:           $FEATURE_BRANCH
Feature-Head:             $FEATURE_HEAD
DB-Zugriff:               NEIN
Produktions-DB:           UNBERUEHRT
Migration:                NEIN
Deploy:                   NEIN
Guest Remote Config:      OK
Andere Token-Routen:      401 OHNE TOKEN
Auth Background Contract: OK
Feed Background Contract: OK
Versionierung:            OK
URL-Sicherheit:           OK
Cache-Invalidierung:      OK
Upload-Validierung:       OK
Upload-Storage:           OK
Admin-Blade:              OK
DB-Feature-Tests:         NICHT AUSGEFUEHRT (pdo_sqlite fehlt)
Log-Datei:                $LOG
============================================================
SUMMARY

echo
echo "ERFOLGREICH"
