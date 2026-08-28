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
HNT.ROCKS PATCH 406g - GUIDES API RESTORE / ROUTE BINDING NORMALIZE
============================================================
- setzt den vollstaendigen 406e-Restore erneut auf
- uebernimmt 406f: kein interaktiver/verdeckter composer --version Aufruf
- letzter Lauf hat den Composer-Autoload erfolgreich rebuilt und alle drei Guide-Klassen geladen
- letzter Abbruch war nur ein Route-Preflight-False-Negative:
  historische Laravel-Routen nutzen Custom Binding {guide:slug}
  der Harness verglich dagegen mit der kanonischen Form {guide}
- Route-Preflight normalisiert deshalb nur fuer den Vergleich {param:binding} -> {param}
- die echten Routen selbst bleiben unveraendert; insbesondere slug-Binding bleibt erhalten
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
    raise SystemExit(f"406g: Composer-Versionsanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

# 406g: Laravel route:list kann Custom Route Model Binding als {guide:slug} ausgeben.
# Der Flutter-/HTTP-Vertrag kennt nur den Platzhalterwert, daher fuer den Harness-Vergleich
# die Binding-Spezifikation entfernen, ohne die echte Route zu veraendern.
old = '''    foreach ($routes as $route) {
        if (($route['method'] ?? '') === $method && ($route['uri'] ?? '') === $uri) {
            $found = true;
            break;
        }
    }
'''
new = '''    foreach ($routes as $route) {
        $actualUri = preg_replace('/\\{([^}:]+):[^}]+\\}/', '{$1}', (string) ($route['uri'] ?? ''));
        if (($route['method'] ?? '') === $method && $actualUri === $uri) {
            $found = true;
            break;
        }
    }
'''
if s.count(old) != 1:
    raise SystemExit(f"406g: Route-Vergleichsanker erwartet 1x, gefunden {s.count(old)}x")
s = s.replace(old, new, 1)

p.write_text(s, encoding='utf-8')
PY

bash -n "$TMP" || fail "korrigierter 406g-Harness ist syntaktisch ungueltig"
grep -Fq 'Composer-Befehl: ${COMPOSER[*]}' "$TMP" || fail "406f Composer-Fix fehlt"
grep -Fq "preg_replace('/\\\\{([^}:]+):[^}]+\\\\}/', '{\$1}'" "$TMP" || fail "Route-Binding-Normalisierung fehlt"
if grep -Fq '${COMPOSER[@]} --version 2>/dev/null' "$TMP"; then fail "alter Composer-Versionsaufruf ist noch vorhanden"; fi
grep -Fq 'COMPOSER_ALLOW_SUPERUSER=1 "${COMPOSER[@]}" dump-autoload --no-dev --optimize --no-scripts --no-interaction' "$TMP" || fail "abgesicherter Composer dump-autoload fehlt"

echo "406g Harness: OK"
echo
bash "$TMP"
