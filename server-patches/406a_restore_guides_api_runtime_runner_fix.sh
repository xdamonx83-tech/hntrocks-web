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
HNT.ROCKS PATCH 406a - GUIDES API RUNTIME RESTORE / SAFE RUNNER
============================================================
- fuehrt Patch 406 mit zwei vorab korrigierten Harness-Punkten aus
- untracked historische Restore-Dateien werden vor Scope-Pruefung als intent-to-add markiert
- authentifizierter Kernel-Smoke-Heredoc wird syntaktisch korrekt in einer Subshell ausgefuehrt
- danach unveraenderter 406-Ablauf:
  Inventur -> 40-Datei-Restore -> Route/Syntax -> Auth-Kernel-Smoke -> Feature-Branch
  -> Vollbackup -> atomarer Deploy -> Cache -> externe + authentifizierte Live-Smokes
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

old = '''echo
echo "[5/14] Exakten 40-Datei-Scope, PHP-Syntax und Route-Order pruefen ..."
git -C "$WT" diff --check
mapfile -t ACTUAL < <(git -C "$WT" diff --name-only | sort)
'''
new = '''echo
echo "[5/14] Exakten 40-Datei-Scope, PHP-Syntax und Route-Order pruefen ..."
# Die 36 historischen Dateien sind auf current main neu/untracked. Intent-to-add macht
# sie fuer diff --check und diff --name-only sichtbar, ohne sie bereits zu committen.
git -C "$WT" add -N -- "${RESTORE_FILES[@]}"
git -C "$WT" diff --check
mapfile -t ACTUAL < <(git -C "$WT" diff --name-only | sort)
'''
if s.count(old) != 1:
    raise SystemExit(f"406a: Scope-Anker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

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
    raise SystemExit(f"406a: Smoke-Startanker erwartet 1x, gefunden {s.count(old)}x")
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
    raise SystemExit(f"406a: Smoke-Endanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406-Harness ist syntaktisch ungueltig"
grep -Fq 'git -C "$WT" add -N -- "${RESTORE_FILES[@]}"' "$TMP" || fail "intent-to-add Fix fehlt"
grep -Fq 'SMOKE_LABEL="$label" php <<' "$TMP" || fail "Kernel-Smoke Fix fehlt"

echo "406a Harness: OK"
echo
bash "$TMP"
