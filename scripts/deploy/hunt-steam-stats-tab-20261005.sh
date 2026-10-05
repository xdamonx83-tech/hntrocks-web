#!/usr/bin/env bash
set -euo pipefail

# Targeted in-place update: no React rebuild, migrations, routes or sidebar.
B=/home/users/hunthub/www/hnt.rocks
BR=feature/hunt-platform-connections-20261005
INDEX="$B/public/app/index.html"
JS="$B/public/app/hunt-steam-profile-stats.js"
CSS="$B/public/app/hunt-steam-profile-stats.css"
BK="/home/users/hunthub/backups/hunt-stats-tab-$(date +%Y%m%d-%H%M%S)"
TMP=$(mktemp -d)
STAGED=0

restore() {
  status=$?
  if [ "$status" -ne 0 ] && [ "$STAGED" -eq 1 ]; then
    cp -p "$BK/index.html" "$INDEX" || :
    cp -p "$BK/stats.js" "$JS" || :
    cp -p "$BK/stats.css" "$CSS" || :
    echo "ROLLBACK: Bestehendes Profil wiederhergestellt."
  fi
  rm -rf -- "$TMP"
}
trap restore EXIT

for tool in git curl python3 node; do
  command -v "$tool" >/dev/null || { echo "STOPP: $tool fehlt."; exit 1; }
done
test -s "$JS" && test -s "$CSS" && test -s "$INDEX" || {
  echo "STOPP: Vorhandene Profil-Dateien fehlen."; exit 1;
}

python3 - "$INDEX" <<'PY'
from pathlib import Path
import sys
s=Path(sys.argv[1]).read_text(encoding='utf-8')
for entry in (
  "/app/hunt-steam-profile-stats.js?v=20261005b",
  "/app/hunt-steam-profile-stats.css?v=20261005b",
  "/app/hunt-steam-profile-bridge.js",
):
  assert s.count(entry)==1, "STOPP: Unbekannter Live-Index-Stand: "+entry
assert "hunt-steam-profile-stats.js?v=20261005c" not in s
PY

# Avoid publishing an unrelated change when the API is not healthy.
curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/api/v1/health >/dev/null
git -C "$B" fetch origin \
  "+refs/heads/$BR:refs/remotes/origin/$BR"
git -C "$B" show "origin/$BR:scripts/deploy/assets/hunt-steam-profile-stats.js" > "$TMP/stats.js"
git -C "$B" show "origin/$BR:scripts/deploy/assets/hunt-steam-profile-stats.css" > "$TMP/stats.css"
node --check "$TMP/stats.js"
grep -q 'hnt-hunt-stats-tab' "$TMP/stats.js"
grep -q 'hnt-hunt-stats-panel' "$TMP/stats.css"

mkdir -p -m 700 "$BK"
cp -p "$INDEX" "$BK/index.html"
cp -p "$JS" "$BK/stats.js"
cp -p "$CSS" "$BK/stats.css"
STAGED=1

install -m 0644 "$TMP/stats.js" "$JS"
install -m 0644 "$TMP/stats.css" "$CSS"
python3 - "$INDEX" <<'PY'
from pathlib import Path
import sys
p=Path(sys.argv[1]); s=p.read_text(encoding='utf-8')
s=s.replace('/app/hunt-steam-profile-stats.js?v=20261005b',
            '/app/hunt-steam-profile-stats.js?v=20261005c',1)
s=s.replace('/app/hunt-steam-profile-stats.css?v=20261005b',
            '/app/hunt-steam-profile-stats.css?v=20261005c',1)
p.write_text(s,encoding='utf-8')
PY

curl -fsS --connect-timeout 5 --max-time 12 \
  'https://hnt.rocks/app/hunt-steam-profile-stats.js?v=20261005c' -o "$TMP/live.js"
curl -fsS --connect-timeout 5 --max-time 12 \
  'https://hnt.rocks/app/hunt-steam-profile-stats.css?v=20261005c' -o "$TMP/live.css"
cmp -s "$JS" "$TMP/live.js"
cmp -s "$CSS" "$TMP/live.css"
curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/api/v1/health >/dev/null

STAGED=0
echo "HUNT-STATS-TAB ERFOLGREICH"
echo "Live-Sidebar, React-Bundles und Profil bearbeiten unverändert."
echo "Backup: $BK"
