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
HNT.ROCKS PATCH 406d - GUIDES API RESTORE / WORKTREE AUTOLOAD FIX
============================================================
- setzt den 406-Restore erneut direkt von current main auf
- uebernimmt alle Fixes aus 406a/406b/406c
- Ursache des letzten Preflight-Fehlers:
  der Worktree nutzte vendor als Symlink auf die Live-App; Composer PSR-4 App\\ zeigte dadurch
  physisch auf /home/users/hunthub/www/hnt.rocks/app statt auf den isolierten Worktree
- vendor wird im Worktree jetzt per Hardlink-Kopie bereitgestellt; dadurch bleibt vendor unveraendert,
  aber Composers relative App\\-Basis zeigt korrekt auf den Worktree
- vor Artisan wird class_exists fuer Controller/Model/Service explizit verifiziert
- danach Route-Preflight + Auth-Kernel-Smoke -> Feature-Commit -> Backup -> Deploy -> Live-Smokes
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

# 406a Fix 1: historische, auf main neue Dateien vor diff-Pruefung sichtbar machen.
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
    raise SystemExit(f"406d: Scope-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406a Fix 2: Kernel-Smoke Heredoc korrekt in Subshell ausfuehren.
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
    raise SystemExit(f"406d: Smoke-Startanker erwartet 1x, gefunden {s.count(old)}x")
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
    raise SystemExit(f"406d: Smoke-Endanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406b: historische Comment-Payload darf keine entfernten Web-Routennamen mehr verwenden.
anchor = '''    "route('guides.media.show', $media)": "url('/api/v1/guides/drafts/media/'.$media->id)",
}'''
replacement = '''    "route('guides.media.show', $media)": "url('/api/v1/guides/drafts/media/'.$media->id)",
    "route('guides.comments.update', $comment)": "url('/api/v1/guides/comments/'.$comment->id)",
    "route('guides.comments.destroy', $comment)": "url('/api/v1/guides/comments/'.$comment->id)",
}'''
if s.count(anchor) != 1:
    raise SystemExit(f"406d: Comment-Route-Anker erwartet 1x, gefunden {s.count(anchor)}x")
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
    raise SystemExit(f"406d: Route-Preflight-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406d: Ein vendor-Symlink laedt App\\ aus der Live-App, weil Composer seine PSR-4-Basis
# relativ zum realen vendor/composer-Pfad berechnet. Hardlink-Kopie ist schnell/read-only,
# gibt den Autoload-Dateien aber den Worktree als physische Basis.
old = '''ln -s "$LIVE/vendor" "$WT/vendor"
ln -s "$LIVE/.env" "$WT/.env"
mkdir -p "$WT/bootstrap/cache" "$WT/storage/framework/cache" "$WT/storage/framework/sessions" "$WT/storage/framework/views" "$WT/storage/logs"
'''
new = '''cp -al "$LIVE/vendor" "$WT/vendor"
ln -s "$LIVE/.env" "$WT/.env"
mkdir -p "$WT/bootstrap/cache" "$WT/storage/framework/cache" "$WT/storage/framework/sessions" "$WT/storage/framework/views" "$WT/storage/logs"
(
  cd "$WT"
  php -r 'require "vendor/autoload.php"; $classes=["App\\\\Http\\\\Controllers\\\\Api\\\\V1\\\\ApiGuidesController","App\\\\Models\\\\Guide","App\\\\Services\\\\Guides\\\\GuideWorkflowService"]; foreach($classes as $class){ if(!class_exists($class)){ fwrite(STDERR,"AUTOLOAD_MISSING=$class\\n"); exit(1); } echo "AUTOLOAD_OK=$class\\n"; }'
) || die "Worktree Composer-Autoload sieht die wiederhergestellte Guide-Runtime nicht"
'''
if s.count(old) != 1:
    raise SystemExit(f"406d: Vendor-Autoload-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406d-Harness ist syntaktisch ungueltig"
grep -Fq 'git -C "$WT" add -N -- "${RESTORE_FILES[@]}"' "$TMP" || fail "intent-to-add Fix fehlt"
grep -Fq 'SMOKE_LABEL="$label" php <<' "$TMP" || fail "Kernel-Smoke Fix fehlt"
grep -Fq 'guides.comments.update' "$TMP" || fail "Comment-Update-Kompatibilitaetsfix fehlt"
grep -Fq 'ROUTE_JSON_PATH="$ROUTE_JSON" php <<' "$TMP" || fail "Route-JSON Fix fehlt"
grep -Fq 'cp -al "$LIVE/vendor" "$WT/vendor"' "$TMP" || fail "Worktree-vendor Hardlink-Fix fehlt"
grep -Fq 'AUTOLOAD_OK=' "$TMP" || fail "Autoload-Klassencheck fehlt"
if grep -Fq 'ln -s "$LIVE/vendor" "$WT/vendor"' "$TMP"; then fail "alter vendor-Symlink ist noch vorhanden"; fi
if grep -Fq 'php - "$ROUTE_JSON"' "$TMP"; then fail "ungueltiger php-minus Aufruf ist noch vorhanden"; fi

echo "406d Harness: OK"
echo
bash "$TMP"
