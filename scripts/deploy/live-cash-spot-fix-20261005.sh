#!/usr/bin/env bash
set -Eeuo pipefail
umask 027

LIVE=/home/users/hunthub/www/hnt.rocks
ASSETS="$LIVE/public/app/assets"
# Resolve the actual entry and Maps detail chunk from the deployed Vite
# manifest instead of assuming hashes from a previous screenshot.
DETECTED=$(node - "$LIVE/public/app/index.html" "$ASSETS" <<'NODE'
const fs = require('fs');
const path = require('path');
const [htmlFile, assets] = process.argv.slice(2);
const html = fs.readFileSync(htmlFile, 'utf8');
const entry = html.match(/(?:\/app\/)?assets\/(index-[a-zA-Z0-9_-]+\.js)/);
if (!entry) throw Error('STOPP: Kein eindeutiger Vite-Einstieg in app/index.html');
const entryPath = path.join(assets, entry[1]);
const source = fs.readFileSync(entryPath, 'utf8');
const route = './pages/app/design-preview/maps/detail/page.tsx';
const position = source.indexOf(route);
if (position < 0 || source.indexOf(route, position + 1) >= 0) {
  throw Error('STOPP: Aktive Maps-Detailroute nicht eindeutig');
}
const chunk = source.slice(position, position + 800).match(/import\s*\(\s*['"\x60]\.\/(page-[a-zA-Z0-9_-]+\.js)['"\x60]\s*\)/);
if (!chunk) throw Error('STOPP: Maps-Detailchunk nicht auffindbar');
const chunkPath = path.join(assets, chunk[1]);
if (!fs.existsSync(chunkPath)) throw Error('STOPP: Chunk fehlt: ' + chunkPath);
console.log(entryPath);
console.log(chunkPath);
NODE
)
INDEX=$(printf '%s\n' "$DETECTED" | sed -n '1p')
PAGE=$(printf '%s\n' "$DETECTED" | sed -n '2p')
echo "Aktiver React-Einstieg: $(basename "$INDEX")"
echo "Aktives Maps-Modul: $(basename "$PAGE")"
BR=origin/fix/maps-cash-spot-image-description-20261005
BASE=checkpoint/20261005-live-source
MIGRATION=database/migrations/2026_10_05_000001_add_description_to_hnt_map_cash_spot_submissions.php
PATCHER=scripts/deploy/patch-live-cash-spot-assets.cjs
FILES="app/Http/Controllers/Admin/AdminMapController.php app/Http/Controllers/Api/V1/ApiMapsController.php app/Http/Controllers/Api/V1/MapCashSpotSubmissionApiController.php app/Http/Controllers/Maps/MapCashSpotSubmissionController.php app/Models/HntMapCashSpotSubmission.php app/Services/Maps/CashSpotImageOptimizer.php database/migrations/2026_10_05_000001_add_description_to_hnt_map_cash_spot_submissions.php resources/views/admin/maps/cash-spots.blade.php"

test -f "$LIVE/artisan" && test -f "$INDEX" && test -f "$PAGE" || {
  echo "STOPP: Live-Code/JS entspricht nicht dem erwarteten Stand."; exit 1;
}
git -C "$LIVE" rev-parse --verify "$BR^{commit}" >/dev/null

STAGE=$(mktemp -d)
BACKUP="/home/users/hunthub/backups/cashspot-$(date +%Y%m%d-%H%M%S)"
trap 'rm -rf "$STAGE"' EXIT
mkdir -p "$BACKUP"

echo "=== Dateien prüfen und sichern ==="
for F in $FILES; do
  if [ -f "$LIVE/$F" ]; then
    EXPECTED=$(git -C "$LIVE" rev-parse "$BASE:$F")
    ACTUAL=$(git -C "$LIVE" hash-object "$LIVE/$F")
    if [ "$EXPECTED" != "$ACTUAL" ]; then
      echo "STOPP: Live-Datei abweichend: $F"; exit 1
    fi
    mkdir -p "$BACKUP/$(dirname "$F")"
    cp -a "$LIVE/$F" "$BACKUP/$F"
  elif [ "$F" != "$MIGRATION" ] && [ "$F" != app/Services/Maps/CashSpotImageOptimizer.php ]; then
    echo "STOPP: Fehlende Datei: $F"; exit 1
  fi
  mkdir -p "$STAGE/$(dirname "$F")"
  git -C "$LIVE" show "$BR:$F" > "$STAGE/$F"
  if [[ "$F" == *.php && "$F" != *.blade.php ]]; then
    php -l "$STAGE/$F" >/dev/null
  fi
done
cp -a "$INDEX" "$BACKUP/$(basename "$INDEX")"
cp -a "$PAGE" "$BACKUP/$(basename "$PAGE")"
git -C "$LIVE" show "$BR:$PATCHER" > "$STAGE/patcher.cjs"
node --check "$STAGE/patcher.cjs"
node "$STAGE/patcher.cjs" "$INDEX" "$PAGE" "$STAGE/index.js" "$STAGE/page.js"
node --input-type=module --check < "$STAGE/index.js"
node --input-type=module --check < "$STAGE/page.js"

echo "=== Datenbankmigration ==="
mkdir -p "$LIVE/$(dirname "$MIGRATION")"
cp "$STAGE/$MIGRATION" "$LIVE/$MIGRATION"
(cd "$LIVE" && php artisan migrate --force --path="$MIGRATION")

echo "=== Gezielte Veröffentlichung ==="
for F in $FILES; do
  if [ -f "$LIVE/$F" ]; then
    chown --reference="$LIVE/$F" "$STAGE/$F"
    chmod --reference="$LIVE/$F" "$STAGE/$F"
  else
    chmod 0644 "$STAGE/$F"
  fi
done

chown --reference="$INDEX" "$STAGE/index.js"
chmod --reference="$INDEX" "$STAGE/index.js"
chown --reference="$PAGE" "$STAGE/page.js"
chmod --reference="$PAGE" "$STAGE/page.js"

for F in $FILES; do
  mv -f "$STAGE/$F" "$LIVE/$F"
done
mv -f "$STAGE/index.js" "$INDEX"
mv -f "$STAGE/page.js" "$PAGE"
(cd "$LIVE" && php artisan view:clear >/dev/null)

echo
echo "======================================"
echo "CASH-SPOT-FIX LIVE"
echo "Screenshots optimiert, Beschreibung aktiv"
echo "======================================"
echo "Backup: $BACKUP"
echo "Server-GD: $(php -r 'echo extension_loaded("gd") && function_exists("imagewebp") ? "WebP aktiv" : "Browser-WebP-Fallback aktiv";')"
