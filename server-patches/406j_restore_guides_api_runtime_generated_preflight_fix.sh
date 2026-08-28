#!/usr/bin/env bash
set -Eeuo pipefail

HUB="/home/users/hunthub"
REPO="$HUB/www/hnt.rocks"
BASE="server-patches/406e_restore_guides_api_runtime_composer_autoload_rebuild.sh"
BASE_BLOB="4ec6ad5aa58e890d1673f433c17b9029522c0abc"
WRAP="$(mktemp)"

cleanup() { rm -f "$WRAP" >/dev/null 2>&1 || true; }
trap cleanup EXIT

fail() { echo; echo "FEHLER: $*" >&2; exit 2; }

cat <<'HEAD'
============================================================
HNT.ROCKS PATCH 406j - GUIDES API RESTORE / GENERATED PREFLIGHT FIX
============================================================
- beendet die fragilen Wrapper-Patches auf Quelltext, der erst spaeter generiert wird
- setzt direkt auf dem bereits bewaehrten 406e-Harness auf
- entfernt weiterhin den problematischen composer --version Aufruf
- WICHTIG: Route-Binding-Fix wird diesmal ERST NACH der 406e-Generierung direkt im fertigen
  406-Runtime-Script angewendet; dort existiert der $checks-Block tatsaechlich
- nur der Route-Preflight erwartet danach Laravel-konform {guide:slug} statt {guide}
- echte Routes, Controller und slug-Binding bleiben unveraendert
- danach laeuft unveraendert: Autoload -> 19 Routes -> Kernel-Smoke -> Feature-Commit -> Backup
  -> atomarer Deploy -> Composer-Autoload -> Cache -> externe/authentifizierte Live-Smokes
- KEINE Migration / KEINE Demo-Daten / KEIN Flutter
- KEIN MERGE / KEIN MAIN-PUSH
============================================================
HEAD

git -C "$REPO" fetch origin agent/server-patches --prune
NOW="$(git -C "$REPO" rev-parse "origin/agent/server-patches:$BASE")"
echo "406e Basis-Script Blob: $NOW"
[[ "$NOW" == "$BASE_BLOB" ]] || fail "406e Basis-Script wurde veraendert: $NOW (erwartet $BASE_BLOB)"

git -C "$REPO" show "origin/agent/server-patches:$BASE" > "$WRAP"

python3 - "$WRAP" <<'PY'
from pathlib import Path
import sys

p = Path(sys.argv[1])
s = p.read_text(encoding='utf-8')

# 406f: keinen versteckten/interaktiven Composer-Versionsaufruf.
old = 'echo "Composer: $(${COMPOSER[@]} --version 2>/dev/null | head -n1)"'
new = 'echo "Composer-Befehl: ${COMPOSER[*]}"'
if s.count(old) != 1:
    raise SystemExit(f"406j: Composer-Versionsanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# Der eigentliche 406-Runtime-Code wird von 406e erst waehrend der Ausfuehrung in $TMP erzeugt.
# Deshalb Route-Checks NICHT hier am Wrapper suchen, sondern unmittelbar vor dessen finalem Start
# im bereits generierten $TMP korrigieren.
old = '''echo "406e Harness: OK"
echo
bash "$TMP"
'''
new = '''echo "406e Harness: OK"
echo

echo "406j: generierten 406-Route-Preflight auf Laravel Custom Binding ausrichten ..."
python3 - "$TMP" <<'PY406J'
from pathlib import Path
import sys

p = Path(sys.argv[1])
s = p.read_text(encoding='utf-8')
marker = '$checks = ['
if s.count(marker) != 1:
    raise SystemExit(f"406j: generierter $checks-Block erwartet 1x, gefunden {s.count(marker)}x")
start = s.index(marker)
end = s.find('\\n];', start)
if end == -1:
    raise SystemExit('406j: Ende des generierten $checks-Blocks nicht gefunden')
end += len('\\n];')
block = s[start:end]
required = [
    "['GET|HEAD', 'api/v1/guides/drafts/{guide}']",
    "['PATCH', 'api/v1/guides/drafts/{guide}']",
    "['GET|HEAD', 'api/v1/guides/{guide}']",
    "['POST', 'api/v1/guides/{guide}/comments']",
]
for needle in required:
    if needle not in block:
        raise SystemExit(f"406j: erwarteter Route-Check fehlt: {needle}")
fixed = block.replace('{guide}', '{guide:slug}')
if fixed == block:
    raise SystemExit('406j: keine Guide-Binding-Erwartung ersetzt')
if "{guide}'" in fixed or "{guide}/" in fixed:
    raise SystemExit('406j: ungebundene {guide}-Erwartung blieb im Route-Check zurueck')
s = s[:start] + fixed + s[end:]
p.write_text(s, encoding='utf-8')
PY406J

bash -n "$TMP" || fail "generierter 406j-Runtime-Harness ist syntaktisch ungueltig"
grep -Fq "['GET|HEAD', 'api/v1/guides/drafts/{guide:slug}']" "$TMP" || fail "Draft-Route erwartet noch nicht {guide:slug}"
grep -Fq "['GET|HEAD', 'api/v1/guides/{guide:slug}']" "$TMP" || fail "Detail-Route erwartet noch nicht {guide:slug}"
echo "406j Generated-Preflight: OK"
echo
bash "$TMP"
'''
if s.count(old) != 1:
    raise SystemExit(f"406j: finaler 406e-Startanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8')
PY

bash -n "$WRAP" || fail "406j Wrapper ist syntaktisch ungueltig"
grep -Fq '406j: generierten 406-Route-Preflight' "$WRAP" || fail "Generated-Preflight-Schritt fehlt"
grep -Fq 'Composer-Befehl: ${COMPOSER[*]}' "$WRAP" || fail "Composer-Prompt-Fix fehlt"
if grep -Fq '${COMPOSER[@]} --version 2>/dev/null' "$WRAP"; then fail "alter Composer-Versionsaufruf ist noch vorhanden"; fi

echo "406j Wrapper-Harness: OK"
echo
bash "$WRAP"
