#!/usr/bin/env bash
set -Eeuo pipefail

HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
BASE_PATCH="server-patches/400a_test_remote_app_backgrounds_without_sqlite.sh"
BASE_BLOB="d43187fa16ec0a3b762e16e1f0ac67886eb6525f"
TMP="$(mktemp)"

cleanup() {
  rm -f "$TMP" >/dev/null 2>&1 || true
}
trap cleanup EXIT

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 400b - FIX GUEST NULL ASSERTION
============================================================
- korrigiert ausschliesslich den Testfehler aus 400a
- PHP ?? kann einen vorhandenen null-Wert nicht von einem fehlenden Key unterscheiden
- prueft deshalb jetzt array_key_exists(...) UND danach explizit === null
- Feature-Code agent/app-remote-backgrounds wird NICHT veraendert
- KEINE PRODUKTIONS-DB / KEINE MIGRATION / KEIN DEPLOY
- main wird NICHT veraendert / KEIN MERGE / KEIN FEATURE-PUSH
============================================================
HEAD

git -C "$REPO" fetch origin agent/server-patches --prune
BASE_NOW="$(git -C "$REPO" rev-parse "origin/agent/server-patches:$BASE_PATCH")"
echo "400a Basis-Blob: $BASE_NOW"
[[ "$BASE_NOW" == "$BASE_BLOB" ]] || {
  echo "FEHLER: 400a wurde seit 400b veraendert: $BASE_NOW (erwartet $BASE_BLOB)" >&2
  exit 2
}

git -C "$REPO" show "origin/agent/server-patches:$BASE_PATCH" > "$TMP"

python3 - "$TMP" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text(encoding='utf-8')

replacements = {
    "expectTrue(($payload['config']['appearance']['auth_background']['url'] ?? 'missing') === null, 'Guest API Auth Default fehlt');":
    "expectTrue(array_key_exists('url', $payload['config']['appearance']['auth_background'] ?? []) && $payload['config']['appearance']['auth_background']['url'] === null, 'Guest API Auth Default fehlt');",
    "expectTrue(($payload['config']['appearance']['feed_background']['url'] ?? 'missing') === null, 'Guest API Feed Default fehlt');":
    "expectTrue(array_key_exists('url', $payload['config']['appearance']['feed_background'] ?? []) && $payload['config']['appearance']['feed_background']['url'] === null, 'Guest API Feed Default fehlt');",
}

for old, new in replacements.items():
    if text.count(old) != 1:
        raise SystemExit(f'400b: erwartete Assertion nicht eindeutig gefunden: {old}')
    text = text.replace(old, new, 1)

text = text.replace('HNT.ROCKS PATCH 400a - DB-FREIER TEST REMOTE APP BACKGROUNDS',
                    'HNT.ROCKS PATCH 400b - DB-FREIER TEST REMOTE APP BACKGROUNDS')
text = text.replace('PATCH 400a ZUSAMMENFASSUNG', 'PATCH 400b ZUSAMMENFASSUNG')

path.write_text(text, encoding='utf-8')
PY

bash -n "$TMP"
grep -Fq "array_key_exists('url', \$payload['config']['appearance']['auth_background']" "$TMP" || {
  echo "FEHLER: korrigierte Auth-null-Assertion fehlt" >&2
  exit 2
}
grep -Fq "array_key_exists('url', \$payload['config']['appearance']['feed_background']" "$TMP" || {
  echo "FEHLER: korrigierte Feed-null-Assertion fehlt" >&2
  exit 2
}

echo "400b Harness: Null-Assertion korrigiert; starte denselben DB-freien Test erneut."
echo
bash "$TMP"
