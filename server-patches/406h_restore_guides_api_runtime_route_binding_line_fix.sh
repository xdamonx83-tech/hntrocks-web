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
HNT.ROCKS PATCH 406h - GUIDES API RESTORE / ROBUSTER ROUTE-BINDING-CHECK
============================================================
- setzt den vollstaendigen 406e-Restore erneut auf
- uebernimmt 406f: kein interaktiver/verdeckter composer --version Aufruf
- letzter 406g-Lauf scheiterte nur am eigenen mehrzeiligen Patch-Anker, bevor 406 selbst startete
- die Route-Binding-Normalisierung wird deshalb jetzt an der EINEN exakten Vergleichszeile eingesetzt
- Laravel route:list darf {guide:slug} liefern; fuer den Test wird nur auf {guide} normalisiert
- echte Routes und slug-Binding bleiben unveraendert
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

# 406f: keinen moeglicherweise interaktiven/verdeckten Composer-Versionsaufruf verwenden.
old = 'echo "Composer: $(${COMPOSER[@]} --version 2>/dev/null | head -n1)"'
new = 'echo "Composer-Befehl: ${COMPOSER[*]}"'
if s.count(old) != 1:
    raise SystemExit(f"406h: Composer-Versionsanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406h: bewusst nur die eine Vergleichszeile ersetzen; kein fragiler mehrzeiliger Anker.
old = "        if (($route['method'] ?? '') === $method && ($route['uri'] ?? '') === $uri) {"
new = "        $actualUri = preg_replace('/\\{([^}:]+):[^}]+\\}/', '{$1}', (string) ($route['uri'] ?? ''));\n        if (($route['method'] ?? '') === $method && $actualUri === $uri) {"
if s.count(old) != 1:
    raise SystemExit(f"406h: Route-Vergleichszeile erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406h-Harness ist syntaktisch ungueltig"
grep -Fq 'Composer-Befehl: ${COMPOSER[*]}' "$TMP" || fail "406f Composer-Fix fehlt"
grep -Fq '$actualUri = preg_replace' "$TMP" || fail "Route-Binding-Normalisierung fehlt"
grep -Fq '$actualUri === $uri' "$TMP" || fail "normalisierter Route-Vergleich fehlt"
if grep -Fq '${COMPOSER[@]} --version 2>/dev/null' "$TMP"; then fail "alter Composer-Versionsaufruf ist noch vorhanden"; fi
grep -Fq 'COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER[@]}" dump-autoload --no-dev --optimize --no-scripts --no-interaction' "$TMP" || fail "abgesicherter Composer dump-autoload fehlt"

echo "406h Harness: OK"
echo
bash "$TMP"
