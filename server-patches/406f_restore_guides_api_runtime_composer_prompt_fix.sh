#!/usr/bin/env bash
set -Eeuo pipefail

HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
BASE="server-patches/406e_restore_guides_api_runtime_composer_autoload_rebuild.sh"
BASE_BLOB="4ec6ad5aa58e890d1673f433c17b9029522c0abc"
TMP="$(mktemp)"

cleanup() { rm -f "$TMP" >/dev/null 2>&1 || true; }
trap cleanup EXIT

fail() { echo; echo "FEHLER: $*" >&2; exit 2; }

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 406f - GUIDES API RESTORE / COMPOSER ROOT-PROMPT FIX
============================================================
- setzt den vollstaendigen 406e-Restore erneut auf
- letzter Lauf hing NICHT beim dump-autoload, sondern bereits bei `composer --version`
- Ursache: Composer lief als root; stderr war fuer den Versionscheck verborgen und ein moeglicher
  Root/Superuser-Prompt war dadurch unsichtbar, waehrend Composer auf Eingabe wartete
- der unnoetige interaktive Versionsaufruf wird komplett entfernt
- dump-autoload bleibt mit COMPOSER_ALLOW_SUPERUSER=1 + --no-interaction abgesichert
- alle bisherigen 406a-e Fixes bleiben erhalten
- weiterhin: kein Deploy vor komplett gruenem Preflight
- KEINE Migration / KEINE Demo-Daten / KEIN Flutter
- KEIN MERGE / KEIN MAIN-PUSH
============================================================
HEAD

git -C "$REPO" fetch origin agent/server-patches --prune
NOW="$(git -C "$REPO" rev-parse "origin/agent/server-patches:$BASE")"
echo "406e Basis-Script Blob: $NOW"
[[ "$NOW" == "$BASE_BLOB" ]] || fail "406e Basis-Script wurde veraendert: $NOW (erwartet $BASE_BLOB)"

git -C "$REPO" show "origin/agent/server-patches:$BASE" > "$TMP"

python3 - "$TMP" <<'PY'
from pathlib import Path
import sys

p = Path(sys.argv[1])
s = p.read_text(encoding='utf-8')
old = 'echo "Composer: $(${COMPOSER[@]} --version 2>/dev/null | head -n1)"'
new = 'echo "Composer-Befehl: ${COMPOSER[*]}"'
if s.count(old) != 1:
    raise SystemExit(f"406f: Composer-Versionsanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)
p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406f-Harness ist syntaktisch ungueltig"
grep -Fq 'Composer-Befehl: ${COMPOSER[*]}' "$TMP" || fail "nicht-interaktive Composer-Ausgabe fehlt"
if grep -Fq '${COMPOSER[@]} --version 2>/dev/null' "$TMP"; then fail "alter versteckter Composer-Versionsaufruf ist noch vorhanden"; fi
grep -Fq 'COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER[@]}" dump-autoload --no-dev --optimize --no-scripts --no-interaction' "$TMP" || fail "abgesicherter Composer dump-autoload fehlt"

echo "406f Harness: OK"
echo
bash "$TMP"
