#!/usr/bin/env bash
set -Eeuo pipefail

HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
BASE="server-patches/405_fix_guides_api_routes.sh"
TMP="$(mktemp)"

cleanup() { rm -f "$TMP" >/dev/null 2>&1 || true; }
trap cleanup EXIT

fail() { echo "FEHLER: $*" >&2; exit 2; }

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 405a - GUIDES API ROUTES / SAFE SMOKE RUNNER
============================================================
- fuehrt Patch 405 mit korrigierter Rollback-Sicherheit aus
- READ-ONLY PHP-Smokes koennen bei Fehlern nicht mehr per direktem exit den Rollback umgehen
- sonst unveraenderter 405-Ablauf:
  Inventur -> Runtime/DB-Pruefung -> 1-Datei-Feature -> Backup -> Deploy -> Route-Cache -> Live-Smokes
- KEINE Migration / KEINE Demo-Daten / KEIN Flutter
- KEIN MERGE / KEIN MAIN-PUSH
============================================================
HEAD

git -C "$REPO" fetch origin agent/server-patches --prune
git -C "$REPO" show "origin/agent/server-patches:$BASE" > "$TMP"

python3 - "$TMP" <<'PY'
from pathlib import Path
import sys
p=Path(sys.argv[1])
text=p.read_text(encoding='utf-8')

old1="php <<'PHP' || exit 22\n"
new1="if ! php <<'PHP'\n"
if text.count(old1) != 1:
    raise SystemExit(f'405a: Schema-Smoke-Anker erwartet 1x, gefunden {text.count(old1)}')
text=text.replace(old1,new1,1)
old1_end='''PHP
[[ $? -eq 0 ]] || die "Guide-Klassen/DB-Schema nicht vollstaendig; keine Migration wird automatisch ausgefuehrt"
cd "$HUB"
'''
new1_end='''PHP
then
  die "Guide-Klassen/DB-Schema nicht vollstaendig; keine Migration wird automatisch ausgefuehrt"
fi
cd "$HUB"
'''
if text.count(old1_end) != 1:
    raise SystemExit('405a: Schema-Smoke-Endanker nicht eindeutig')
text=text.replace(old1_end,new1_end,1)

old2="php <<'PHP' || exit 23\n"
new2="if ! php <<'PHP'\n"
if text.count(old2) != 1:
    raise SystemExit(f'405a: Controller-Smoke-Anker erwartet 1x, gefunden {text.count(old2)}')
text=text.replace(old2,new2,1)
old2_end='''PHP
[[ $? -eq 0 ]] || die "READ-ONLY Guide-Controller-Smoke fehlgeschlagen"
cd "$HUB"
'''
new2_end='''PHP
then
  die "READ-ONLY Guide-Controller-Smoke fehlgeschlagen"
fi
cd "$HUB"
'''
if text.count(old2_end) != 1:
    raise SystemExit('405a: Controller-Smoke-Endanker nicht eindeutig')
text=text.replace(old2_end,new2_end,1)

p.write_text(text,encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 405-Harness hat Syntaxfehler"
grep -Fq 'if ! php <<'"'"'PHP'"'"'' "$TMP" || fail "safe PHP-Smoke-Guard fehlt"
if grep -Fq "|| exit 23" "$TMP"; then fail "unsicherer Controller-Smoke exit ist noch vorhanden"; fi
if grep -Fq "|| exit 22" "$TMP"; then fail "unsicherer Schema-Smoke exit ist noch vorhanden"; fi

echo "405a Safe-Smoke-Harness: OK"
echo
bash "$TMP"
