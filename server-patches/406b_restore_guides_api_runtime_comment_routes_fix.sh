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
HNT.ROCKS PATCH 406b - GUIDES API RESTORE / COMMENT ROUTE FIX
============================================================
- setzt den 406-Restore erneut direkt von current main auf
- uebernimmt die zwei bereits benoetigten 406a-Harness-Fixes
- korrigiert zusaetzlich die letzten historischen Web-Route-Abhaengigkeiten
  guides.comments.update / guides.comments.destroy im GuideCommentController
- API-Kommentar-Actions zeigen danach stabil auf /api/v1/guides/comments/{id}
- KEINE Migration / KEINE Demo-Daten / KEIN Flutter
- erst Predeploy-Smokes, dann Feature-Commit, Backup, atomarer Deploy und Live-Smokes
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
    raise SystemExit(f"406b: Scope-Anker erwartet 1x, gefunden {s.count(old)}x")
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
    raise SystemExit(f"406b: Smoke-Startanker erwartet 1x, gefunden {s.count(old)}x")
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
    raise SystemExit(f"406b: Smoke-Endanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406b: Die historische API-Comment-Payload referenziert zwei alte Web-Routennamen.
# Diese Web-Routen existieren im heutigen main nicht mehr. Die App nutzt die API-Pfade.
anchor = '''    "route('guides.media.show', $media)": "url('/api/v1/guides/drafts/media/'.$media->id)",
}'''
replacement = '''    "route('guides.media.show', $media)": "url('/api/v1/guides/drafts/media/'.$media->id)",
    "route('guides.comments.update', $comment)": "url('/api/v1/guides/comments/'.$comment->id)",
    "route('guides.comments.destroy', $comment)": "url('/api/v1/guides/comments/'.$comment->id)",
}'''
if s.count(anchor) != 1:
    raise SystemExit(f"406b: Comment-Route-Anker erwartet 1x, gefunden {s.count(anchor)}x")
s = s.replace(anchor, replacement, 1)

p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406b-Harness ist syntaktisch ungueltig"
grep -Fq 'git -C "$WT" add -N -- "${RESTORE_FILES[@]}"' "$TMP" || fail "intent-to-add Fix fehlt"
grep -Fq 'SMOKE_LABEL="$label" php <<' "$TMP" || fail "Kernel-Smoke Fix fehlt"
grep -Fq 'guides.comments.update' "$TMP" || fail "Comment-Update-Kompatibilitaetsfix fehlt"
grep -Fq 'guides.comments.destroy' "$TMP" || fail "Comment-Delete-Kompatibilitaetsfix fehlt"

echo "406b Harness: OK"
echo
bash "$TMP"
