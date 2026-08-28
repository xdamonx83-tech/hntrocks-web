#!/usr/bin/env bash
set -Eeuo pipefail

HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
BASE="server-patches/406_restore_guides_api_runtime_and_routes.sh"
BASE_BLOB="af3ca8a0ce5bb451e3e478c3a20eec161fac6621"
TMP="$(mktemp)"

cleanup() { rm -f "$TMP" >/dev/null 2>&1 || true; }
trap cleanup EXIT

fail() { echo; echo "FEHLER: $*" >&2; exit 2; }

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 406e - GUIDES API RESTORE / COMPOSER AUTOLOAD REBUILD
============================================================
- setzt den 406-Restore erneut direkt von current main auf
- uebernimmt alle Fixes aus 406a/406b/406c/406d
- letzter Befund: selbst mit eigenem Worktree-vendor sieht der vorhandene optimierte Composer-Autoloader
  die neu wiederhergestellten App-Klassen nicht, weil dessen generierte Autoload-Dateien noch vom current-main-Stand stammen
- Composer-Autoload wird deshalb im isolierten Worktree VOR Artisan neu aufgebaut
- vendor/composer + vendor/autoload.php werden vorher aus den Hardlinks geloest, damit der Preflight die Live-App nicht beruehrt
- vor Deploy wird weiterhin Controller/Model/Service class_exists explizit geprueft
- NACH atomarem Live-Deploy wird Composer-Autoload auch live neu aufgebaut und erneut auf die Guide-Klassen geprueft
- bei spaeterem Fehler regeneriert der Rollback nach Wiederherstellung der alten Dateien auch den alten Live-Autoload
- erst danach Route-Cache + externe/authentifizierte Live-Smokes
- KEINE Migration / KEINE Demo-Daten / KEIN Flutter
- KEIN MERGE / KEIN MAIN-PUSH
============================================================
HEAD

git -C "$REPO" fetch origin agent/server-patches --prune
NOW="$(git -C "$REPO" rev-parse "origin/agent/server-patches:$BASE")"
echo "406 Basis-Script Blob: $NOW"
[[ "$NOW" == "$BASE_BLOB" ]] || fail "406 Basis-Script wurde veraendert: $NOW (erwartet $BASE_BLOB)"

git -C "$REPO" show "origin/agent/server-patches:$BASE" > "$TMP"

python3 - "$TMP" <<'PY'
from pathlib import Path
import sys

p = Path(sys.argv[1])
s = p.read_text(encoding='utf-8')

# 406a: historische, auf main neue Dateien vor diff-Pruefung sichtbar machen.
old = '''echo
echo "[5/14] Exakten 40-Datei-Scope, PHP-Syntax und Route-Order pruefen ..."
git -C "$WT" diff --check
mapfile -t ACTUAL < <(git -C "$WT" diff --name-only | sort)
'''
new = '''echo
echo "[5/14] Exakten 40-Datei-Scope, PHP-Syntax und Route-Order pruefen ..."
git -C "$WT" add -N -- "${RESTORE_FILES[@]}"
git -C "$WT" diff --check
mapfile -t ACTUAL < <(git -C "$WT" diff --name-only | sort)
'''
if s.count(old) != 1:
    raise SystemExit(f"406e: Scope-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406a: Kernel-Smoke Heredoc korrekt in Subshell ausfuehren.
old = '''run_kernel_smoke() {
  local base="$1" label="$2"
  (cd "$base" && SMOKE_LABEL="$label" php <<'PHP')
'''
new = '''run_kernel_smoke() {
  local base="$1" label="$2"
  (
    cd "$base"
    SMOKE_LABEL="$label" php <<'PHP'
'''
if s.count(old) != 1:
    raise SystemExit(f"406e: Smoke-Startanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

old = '''    fwrite(STDERR, $label.'_FAIL='.$e->getMessage()."\\n");
    exit(1);
}
PHP
}

run_kernel_smoke "$WT" "PREDEPLOY"'''
new = '''    fwrite(STDERR, $label.'_FAIL='.$e->getMessage()."\\n");
    exit(1);
}
PHP
  )
}

run_kernel_smoke "$WT" "PREDEPLOY"'''
if s.count(old) != 1:
    raise SystemExit(f"406e: Smoke-Endanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406b: historische Comment-Payload darf keine entfernten Web-Routennamen mehr verwenden.
anchor = '''    "route('guides.media.show', $media)": "url('/api/v1/guides/drafts/media/'.$media->id)",
}'''
replacement = '''    "route('guides.media.show', $media)": "url('/api/v1/guides/drafts/media/'.$media->id)",
    "route('guides.comments.update', $comment)": "url('/api/v1/guides/comments/'.$comment->id)",
    "route('guides.comments.destroy', $comment)": "url('/api/v1/guides/comments/'.$comment->id)",
}'''
if s.count(anchor) != 1:
    raise SystemExit(f"406e: Comment-Route-Anker erwartet 1x, gefunden {s.count(anchor)}x")
s = s.replace(anchor, replacement, 1)

# 406c: Schritt 6 mit sichtbarer Fehlerausgabe und korrektem JSON-Check.
old = '''(cd "$WT" && php artisan route:clear >/dev/null && php artisan config:clear >/dev/null)
ROUTE_JSON="$WT/.406-routes.json"
(cd "$WT" && php artisan route:list --path=api/v1/guides --json) > "$ROUTE_JSON"
php - "$ROUTE_JSON" <<'PHP'
<?php
$routes = json_decode(file_get_contents($argv[1]), true);
'''
new = '''if ! (cd "$WT" && php artisan route:clear && php artisan config:clear); then
  die "Worktree route:clear/config:clear fehlgeschlagen"
fi
ROUTE_JSON="$WT/.406-routes.json"
ROUTE_ERR="$WT/.406-routes.err"
if ! (cd "$WT" && php artisan route:list --path=api/v1/guides --json) > "$ROUTE_JSON" 2> "$ROUTE_ERR"; then
  echo "----- route:list STDOUT -----"
  cat "$ROUTE_JSON" 2>/dev/null || true
  echo "----- route:list STDERR -----"
  cat "$ROUTE_ERR" 2>/dev/null || true
  die "Worktree route:list fuer Guide-API fehlgeschlagen"
fi
rm -f "$ROUTE_ERR"
ROUTE_JSON_PATH="$ROUTE_JSON" php <<'PHP'
<?php
$path = getenv('ROUTE_JSON_PATH');
$routes = $path ? json_decode(file_get_contents($path), true) : null;
'''
if s.count(old) != 1:
    raise SystemExit(f"406e: Route-Preflight-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406e: Composer-Binary frueh und VOR Deploy verbindlich ermitteln.
old = '''echo "Datei-Scope/Syntax/Order: OK (40 Dateien)"

echo
echo "[6/14] Worktree mit bestehender Laravel-Laufzeit booten und 19 Guide-Routen verifizieren ..."
ln -s "$LIVE/vendor" "$WT/vendor"
ln -s "$LIVE/.env" "$WT/.env"
mkdir -p "$WT/bootstrap/cache" "$WT/storage/framework/cache" "$WT/storage/framework/sessions" "$WT/storage/framework/views" "$WT/storage/logs"
'''
new = '''echo "Datei-Scope/Syntax/Order: OK (40 Dateien)"

echo
echo "[6/14] Worktree-Autoload neu aufbauen und 19 Guide-Routen verifizieren ..."
if command -v composer >/dev/null 2>&1; then
  COMPOSER=(composer)
elif [[ -f "$HUB/composer.phar" ]]; then
  COMPOSER=(php "$HUB/composer.phar")
elif [[ -f "$LIVE/composer.phar" ]]; then
  COMPOSER=(php "$LIVE/composer.phar")
else
  die "Composer wurde auf dem Server nicht gefunden; ohne kontrollierten Autoload-Rebuild kein Deploy"
fi
echo "Composer: $(${COMPOSER[@]} --version 2>/dev/null | head -n1)"

# Pakete per Hardlinks kopieren, aber die von dump-autoload geschriebenen Composer-Dateien
# bewusst physisch vom Live-vendor trennen.
cp -al "$LIVE/vendor" "$WT/vendor"
rm -rf "$WT/vendor/composer"
cp -a "$LIVE/vendor/composer" "$WT/vendor/composer"
cp -a --remove-destination "$LIVE/vendor/autoload.php" "$WT/vendor/autoload.php"
ln -s "$LIVE/.env" "$WT/.env"
mkdir -p "$WT/bootstrap/cache" "$WT/storage/framework/cache" "$WT/storage/framework/sessions" "$WT/storage/framework/views" "$WT/storage/logs"

if ! (cd "$WT" && COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER[@]}" dump-autoload --no-dev --optimize --no-scripts --no-interaction); then
  die "Composer dump-autoload im Worktree fehlgeschlagen"
fi
(
  cd "$WT"
  php <<'PHP'
<?php
require getcwd().'/vendor/autoload.php';
$classes = [
    App\\Http\\Controllers\\Api\\V1\\ApiGuidesController::class,
    App\\Models\\Guide::class,
    App\\Services\\Guides\\GuideWorkflowService::class,
];
foreach ($classes as $class) {
    if (! class_exists($class)) {
        fwrite(STDERR, "AUTOLOAD_MISSING=$class\n");
        exit(1);
    }
    echo "AUTOLOAD_OK=$class\n";
}
PHP
) || die "Worktree Composer-Autoload sieht die wiederhergestellte Guide-Runtime trotz Rebuild nicht"
'''
if s.count(old) != 1:
    raise SystemExit(f"406e: Worktree-Autoload-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# Rollback muss nach Rueckkopieren der alten App-Dateien auch wieder den alten Autoload erzeugen,
# bevor Laravel-Caches hergestellt werden.
old = '''  restore_caches
  echo "ROLLBACK: abgeschlossen. Backup bleibt unter $BACKUP"
'''
new = '''  if declare -p COMPOSER >/dev/null 2>&1; then
    (cd "$LIVE" && COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER[@]}" dump-autoload --no-dev --optimize --no-scripts --no-interaction >/dev/null 2>&1) || true
  fi
  restore_caches
  echo "ROLLBACK: abgeschlossen. Backup bleibt unter $BACKUP"
'''
if s.count(old) != 1:
    raise SystemExit(f"406e: Rollback-Autoload-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# Nach dem atomaren App-Deploy live Autoload neu generieren. DEPLOYED=1 ist zu diesem Zeitpunkt
# bereits gesetzt; jeder Fehler fuehrt deshalb ueber den bestehenden Rollback zurueck.
old = '''echo "40 Dateien atomar live: OK"

echo
echo "[11/14] Config-/Route-Cache kontrolliert invalidieren bzw. wieder aufbauen ..."
'''
new = '''echo "40 Dateien atomar live: OK"
echo "Live Composer-Autoload fuer Guide-Runtime neu aufbauen ..."
if ! (cd "$LIVE" && COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER[@]}" dump-autoload --no-dev --optimize --no-scripts --no-interaction); then
  die "Live Composer dump-autoload fehlgeschlagen"
fi
(
  cd "$LIVE"
  php <<'PHP'
<?php
require getcwd().'/vendor/autoload.php';
$classes = [
    App\\Http\\Controllers\\Api\\V1\\ApiGuidesController::class,
    App\\Models\\Guide::class,
    App\\Services\\Guides\\GuideWorkflowService::class,
];
foreach ($classes as $class) {
    if (! class_exists($class)) {
        fwrite(STDERR, "LIVE_AUTOLOAD_MISSING=$class\n");
        exit(1);
    }
    echo "LIVE_AUTOLOAD_OK=$class\n";
}
PHP
) || die "Live Composer-Autoload sieht die deployte Guide-Runtime nicht"

echo
echo "[11/14] Config-/Route-Cache kontrolliert invalidieren bzw. wieder aufbauen ..."
'''
if s.count(old) != 1:
    raise SystemExit(f"406e: Live-Autoload-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406e-Harness ist syntaktisch ungueltig"
grep -Fq 'git -C "$WT" add -N -- "${RESTORE_FILES[@]}"' "$TMP" || fail "intent-to-add Fix fehlt"
grep -Fq 'SMOKE_LABEL="$label" php <<' "$TMP" || fail "Kernel-Smoke Fix fehlt"
grep -Fq 'guides.comments.update' "$TMP" || fail "Comment-Update-Kompatibilitaetsfix fehlt"
grep -Fq 'ROUTE_JSON_PATH="$ROUTE_JSON" php <<' "$TMP" || fail "Route-JSON Fix fehlt"
grep -Fq 'dump-autoload --no-dev --optimize --no-scripts --no-interaction' "$TMP" || fail "Composer Autoload-Rebuild fehlt"
grep -Fq 'LIVE_AUTOLOAD_OK=' "$TMP" || fail "Live-Autoload-Klassencheck fehlt"
grep -Fq 'declare -p COMPOSER' "$TMP" || fail "Rollback-Autoload-Rebuild fehlt"
if grep -Fq 'ln -s "$LIVE/vendor" "$WT/vendor"' "$TMP"; then fail "alter vendor-Symlink ist noch vorhanden"; fi
if grep -Fq 'php - "$ROUTE_JSON"' "$TMP"; then fail "ungueltiger php-minus Aufruf ist noch vorhanden"; fi

echo "406e Harness: OK"
echo
bash "$TMP"
