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
HNT.ROCKS PATCH 406i - GUIDES API RESTORE / EXPECTED BINDING FIX
============================================================
- setzt den vollstaendigen 406e-Restore erneut direkt von current main auf
- uebernimmt 406f: KEIN interaktiver/verdeckter composer --version Aufruf
- letzter echte 406-Lauf bestaetigte bereits:
  Composer-Autoload OK fuer ApiGuidesController, Guide und GuideWorkflowService
- der verbleibende Fehler ist ausschliesslich der Route-Preflight:
  Laravel route:list liefert fuer Route-Model-Binding {guide:slug}; der Harness erwartete {guide}
- statt den Vergleichscode fragil umzuschreiben, wird NUR der klar begrenzte $checks-Block
  im Preflight an die tatsaechliche Laravel-Ausgabe {guide:slug} angepasst
- echte Routes, Controller und slug-Binding bleiben unveraendert
- vor dem Start wird geprueft, dass der $checks-Block exakt einmal existiert und danach
  KEIN dynamischer Guide-Check mehr die falsche Form {guide} enthaelt
- erst nach komplett gruenem Preflight: Feature-Commit -> Backup -> atomarer Deploy -> Live-Smokes
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

# 406f: moeglicherweise interaktiven/verdeckten Composer-Versionsaufruf entfernen.
old = 'echo "Composer: $(${COMPOSER[@]} --version 2>/dev/null | head -n1)"'
new = 'echo "Composer-Befehl: ${COMPOSER[*]}"'
if s.count(old) != 1:
    raise SystemExit(f"406i: Composer-Versionsanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# Route-Preflight strukturell behandeln: nur den $checks-Arrayblock anpassen.
marker = '$checks = ['
if s.count(marker) != 1:
    raise SystemExit(f"406i: $checks-Block erwartet 1x, gefunden {s.count(marker)}x")
start = s.index(marker)
end = s.find('\n];', start)
if end == -1:
    raise SystemExit('406i: Ende des $checks-Blocks nicht gefunden')
end += len('\n];')
block = s[start:end]

# Sicherstellen, dass wir wirklich den Guide-Routencheck und nicht irgendeinen anderen Arrayblock haben.
required = [
    "['GET|HEAD', 'api/v1/guides']",
    "['GET|HEAD', 'api/v1/guides/drafts/{guide}']",
    "['PATCH', 'api/v1/guides/drafts/{guide}']",
    "['GET|HEAD', 'api/v1/guides/{guide}']",
    "['DELETE', 'api/v1/guides/comments/{comment}']",
]
for needle in required:
    if needle not in block:
        raise SystemExit(f"406i: erwarteter Guide-Check fehlt im $checks-Block: {needle}")

fixed = block.replace('{guide}', '{guide:slug}')
if fixed == block:
    raise SystemExit('406i: keine {guide}-Bindings im $checks-Block ersetzt')
if "guides/drafts/{guide}'" in fixed or "guides/{guide}'" in fixed:
    raise SystemExit('406i: ungebundene {guide}-Erwartung blieb im $checks-Block zurueck')

s = s[:start] + fixed + s[end:]
p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406i-Harness ist syntaktisch ungueltig"
grep -Fq 'Composer-Befehl: ${COMPOSER[*]}' "$TMP" || fail "406f Composer-Fix fehlt"
grep -Fq "['GET|HEAD', 'api/v1/guides/drafts/{guide:slug}']" "$TMP" || fail "Draft-Show Binding-Erwartung fehlt"
grep -Fq "['GET|HEAD', 'api/v1/guides/{guide:slug}']" "$TMP" || fail "Guide-Detail Binding-Erwartung fehlt"
if grep -Fq '${COMPOSER[@]} --version 2>/dev/null' "$TMP"; then fail "alter Composer-Versionsaufruf ist noch vorhanden"; fi
grep -Fq 'COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER[@]}" dump-autoload --no-dev --optimize --no-scripts --no-interaction' "$TMP" || fail "abgesicherter Composer dump-autoload fehlt"

echo "406i Harness: OK"
echo
bash "$TMP"
