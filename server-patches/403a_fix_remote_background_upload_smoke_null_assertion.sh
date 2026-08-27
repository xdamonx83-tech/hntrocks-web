#!/usr/bin/env bash
set -Eeuo pipefail

PATCH_NO="403a"
HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
BASE_PATCH="server-patches/403_fix_remote_background_upload_persistence.sh"
BASE_BLOB="14589f36916c8feaba07944cb96438be381cae96"
TMP="$(mktemp)"

cleanup() {
  rm -f "$TMP" >/dev/null 2>&1 || true
}
trap cleanup EXIT

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 403a - FIX NULL-ASSERTION IM 403 LIVE-SMOKE
============================================================
- Feature-Code aus 403 bleibt UNVERAENDERT
- behebt ausschliesslich eine falsche Test-Assertion im 403 Runtime-Smoke
- Ursache: PHP ?? ersetzt einen vorhandenen null-Wert durch den Fallback
- deshalb wurde korrekt gespeichertes url=null faelschlich als Fehler bewertet
- Remove-Pruefung nutzt jetzt array_key_exists() + direkten === null Vergleich
- danach laeuft der komplette bewaehrte 403 Ablauf erneut:
  Storage-Rechte -> Backup -> Deploy -> Upload -> API -> Replace -> API -> Remove -> API
- Produktions-DB-Smoke bleibt vollstaendig transaktional und wird zurueckgerollt
- keine Migration / keine neue Tabelle / kein Flutter
- main wird NICHT veraendert / KEIN MERGE / KEIN MAIN-PUSH
============================================================
HEAD

git -C "$REPO" fetch origin agent/server-patches --prune
BASE_NOW="$(git -C "$REPO" rev-parse "origin/agent/server-patches:$BASE_PATCH")"
echo "403 Basis-Harness Blob: $BASE_NOW"
[[ "$BASE_NOW" == "$BASE_BLOB" ]] || {
  echo "FEHLER: 403-Harness wurde veraendert: $BASE_NOW (erwartet $BASE_BLOB)" >&2
  exit 2
}

git -C "$REPO" show "origin/agent/server-patches:$BASE_PATCH" > "$TMP"

python3 - "$TMP" <<'PY'
from pathlib import Path
import sys

path = Path(sys.argv[1])
text = path.read_text(encoding="utf-8")

old = """    if (($removed['url'] ?? 'not-null') !== null) {
        smokeFail('Entfernen setzt feed_background.url nicht auf null.');
    }
"""
new = """    if (! array_key_exists('url', $removed) || $removed['url'] !== null) {
        smokeFail('Entfernen setzt feed_background.url nicht auf null.');
    }
"""

if text.count(old) != 1:
    raise SystemExit(f"403a: fehlerhafte Null-Assertion erwartet 1x, gefunden {text.count(old)}x")
text = text.replace(old, new, 1)
text = text.replace('PATCH_NO="403"', 'PATCH_NO="403a"', 1)
text = text.replace('HNT.ROCKS PATCH 403 - REMOTE BACKGROUND UPLOAD PERSISTENCE FIX',
                    'HNT.ROCKS PATCH 403a - REMOTE BACKGROUND UPLOAD PERSISTENCE FIX', 1)
text = text.replace('PATCH 403 ZUSAMMENFASSUNG', 'PATCH 403a ZUSAMMENFASSUNG')
path.write_text(text, encoding="utf-8")
PY

bash -n "$TMP"
grep -Fq "array_key_exists('url', \$removed)" "$TMP" || {
  echo "FEHLER: korrigierte Remove-Null-Pruefung fehlt" >&2
  exit 2
}
if grep -Fq "\$removed['url'] ?? 'not-null'" "$TMP"; then
  echo "FEHLER: alte fehlerhafte Null-Assertion ist noch vorhanden" >&2
  exit 2
fi

echo "403a Harness: Syntax OK / nur Smoke-Assertion korrigiert / Feature-Code unveraendert."
echo

bash "$TMP"
