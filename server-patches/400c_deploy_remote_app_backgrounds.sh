#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NO="400c"
HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
MAIN_SHA="18d4febb5be55c9a5b2fc8b10233bf3d23ffb4ac"
FEATURE_BRANCH="agent/app-remote-backgrounds"
FEATURE_HEAD="3ea6b434e13eec0d4553d12b9e1b5efd7b5f1fc9"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="$REPO/storage/app/deploy-backups/remote-app-backgrounds/$STAMP"
LOG="$HUB/${PATCH_NO}_deploy_remote_app_backgrounds_${STAMP}.log"
SMOKE_JSON="$(mktemp)"
DEPLOY_STARTED=0
DEPLOY_OK=0

FEATURE_FILES=(
  "app/Http/Controllers/Admin/AdminAppRemoteConfigController.php"
  "app/Http/Controllers/Api/V1/AppRemoteConfigController.php"
  "app/Http/Middleware/AuthenticateApiToken.php"
  "app/Services/AppConfig/AppRemoteConfigService.php"
  "resources/views/admin/app-remote-config/index.blade.php"
  "tests/Feature/AppRemoteConfigAppearanceTest.php"
  "tests/Feature/AppRemoteConfigPublicReadTest.php"
)

RUNTIME_FILES=(
  "app/Http/Controllers/Admin/AdminAppRemoteConfigController.php"
  "app/Http/Controllers/Api/V1/AppRemoteConfigController.php"
  "app/Http/Middleware/AuthenticateApiToken.php"
  "app/Services/AppConfig/AppRemoteConfigService.php"
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
  printf '\n[%s/11] %s ...\n' "$1" "$2"
}

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
  echo "ROLLBACK: stelle die fuenf gesicherten Runtime-Dateien wieder her ..."
  for file in "${RUNTIME_FILES[@]}"; do
    if [[ -f "$BACKUP/$file" ]]; then
      mkdir -p "$(dirname "$REPO/$file")"
      cp -a "$BACKUP/$file" "$REPO/$file"
    fi
  done
  clear_runtime_caches
  echo "ROLLBACK: abgeschlossen. Backup bleibt unter $BACKUP erhalten."
  set -e
}

on_exit() {
  status=$?
  rm -f "$SMOKE_JSON" >/dev/null 2>&1 || true
  if [[ $DEPLOY_STARTED -eq 1 && $DEPLOY_OK -ne 1 ]]; then
    rollback
  fi
  exit "$status"
}
trap on_exit EXIT

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 400c - DEPLOY REMOTE APP BACKGROUNDS
============================================================
- deployt den mit 400b erfolgreich geprueften Backend-Stand
- bestehender Endpoint /api/v1/app/remote-config bleibt bestehen
- GET Remote Config wird fuer Auth-Screens vor Login lesbar
- Auth-Hintergrund + Feed-Hintergrund: URL + Version
- Admin kann JPG/JPEG/PNG/WebP bis 8 MB hochladen oder entfernen
- Uploads landen im bestehenden public Storage unter app-backgrounds
- Versionsnummer steigt bei Aenderung/Entfernung automatisch
- Remote-Config-Cache wird unmittelbar invalidiert
- KEINE Migration / KEINE neue Tabelle / KEINE Flutter-Aenderung
- kein Git-Merge und kein Push nach main
- vor Live-Aenderung Vollbackup der 5 Runtime-Dateien
- bei Fehler nach Deploy-Start automatischer Rollback
============================================================
HEAD

step 1 "Exakte main-/Feature-Basis und erfolgreichen 400b-Test verifizieren"
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

TEST_LOG="$(ls -1t "$HUB"/400a_test_remote_app_backgrounds_*.log 2>/dev/null | head -n 1 || true)"
[[ -n "$TEST_LOG" && -f "$TEST_LOG" ]] || fail "Kein 400b-Testlog gefunden"
grep -Fq 'PATCH 400b ZUSAMMENFASSUNG' "$TEST_LOG" || fail "Neuestes Testlog ist kein erfolgreicher 400b-Lauf: $TEST_LOG"
grep -Eq 'Ergebnis:[[:space:]]+ERFOLGREICH' "$TEST_LOG" || fail "400b ist im Testlog nicht ERFOLGREICH"
grep -Fq "Feature-Head:             $FEATURE_HEAD" "$TEST_LOG" || fail "400b-Testlog gehoert nicht zum erwarteten Feature-Head"
echo "400b-Testlog: $TEST_LOG / ERFOLGREICH"

step 2 "Feature-Scope exakt auf sieben erwartete Dateien begrenzen"
mapfile -t CHANGED < <(git -C "$REPO" diff --name-only "$MAIN_SHA..$FEATURE_HEAD" | sort)
mapfile -t EXPECTED_SORTED < <(printf '%s\n' "${FEATURE_FILES[@]}" | sort)
printf 'Feature-Scope:\n'; printf '  %s\n' "${CHANGED[@]}"
[[ ${#CHANGED[@]} -eq ${#EXPECTED_SORTED[@]} ]] || fail "Erwartet ${#EXPECTED_SORTED[@]} Dateien, gefunden ${#CHANGED[@]}"
[[ "$(printf '%s\n' "${CHANGED[@]}")" == "$(printf '%s\n' "${EXPECTED_SORTED[@]}")" ]] || fail "Feature-Scope stimmt nicht"
git -C "$REPO" diff --check "$MAIN_SHA..$FEATURE_HEAD"
echo "Feature-Scope: OK"

step 3 "Nur die fuenf Live-Runtime-Dateien auf unveraenderten main-Stand pruefen"
for file in "${RUNTIME_FILES[@]}"; do
  [[ -f "$REPO/$file" ]] || fail "Live-Datei fehlt: $file"
  git -C "$REPO" diff --quiet "$MAIN_SHA" -- "$file" || fail "Live-Datei weicht bereits von main ab: $file"
  [[ -z "$(git -C "$REPO" status --porcelain -- "$file")" ]] || fail "Live-Datei hat lokale/staged Aenderungen: $file"
done
echo "Runtime-Dateien vor Deploy: exakt main"

step 4 "Vollbackup der fuenf Runtime-Dateien anlegen"
mkdir -p "$BACKUP"
for file in "${RUNTIME_FILES[@]}"; do
  mkdir -p "$BACKUP/$(dirname "$file")"
  cp -a "$REPO/$file" "$BACKUP/$file"
done
printf '%s\n' "$MAIN_SHA" > "$BACKUP/main-sha.txt"
printf '%s\n' "$FEATURE_HEAD" > "$BACKUP/feature-head.txt"
echo "Backup: $BACKUP"

step 5 "Feature-Dateien vor dem Live-Schalten statisch pruefen"
for file in \
  app/Http/Controllers/Admin/AdminAppRemoteConfigController.php \
  app/Http/Controllers/Api/V1/AppRemoteConfigController.php \
  app/Http/Middleware/AuthenticateApiToken.php \
  app/Services/AppConfig/AppRemoteConfigService.php; do
  TMP_PHP="$(mktemp --suffix=.php)"
  git -C "$REPO" show "$FEATURE_HEAD:$file" > "$TMP_PHP"
  php -l "$TMP_PHP" >/dev/null || { rm -f "$TMP_PHP"; fail "PHP-Syntaxfehler im Feature: $file"; }
  rm -f "$TMP_PHP"
done

git -C "$REPO" show "$FEATURE_HEAD:app/Services/AppConfig/AppRemoteConfigService.php" | grep -Fq "'appearance' => [" || fail "appearance-Vertrag fehlt"
git -C "$REPO" show "$FEATURE_HEAD:app/Http/Middleware/AuthenticateApiToken.php" | grep -Fq "request->is('api/v1/app/remote-config')" || fail "Guest-Read-Ausnahme fehlt"
git -C "$REPO" show "$FEATURE_HEAD:resources/views/admin/app-remote-config/index.blade.php" | grep -Fq 'Remote App Hintergründe' || fail "Admin-UI fehlt"
echo "Feature-Marker/Syntax: OK"

step 6 "Fuenf Runtime-Dateien atomar aus dem getesteten Feature-Head deployen"
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
  [[ "$expected_sha" == "$live_sha" ]] || fail "Hash-Mismatch nach Deploy: $file"
  echo "LIVE: $file"
done

step 7 "Live-PHP-Syntax, Blade und Remote-Config-Cache aktualisieren"
for file in \
  app/Http/Controllers/Admin/AdminAppRemoteConfigController.php \
  app/Http/Controllers/Api/V1/AppRemoteConfigController.php \
  app/Http/Middleware/AuthenticateApiToken.php \
  app/Services/AppConfig/AppRemoteConfigService.php; do
  php -l "$REPO/$file" >/dev/null || fail "Live PHP-Syntaxfehler: $file"
done
(
  cd "$REPO"
  php artisan view:clear >/dev/null
  php artisan view:cache >/dev/null
  php artisan cache:forget app_remote_config:active:default >/dev/null
) || fail "Laravel Cache-/Blade-Aktualisierung fehlgeschlagen"
echo "Live PHP + Blade + Cache: OK"

step 8 "Oeffentlichen Remote-Config-Endpoint live pruefen"
curl -fsS --max-time 20 -H 'Accept: application/json' 'https://hnt.rocks/api/v1/app/remote-config?locale=de&app_version_code=8' > "$SMOKE_JSON" || fail "Live Remote Config GET fehlgeschlagen"
php -r '
$p = json_decode(file_get_contents($argv[1]), true);
if (!is_array($p)) { fwrite(STDERR, "ungueltiges JSON\n"); exit(2); }
$a = $p["config"]["appearance"] ?? null;
if (!is_array($a)) { fwrite(STDERR, "config.appearance fehlt\n"); exit(2); }
foreach (["auth_background", "feed_background"] as $key) {
    if (!isset($a[$key]) || !is_array($a[$key])) { fwrite(STDERR, "$key fehlt\n"); exit(2); }
    if (!array_key_exists("url", $a[$key])) { fwrite(STDERR, "$key.url fehlt\n"); exit(2); }
    if (!array_key_exists("version", $a[$key]) || !is_int($a[$key]["version"])) { fwrite(STDERR, "$key.version fehlt/ungueltig\n"); exit(2); }
}
echo "Live Remote Config Contract: OK\n";
' "$SMOKE_JSON" || fail "Live Remote-Config-Vertrag unvollstaendig"

step 9 "Geschuetzte API bleibt ohne Token geschuetzt"
PROTECTED_CODE="$(curl -sS --max-time 20 -o /dev/null -w '%{http_code}' -H 'Accept: application/json' 'https://hnt.rocks/api/v1/me' || true)"
[[ "$PROTECTED_CODE" == "401" ]] || fail "/api/v1/me liefert ohne Token $PROTECTED_CODE statt 401"
echo "Protected API ohne Token: 401 / OK"

step 10 "Admin-UI und Storage-Verzeichnis verifizieren"
[[ -f "$REPO/resources/views/admin/app-remote-config/index.blade.php" ]] || fail "Admin View fehlt live"
grep -Fq 'auth_background_file' "$REPO/resources/views/admin/app-remote-config/index.blade.php" || fail "Auth Upload UI fehlt live"
grep -Fq 'feed_background_file' "$REPO/resources/views/admin/app-remote-config/index.blade.php" || fail "Feed Upload UI fehlt live"
mkdir -p "$REPO/storage/app/public/app-backgrounds"
[[ -d "$REPO/storage/app/public/app-backgrounds" ]] || fail "app-backgrounds Storage-Verzeichnis fehlt"
echo "Admin-UI + Storage: OK"

step 11 "main-Ref unveraendert bestaetigen und Abschluss ausgeben"
git -C "$REPO" fetch origin main >/dev/null
MAIN_AFTER="$(git -C "$REPO" rev-parse origin/main)"
[[ "$MAIN_AFTER" == "$MAIN_SHA" ]] || fail "origin/main hat sich waehrend des Deploys geaendert: $MAIN_AFTER"
DEPLOY_OK=1
cat <<SUMMARY
============================================================
PATCH 400c ZUSAMMENFASSUNG
============================================================
Ergebnis:                 ERFOLGREICH / LIVE
origin/main:              $MAIN_AFTER / UNVERAENDERT
Feature-Branch:           $FEATURE_BRANCH
Feature-Head:             $FEATURE_HEAD
Runtime-Dateien deployed: ${#RUNTIME_FILES[@]}
Guest Remote Config:      LIVE / OK
Auth Background Contract: LIVE / OK
Feed Background Contract: LIVE / OK
Andere Token-Routen:      401 OHNE TOKEN
Admin Upload UI:          LIVE
Upload Storage:           storage/app/public/app-backgrounds
Versionierung:            LIVE
Cache-Invalidierung:      LIVE
Migration:                NEIN
Neue Tabelle:             NEIN
Flutter-Aenderung:        NEIN
Git Merge/Main-Push:      NEIN
Backup:                   $BACKUP
Log-Datei:                $LOG
============================================================
SUMMARY

echo
echo "ERFOLGREICH / LIVE"