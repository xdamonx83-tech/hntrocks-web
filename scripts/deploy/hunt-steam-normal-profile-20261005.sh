#!/usr/bin/env bash
set -euo pipefail

B=/home/users/hunthub/www/hnt.rocks
BR=feature/hunt-platform-connections-20261005
INDEX="$B/public/app/index.html"
JS="$B/public/app/hunt-steam-profile-stats.js"
CSS="$B/public/app/hunt-steam-profile-stats.css"
BACKUP="/home/users/hunthub/backups/hunt-steam-normal-profile-$(date +%Y%m%d-%H%M%S)"
TMP=$(mktemp -d)
ROLLBACK=0

restore() {
  status=$?
  if [ "$status" -ne 0 ] && [ "$ROLLBACK" = 1 ]; then
    cp -p "$BACKUP/index.html" "$INDEX"
    if [ -e "$BACKUP/stats.js" ]; then cp -p "$BACKUP/stats.js" "$JS"; else rm -f "$JS"; fi
    if [ -e "$BACKUP/stats.css" ]; then cp -p "$BACKUP/stats.css" "$CSS"; else rm -f "$CSS"; fi
    echo "ROLLBACK: Alte Profilansicht bleibt erhalten."
  fi
  rm -rf -- "$TMP"
}
trap restore EXIT

test -s "$INDEX" && test -s "$B/public/app/hunt-steam-profile-bridge.js" || {
  echo "STOPP: Live-App oder vorhandene Steam-Bridge fehlt."; exit 1;
}
for bin in git node python3 curl; do
  command -v "$bin" >/dev/null || { echo "STOPP: Werkzeug fehlt: $bin"; exit 1; }
done

# The existing Steam editor works, so this release only adds the read-only
# overview widget. Never rebuild React or change the sidebar bundles.
if grep -q 'hunt-steam-profile-stats.js' "$INDEX"; then
  echo "Normalprofil-Steam-Anzeige ist bereits eingebunden."
  exit 0
fi
curl -fsS --connect-timeout 5 --max-time 12 \
  https://hnt.rocks/api/v1/health >/dev/null || {
  echo "STOPP: API vor Deployment nicht erreichbar."; exit 1;
}
git -C "$B" fetch origin \
  "+refs/heads/$BR:refs/remotes/origin/$BR"
git -C "$B" show "origin/$BR:scripts/deploy/assets/hunt-steam-profile-stats.js" > "$TMP/stats.js"
git -C "$B" show "origin/$BR:scripts/deploy/assets/hunt-steam-profile-stats.css" > "$TMP/stats.css"
node --check "$TMP/stats.js"

python3 - "$INDEX" <<'PY'
from pathlib import Path
import sys
html = Path(sys.argv[1]).read_text(encoding="utf-8")
assert html.count("</head>") == 1 and html.count("</body>") == 1, "STOPP: Index layout unknown"
assert "hunt-steam-profile-bridge.js" in html, "STOPP: Editor-Bridge not present"
assert "hunt-steam-profile-stats" not in html, "STOPP: Already integrated"
PY

mkdir -p -m 700 "$BACKUP"
cp -p "$INDEX" "$BACKUP/index.html"
if [ -e "$JS" ]; then cp -p "$JS" "$BACKUP/stats.js"; fi
if [ -e "$CSS" ]; then cp -p "$CSS" "$BACKUP/stats.css"; fi
ROLLBACK=1

install -m 0644 "$TMP/stats.js" "$JS"
install -m 0644 "$TMP/stats.css" "$CSS"
python3 - "$INDEX" <<'PY'
from pathlib import Path
import sys
p = Path(sys.argv[1])
html = p.read_text(encoding="utf-8")
html = html.replace("</head>",
 '<link rel="stylesheet" href="/app/hunt-steam-profile-stats.css?v=20261005b" />\n</head>',1)
html = html.replace("</body>",
 '<script defer src="/app/hunt-steam-profile-stats.js?v=20261005b"></script>\n</body>',1)
p.write_text(html, encoding="utf-8")
PY

curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/api/v1/health >/dev/null
curl -fsS --connect-timeout 5 --max-time 12 \
  https://hnt.rocks/app/hunt-steam-profile-stats.js -o "$TMP/live.js"
cmp -s "$TMP/live.js" "$JS" || { echo "STOPP: Öffentliche Datei weicht ab."; exit 1; }
grep -q 'hunt-steam-profile-stats.js' "$INDEX"
grep -q 'hunt-steam-profile-bridge.js' "$INDEX"

ROLLBACK=0
echo "HUNT-NORMALPROFIL ERFOLGREICH"
echo "Sidebar und React-Bundles unverändert."
echo "Backup: $BACKUP"
