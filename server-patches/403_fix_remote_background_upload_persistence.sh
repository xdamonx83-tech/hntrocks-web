#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NO="403"
HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
MAIN_SHA="18d4febb5be55c9a5b2fc8b10233bf3d23ffb4ac"
PREVIOUS_HEAD="3ea6b434e13eec0d4553d12b9e1b5efd7b5f1fc9"
FEATURE_BRANCH="agent/app-remote-backgrounds"
FEATURE_HEAD="e65d8af922542a515fbf0937ec9d7743a7252db5"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="$REPO/storage/app/deploy-backups/remote-app-background-upload-fix/$STAMP"
LOG="$HUB/${PATCH_NO}_fix_remote_background_upload_${STAMP}.log"
SMOKE_PHP="$(mktemp --suffix=.php)"
UPLOAD_DIR="$REPO/storage/app/public/app-backgrounds"
PUBLIC_ROOT="$REPO/storage/app/public"
DEPLOY_STARTED=0
DEPLOY_OK=0

DELTA_FILES=(
  "app/Http/Controllers/Admin/AdminAppRemoteConfigController.php"
  "resources/views/admin/app-remote-config/index.blade.php"
  "tests/Feature/AppRemoteConfigAppearanceTest.php"
)

RUNTIME_FILES=(
  "app/Http/Controllers/Admin/AdminAppRemoteConfigController.php"
  "resources/views/admin/app-remote-config/index.blade.php"
)

mkdir -p "$(dirname "$LOG")"
exec > >(tee -a "$LOG") 2>&1

fail() {
  echo
  echo "FEHLER: $*" >&2
  exit 2
}

step() {
  printf '\n[%s/10] %s ...\n' "$1" "$2"
}

OLD_UPLOAD_EXISTS=0
OLD_UPLOAD_UID=""
OLD_UPLOAD_GID=""
OLD_UPLOAD_MODE=""
if [[ -d "$UPLOAD_DIR" ]]; then
  OLD_UPLOAD_EXISTS=1
  OLD_UPLOAD_UID="$(stat -c '%u' "$UPLOAD_DIR")"
  OLD_UPLOAD_GID="$(stat -c '%g' "$UPLOAD_DIR")"
  OLD_UPLOAD_MODE="$(stat -c '%a' "$UPLOAD_DIR")"
fi

clear_runtime_caches() {
  set +e
  (
    cd "$REPO" || exit 0
    php artisan view:clear >/dev/null 2>&1 || true
    php artisan cache:forget app_remote_config:active:default >/dev/null 2>&1 || true
  )
  set -e
}

rollback() {
  set +e
  echo
  echo "ROLLBACK: stelle Runtime-Dateien und vorherige Storage-Rechte wieder her ..."
  for file in "${RUNTIME_FILES[@]}"; do
    if [[ -f "$BACKUP/$file" ]]; then
      mkdir -p "$(dirname "$REPO/$file")"
      cp -a "$BACKUP/$file" "$REPO/$file"
    fi
  done
  if [[ $OLD_UPLOAD_EXISTS -eq 1 && -d "$UPLOAD_DIR" ]]; then
    chown "$OLD_UPLOAD_UID:$OLD_UPLOAD_GID" "$UPLOAD_DIR" >/dev/null 2>&1 || true
    chmod "$OLD_UPLOAD_MODE" "$UPLOAD_DIR" >/dev/null 2>&1 || true
  fi
  clear_runtime_caches
  echo "ROLLBACK: abgeschlossen. Backup bleibt unter $BACKUP."
  set -e
}

on_exit() {
  status=$?
  rm -f "$SMOKE_PHP" >/dev/null 2>&1 || true
  if [[ $DEPLOY_STARTED -eq 1 && $DEPLOY_OK -ne 1 ]]; then
    rollback
  fi
  exit "$status"
}
trap on_exit EXIT

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 403 - REMOTE BACKGROUND UPLOAD PERSISTENCE FIX
============================================================
- behebt den Admin-Fall: Erfolgsmeldung, aber appearance.*_background bleibt null/version 0
- prueft und repariert die Schreibrechte von storage/app/public/app-backgrounds
- Hintergrund-Store-Fehler werden nicht mehr still als Erfolg behandelt
- gespeicherte Background-URLs werden kanonisch als /storage/app-backgrounds/... persistiert
- stale config_json mit url=null kann einen erfolgreichen Upload nicht mehr zuruecksetzen
- Admin-Form markiert den Appearance-Bereich explizit als Bestandteil des Save-Vorgangs
- Live-Runtime-Smoke testet Upload -> API -> Ersetzen -> API -> Entfernen in EINER DB-Transaktion
- die Produktions-DB wird beim Smoke am Ende immer zurueckgerollt
- Testdateien werden nach dem Smoke wieder geloescht
- keine Migration / keine neue Tabelle / kein Flutter
- main wird NICHT veraendert / KEIN MERGE / KEIN MAIN-PUSH
============================================================
HEAD

step 1 "Git-Basis, Feature-Head und main exakt verifizieren"
[[ -d "$REPO/.git" || -f "$REPO/.git" ]] || fail "$REPO ist kein Git-Checkout"
git -C "$REPO" fetch origin main "$FEATURE_BRANCH" --prune
MAIN_NOW="$(git -C "$REPO" rev-parse origin/main)"
FEATURE_NOW="$(git -C "$REPO" rev-parse "origin/$FEATURE_BRANCH")"
MERGE_BASE="$(git -C "$REPO" merge-base origin/main "origin/$FEATURE_BRANCH")"
echo "origin/main:   $MAIN_NOW"
echo "Feature-Head:  $FEATURE_NOW"
echo "Merge-Base:    $MERGE_BASE"
[[ "$MAIN_NOW" == "$MAIN_SHA" ]] || fail "origin/main ist $MAIN_NOW, erwartet $MAIN_SHA"
[[ "$FEATURE_NOW" == "$FEATURE_HEAD" ]] || fail "Feature-Head ist $FEATURE_NOW, erwartet $FEATURE_HEAD"
[[ "$MERGE_BASE" == "$MAIN_SHA" ]] || fail "Feature basiert nicht exakt auf main"

step 2 "403-Delta exakt auf die drei erwarteten Dateien begrenzen"
mapfile -t CHANGED < <(git -C "$REPO" diff --name-only "$PREVIOUS_HEAD..$FEATURE_HEAD" | sort)
mapfile -t EXPECTED < <(printf '%s\n' "${DELTA_FILES[@]}" | sort)
printf '403 Delta:\n'; printf '  %s\n' "${CHANGED[@]}"
[[ ${#CHANGED[@]} -eq 3 ]] || fail "Erwartet 3 Delta-Dateien, gefunden ${#CHANGED[@]}"
[[ "$(printf '%s\n' "${CHANGED[@]}")" == "$(printf '%s\n' "${EXPECTED[@]}")" ]] || fail "403 Delta-Scope stimmt nicht"
git -C "$REPO" diff --check "$PREVIOUS_HEAD..$FEATURE_HEAD"
echo "403 Delta-Scope: OK"

step 3 "Live-Runtime als exakt den zuvor deployten 400c-Stand verifizieren"
for file in "${RUNTIME_FILES[@]}"; do
  [[ -f "$REPO/$file" ]] || fail "Live-Datei fehlt: $file"
  expected_sha="$(git -C "$REPO" show "$PREVIOUS_HEAD:$file" | sha256sum | awk '{print $1}')"
  live_sha="$(sha256sum "$REPO/$file" | awk '{print $1}')"
  echo "$file: $live_sha"
  [[ "$live_sha" == "$expected_sha" ]] || fail "Live-Datei entspricht nicht dem erwarteten 400c-Stand: $file"
done
echo "Live-Runtime vor 403: OK"

step 4 "Storage-Ursache pruefen und app-backgrounds Schreibrechte reparieren"
[[ -d "$PUBLIC_ROOT" ]] || fail "Public Storage Root fehlt: $PUBLIC_ROOT"
PARENT_UID="$(stat -c '%u' "$PUBLIC_ROOT")"
PARENT_GID="$(stat -c '%g' "$PUBLIC_ROOT")"
PARENT_MODE="$(stat -c '%a' "$PUBLIC_ROOT")"
APP_USER="$(getent passwd "$PARENT_UID" | cut -d: -f1 || true)"
echo "Public Storage: uid=$PARENT_UID gid=$PARENT_GID mode=$PARENT_MODE user=${APP_USER:-unbekannt}"
if [[ $OLD_UPLOAD_EXISTS -eq 1 ]]; then
  echo "app-backgrounds VORHER: uid=$OLD_UPLOAD_UID gid=$OLD_UPLOAD_GID mode=$OLD_UPLOAD_MODE"
else
  echo "app-backgrounds VORHER: fehlte"
fi
mkdir -p "$UPLOAD_DIR"
chown "$PARENT_UID:$PARENT_GID" "$UPLOAD_DIR"
chmod "$PARENT_MODE" "$UPLOAD_DIR"
NEW_UPLOAD_UID="$(stat -c '%u' "$UPLOAD_DIR")"
NEW_UPLOAD_GID="$(stat -c '%g' "$UPLOAD_DIR")"
NEW_UPLOAD_MODE="$(stat -c '%a' "$UPLOAD_DIR")"
echo "app-backgrounds NACHHER: uid=$NEW_UPLOAD_UID gid=$NEW_UPLOAD_GID mode=$NEW_UPLOAD_MODE"
[[ "$NEW_UPLOAD_UID" == "$PARENT_UID" && "$NEW_UPLOAD_GID" == "$PARENT_GID" ]] || fail "Storage-Owner konnte nicht angeglichen werden"

if [[ -n "$APP_USER" && "$PARENT_UID" != "0" && "$(id -u)" == "0" ]]; then
  runuser -u "$APP_USER" -- test -w "$UPLOAD_DIR" || fail "app-backgrounds ist fuer Storage-Owner $APP_USER nicht schreibbar"
  echo "Schreibtest als $APP_USER: OK"
else
  [[ -w "$UPLOAD_DIR" ]] || fail "app-backgrounds ist nicht schreibbar"
  echo "Schreibtest als aktueller User: OK"
fi

if [[ -L "$REPO/public/storage" ]]; then
  STORAGE_TARGET="$(readlink -f "$REPO/public/storage")"
  [[ "$STORAGE_TARGET" == "$PUBLIC_ROOT" ]] || fail "public/storage zeigt auf $STORAGE_TARGET statt $PUBLIC_ROOT"
elif [[ ! -e "$REPO/public/storage" ]]; then
  (cd "$REPO" && php artisan storage:link >/dev/null) || fail "public/storage Symlink konnte nicht erstellt werden"
else
  fail "public/storage existiert, ist aber kein Symlink"
fi
echo "public/storage Symlink: OK"

step 5 "Vollbackup der zwei geaenderten Live-Runtime-Dateien anlegen"
mkdir -p "$BACKUP"
for file in "${RUNTIME_FILES[@]}"; do
  mkdir -p "$BACKUP/$(dirname "$file")"
  cp -a "$REPO/$file" "$BACKUP/$file"
done
cat > "$BACKUP/storage-before.txt" <<EOF
exists=$OLD_UPLOAD_EXISTS
uid=$OLD_UPLOAD_UID
gid=$OLD_UPLOAD_GID
mode=$OLD_UPLOAD_MODE
EOF
printf '%s\n' "$PREVIOUS_HEAD" > "$BACKUP/previous-feature-head.txt"
printf '%s\n' "$FEATURE_HEAD" > "$BACKUP/feature-head.txt"
echo "Backup: $BACKUP"

step 6 "Feature-Syntax und die exakten Upload-Fix-Marker pruefen"
TMP_CONTROLLER="$(mktemp --suffix=.php)"
git -C "$REPO" show "$FEATURE_HEAD:app/Http/Controllers/Admin/AdminAppRemoteConfigController.php" > "$TMP_CONTROLLER"
php -l "$TMP_CONTROLLER" >/dev/null || { rm -f "$TMP_CONTROLLER"; fail "PHP-Syntaxfehler im Admin Controller"; }
rm -f "$TMP_CONTROLLER"

CONTROLLER="$(git -C "$REPO" show "$FEATURE_HEAD:app/Http/Controllers/Admin/AdminAppRemoteConfigController.php")"
VIEW="$(git -C "$REPO" show "$FEATURE_HEAD:resources/views/admin/app-remote-config/index.blade.php")"
TEST="$(git -C "$REPO" show "$FEATURE_HEAD:tests/Feature/AppRemoteConfigAppearanceTest.php")"
grep -Fq "storeAs('app-backgrounds'" <<<"$CONTROLLER" || fail "app-backgrounds Store fehlt"
grep -Fq "throw ValidationException::withMessages" <<<"$CONTROLLER" || fail "Store-Fehler wird noch still verschluckt"
grep -Fq "return '/storage/'.ltrim(\$path, '/');" <<<"$CONTROLLER" || fail "kanonische /storage URL fehlt"
grep -Fq 'enctype="multipart/form-data"' <<<"$VIEW" || fail "multipart/form-data fehlt"
grep -Fq 'name="feed_background_file"' <<<"$VIEW" || fail "Feed-File-Input fehlt"
grep -Fq 'name="remote_appearance_form" value="1"' <<<"$VIEW" || fail "Appearance-Form-Marker fehlt"
grep -Fq 'test_admin_feed_upload_wins_over_stale_null_json_and_uses_canonical_storage_url' <<<"$TEST" || fail "Stale-JSON Regressionstest fehlt"
grep -Fq 'test_admin_feed_upload_replace_and_clear_increments_version_each_time' <<<"$TEST" || fail "Upload/Replace/Clear Regressionstest fehlt"
echo "Feature-Marker/Syntax: OK"

step 7 "Controller und Admin-View atomar live deployen"
DEPLOY_STARTED=1
for file in "${RUNTIME_FILES[@]}"; do
  dest="$REPO/$file"
  tmp="$dest.${PATCH_NO}.tmp"
  git -C "$REPO" show "$FEATURE_HEAD:$file" > "$tmp"
  chown --reference="$dest" "$tmp"
  chmod --reference="$dest" "$tmp"
  mv -f "$tmp" "$dest"
  expected_sha="$(git -C "$REPO" show "$FEATURE_HEAD:$file" | sha256sum | awk '{print $1}')"
  live_sha="$(sha256sum "$dest" | awk '{print $1}')"
  [[ "$live_sha" == "$expected_sha" ]] || fail "Hash-Mismatch nach Deploy: $file"
  echo "LIVE: $file"
done

(
  cd "$REPO"
  php artisan view:clear >/dev/null
  php artisan view:cache >/dev/null
  php artisan cache:forget app_remote_config:active:default >/dev/null
) || fail "Blade/Remote-Config-Cache konnte nicht aktualisiert werden"
php -l "$REPO/app/Http/Controllers/Admin/AdminAppRemoteConfigController.php" >/dev/null || fail "Live Controller PHP-Syntax fehlerhaft"
echo "Live Controller + Blade + Cache: OK"

step 8 "Live Upload -> API -> Ersetzen -> API -> Entfernen transaktional verifizieren"
cat > "$SMOKE_PHP" <<'PHP'
<?php

use App\Http\Controllers\Admin\AdminAppRemoteConfigController;
use App\Http\Controllers\Api\V1\AppRemoteConfigController;
use App\Models\AppRemoteConfig;
use App\Models\User;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

$repo = $argv[1];
require $repo.'/vendor/autoload.php';
$app = require $repo.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

config([
    'cache.default' => 'array',
    'session.driver' => 'array',
]);

function smokeFail(string $message): never
{
    throw new RuntimeException($message);
}

function tinyPng(): string
{
    $path = tempnam(sys_get_temp_dir(), 'hnt403-bg-');
    file_put_contents($path, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='));
    return $path;
}

/** @var User|null $admin */
$admin = User::query()->where('is_admin', true)->first();
if (! $admin) {
    smokeFail('Kein Admin-User fuer den transaktionalen Runtime-Smoke gefunden.');
}

/** @var AppRemoteConfigService $service */
$service = app(AppRemoteConfigService::class);
/** @var AdminAppRemoteConfigController $adminController */
$adminController = app(AdminAppRemoteConfigController::class);
/** @var AppRemoteConfigController $apiController */
$apiController = app(AppRemoteConfigController::class);

$createdFiles = [];
$tempFiles = [];

$physicalFromUrl = static function (?string $url) use ($repo): ?string {
    if (! is_string($url) || ! str_starts_with($url, '/storage/')) {
        return null;
    }
    return $repo.'/storage/app/public/'.substr($url, strlen('/storage/'));
};

$save = static function (array $config, ?string $pngPath, bool $clear) use ($admin, $adminController, $service): AppRemoteConfig {
    $params = [
        'is_active' => '1',
        'remote_appearance_form' => '1',
        'config_json' => json_encode($config, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
    ];
    $files = [];

    if ($clear) {
        $params['feed_background_clear'] = '1';
    }

    if ($pngPath !== null) {
        $files['feed_background_file'] = new UploadedFile($pngPath, 'feed-test.png', 'image/png', UPLOAD_ERR_OK, true);
    }

    $request = Request::create('/admin/app-remote-config', 'POST', $params, [], $files);
    $request->setUserResolver(fn () => $admin);
    $adminController->update($request, $service);

    return AppRemoteConfig::query()->where('key', AppRemoteConfigService::DEFAULT_KEY)->firstOrFail();
};

$apiFeed = static function () use ($apiController, $service): array {
    $request = Request::create('/api/v1/app/remote-config', 'GET', ['locale' => 'de']);
    $request->setUserResolver(fn () => null);
    $response = $apiController->show($request, $service);
    if ($response->getStatusCode() !== 200) {
        smokeFail('Remote Config API Controller lieferte nicht HTTP 200.');
    }
    $payload = $response->getData(true);
    return $payload['config']['appearance']['feed_background'] ?? [];
};

DB::beginTransaction();
try {
    $record = AppRemoteConfig::query()->where('key', AppRemoteConfigService::DEFAULT_KEY)->first();
    $current = $service->normalizeConfig($record?->config_json ?? []);
    $baseVersion = (int) $current['appearance']['feed_background']['version'];

    // Absichtlich stale JSON: genau der gemeldete Admin-Fall null/0 darf den Upload nicht ueberschreiben.
    $stale = $current;
    $stale['appearance']['feed_background'] = ['url' => null, 'version' => 0];

    $png1 = tinyPng();
    $tempFiles[] = $png1;
    $stored = $save($stale, $png1, false);
    $upload = $stored->config_json['appearance']['feed_background'];
    if (! is_string($upload['url'] ?? null) || ! str_starts_with($upload['url'], '/storage/app-backgrounds/feed-background-')) {
        smokeFail('Upload wurde nicht als kanonische /storage/app-backgrounds/feed-background-* URL persistiert.');
    }
    if ((int) ($upload['version'] ?? -1) !== $baseVersion + 1) {
        smokeFail('Upload-Version ist nicht um 1 gestiegen.');
    }
    $path1 = $physicalFromUrl($upload['url']);
    if (! $path1 || ! is_file($path1)) {
        smokeFail('Upload-Datei existiert nach Controller-Save nicht im public Storage.');
    }
    $createdFiles[] = $path1;
    $api1 = $apiFeed();
    if (($api1['url'] ?? null) !== $upload['url'] || (int) ($api1['version'] ?? -1) !== $baseVersion + 1) {
        smokeFail('API uebernimmt Upload URL/Version nicht unmittelbar.');
    }

    $png2 = tinyPng();
    $tempFiles[] = $png2;
    $stored = $save($stored->config_json, $png2, false);
    $replace = $stored->config_json['appearance']['feed_background'];
    if (! is_string($replace['url'] ?? null) || $replace['url'] === $upload['url']) {
        smokeFail('Ersetzen erzeugte keine neue Feed-Hintergrund-URL.');
    }
    if ((int) ($replace['version'] ?? -1) !== $baseVersion + 2) {
        smokeFail('Replace-Version ist nicht erneut gestiegen.');
    }
    $path2 = $physicalFromUrl($replace['url']);
    if (! $path2 || ! is_file($path2)) {
        smokeFail('Replace-Datei existiert nicht im public Storage.');
    }
    $createdFiles[] = $path2;
    $api2 = $apiFeed();
    if (($api2['url'] ?? null) !== $replace['url'] || (int) ($api2['version'] ?? -1) !== $baseVersion + 2) {
        smokeFail('API uebernimmt Replace URL/Version nicht unmittelbar.');
    }

    $stored = $save($stored->config_json, null, true);
    $removed = $stored->config_json['appearance']['feed_background'];
    if (($removed['url'] ?? 'not-null') !== null) {
        smokeFail('Entfernen setzt feed_background.url nicht auf null.');
    }
    if ((int) ($removed['version'] ?? -1) !== $baseVersion + 3) {
        smokeFail('Remove-Version ist nicht erneut gestiegen.');
    }
    $api3 = $apiFeed();
    if (array_key_exists('url', $api3) === false || $api3['url'] !== null || (int) ($api3['version'] ?? -1) !== $baseVersion + 3) {
        smokeFail('API uebernimmt Remove null/Version nicht unmittelbar.');
    }

    echo "SMOKE_BASE_VERSION={$baseVersion}\n";
    echo "SMOKE_UPLOAD_URL={$upload['url']}\n";
    echo 'SMOKE_UPLOAD_VERSION='.(int) $upload['version']."\n";
    echo "SMOKE_REPLACE_URL={$replace['url']}\n";
    echo 'SMOKE_REPLACE_VERSION='.(int) $replace['version']."\n";
    echo "SMOKE_REMOVE_URL=null\n";
    echo 'SMOKE_REMOVE_VERSION='.(int) $removed['version']."\n";
    echo "SMOKE_API_AFTER_UPLOAD=OK\n";
    echo "SMOKE_API_AFTER_REPLACE=OK\n";
    echo "SMOKE_API_AFTER_REMOVE=OK\n";

    DB::rollBack();
} catch (Throwable $e) {
    if (DB::transactionLevel() > 0) {
        DB::rollBack();
    }
    fwrite(STDERR, 'SMOKE FAIL: '.$e->getMessage()."\n");
    foreach ($createdFiles as $file) {
        @unlink($file);
    }
    foreach ($tempFiles as $file) {
        @unlink($file);
    }
    exit(2);
}

foreach ($createdFiles as $file) {
    @unlink($file);
}
foreach ($tempFiles as $file) {
    @unlink($file);
}

echo "TRANSACTION_ROLLBACK=OK\n";
echo "TEST_UPLOAD_FILES_CLEANED=OK\n";
PHP
chmod 0644 "$SMOKE_PHP"

if [[ -n "$APP_USER" && "$PARENT_UID" != "0" && "$(id -u)" == "0" ]]; then
  runuser -u "$APP_USER" -- env HOME="$HUB" php "$SMOKE_PHP" "$REPO" || fail "Transaktionaler Live-Runtime-Smoke als $APP_USER fehlgeschlagen"
else
  php "$SMOKE_PHP" "$REPO" || fail "Transaktionaler Live-Runtime-Smoke fehlgeschlagen"
fi
rm -f "$SMOKE_PHP"
SMOKE_PHP=""
echo "Upload/Replace/Remove + API + Rollback: OK"

step 9 "Externen Guest-Endpoint und Admin-View nach Fix pruefen"
SMOKE_JSON="$(mktemp)"
curl -fsS --max-time 20 -H 'Accept: application/json' 'https://hnt.rocks/api/v1/app/remote-config?locale=de&app_version_code=8' > "$SMOKE_JSON" || { rm -f "$SMOKE_JSON"; fail "Live Remote Config GET fehlgeschlagen"; }
php -r '
$p = json_decode(file_get_contents($argv[1]), true);
$f = $p["config"]["appearance"]["feed_background"] ?? null;
if (!is_array($f) || !array_key_exists("url", $f) || !array_key_exists("version", $f)) { exit(2); }
echo "Externer Guest Remote-Config-Vertrag: OK\n";
' "$SMOKE_JSON" || { rm -f "$SMOKE_JSON"; fail "Externer Feed-Background-Vertrag unvollstaendig"; }
rm -f "$SMOKE_JSON"
grep -Fq 'name="remote_appearance_form" value="1"' "$REPO/resources/views/admin/app-remote-config/index.blade.php" || fail "Live Admin Appearance-Marker fehlt"
grep -Fq 'enctype="multipart/form-data"' "$REPO/resources/views/admin/app-remote-config/index.blade.php" || fail "Live Admin multipart/form-data fehlt"
echo "Guest API + Admin View: OK"

step 10 "main unveraendert bestaetigen und Abschluss ausgeben"
git -C "$REPO" fetch origin main >/dev/null
MAIN_AFTER="$(git -C "$REPO" rev-parse origin/main)"
[[ "$MAIN_AFTER" == "$MAIN_SHA" ]] || fail "origin/main hat sich waehrend 403 geaendert: $MAIN_AFTER"
DEPLOY_OK=1
cat <<SUMMARY
============================================================
PATCH 403 ZUSAMMENFASSUNG
============================================================
Ergebnis:                       ERFOLGREICH / LIVE
origin/main:                    $MAIN_AFTER / UNVERAENDERT
Feature-Branch:                 $FEATURE_BRANCH
Feature-Head:                   $FEATURE_HEAD
Ursache Storage-Rechte geprueft: JA
app-backgrounds Owner:          $NEW_UPLOAD_UID:$NEW_UPLOAD_GID
app-backgrounds Mode:           $NEW_UPLOAD_MODE
Storage-Schreibtest:            OK
Store-Fehler still verschluckt: NEIN
Kanonische Upload-URL:          /storage/app-backgrounds/...
Stale JSON null ueberschreibt:  NEIN
Upload -> Persistenz:           OK
Upload -> API:                  OK
Ersetzen -> Version +1:         OK
Ersetzen -> API:                OK
Entfernen -> URL null:          OK
Entfernen -> Version +1:        OK
Entfernen -> API:               OK
Smoke Produktions-DB:           TRANSAKTION / ROLLBACK
Persistente Test-DB-Aenderung:  NEIN
Test-Upload-Dateien:            GELOESCHT
Migration:                      NEIN
Flutter-Aenderung:              NEIN
Git Merge/Main-Push:            NEIN
Backup:                         $BACKUP
Log-Datei:                      $LOG
============================================================
SUMMARY

echo
echo "ERFOLGREICH / LIVE"
