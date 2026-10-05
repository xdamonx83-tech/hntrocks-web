#!/usr/bin/env bash
set -euo pipefail

B=/home/users/hunthub/www/hnt.rocks
BR=feature/hunt-platform-connections-20261005
INDEX="$B/public/app/index.html"
JS="$B/public/app/hunt-steam-profile-bridge.js"
CSS="$B/public/app/hunt-steam-profile-bridge.css"
BACKUP="/home/users/hunthub/backups/hunt-steam-bridge-$(date +%Y%m%d-%H%M%S)"

# No React rebuild, npm, route changes, migrations or other asset changes.
test -f "$INDEX" && test -f "$B/app/Http/Controllers/Hunt/SteamLinkCallbackController.php" || {
  echo "STOPP: Benötigte Live-Dateien fehlen."; exit 1;
}
for bin in git node python3 curl; do
  command -v "$bin" >/dev/null || { echo "STOPP: $bin fehlt."; exit 1; }
done
if grep -q 'hunt-steam-profile-bridge.js' "$INDEX"; then
  echo "Steam-Bridge ist bereits in index.html eingebunden."
  exit 0
fi
curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/api/v1/health >/dev/null || {
  echo "STOPP: Live API ist nicht gesund."; exit 1;
}

# Download only the reviewed files from GitHub to a temporary directory.
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
git -C "$B" fetch origin "+refs/heads/$BR:refs/remotes/origin/$BR"
git -C "$B" show "origin/$BR:scripts/deploy/assets/hunt-steam-profile-bridge.js" > "$TMP/bridge.js"
git -C "$B" show "origin/$BR:scripts/deploy/assets/hunt-steam-profile-bridge.css" > "$TMP/bridge.css"
node --check "$TMP/bridge.js"

python3 - "$INDEX" <<'PY'
from pathlib import Path
import sys
text = Path(sys.argv[1]).read_text(encoding="utf-8")
assert text.count("</head>") == 1, "STOPP: index.html head-Abschluss nicht eindeutig"
assert text.count("</body>") == 1, "STOPP: index.html body-Abschluss nicht eindeutig"
assert 'hunt-steam-profile-bridge' not in text, "STOPP: Bridge bereits eingebunden"
PY

mkdir -p -m 700 "$BACKUP"
cp -p "$INDEX" "$BACKUP/index.html"
if [ -e "$JS" ]; then cp -p "$JS" "$BACKUP/bridge.js"; fi
if [ -e "$CSS" ]; then cp -p "$CSS" "$BACKUP/bridge.css"; fi

ROLLED_OUT=1
rollback() {
  result=$?
  if [ "$result" -ne 0 ] && [ "$ROLLED_OUT" = 1 ]; then
    cp -p "$BACKUP/index.html" "$INDEX"
    if [ -f "$BACKUP/bridge.js" ]; then cp -p "$BACKUP/bridge.js" "$JS"; else rm -f "$JS"; fi
    if [ -f "$BACKUP/bridge.css" ]; then cp -p "$BACKUP/bridge.css" "$CSS"; else rm -f "$CSS"; fi
    echo "ROLLBACK: Frühere Live-index.html wiederhergestellt."
  fi
  rm -rf -- "$TMP"
}
trap rollback EXIT

install -m 0644 "$TMP/bridge.js" "$JS"
install -m 0644 "$TMP/bridge.css" "$CSS"

python3 - "$INDEX" <<'PY'
from pathlib import Path
import sys
p = Path(sys.argv[1])
html = p.read_text(encoding="utf-8")
html = html.replace("</head>", '<link rel="stylesheet" href="/app/hunt-steam-profile-bridge.css?v=20261005" />\n</head>', 1)
html = html.replace("</body>", '<script defer src="/app/hunt-steam-profile-bridge.js?v=20261005"></script>\n</body>', 1)
p.write_text(html, encoding="utf-8")
PY

# Verify the actual published files and API, not just filesystem copies.
curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/api/v1/health >/dev/null
curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/app/hunt-steam-profile-bridge.js -o "$TMP/live.js"
grep -q 'hnt-steam-profile-bridge' "$TMP/live.js"
curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/app/hunt-steam-profile-bridge.css -o "$TMP/live.css"
grep -q 'hnt-steam-bridge' "$TMP/live.css"
grep -q 'hunt-steam-profile-bridge.js' "$INDEX"

ROLLED_OUT=0
echo "STEAM-PROFIL-BRIDGE ERFOLGREICH"
echo "Sidebar und bisherige React-Bundles unverändert."
echo "Backup: $BACKUP"
