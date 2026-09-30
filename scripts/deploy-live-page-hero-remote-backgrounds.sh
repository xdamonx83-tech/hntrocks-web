#!/usr/bin/env bash
set -euo pipefail

APP="/home/users/hunthub/www/hnt.rocks"
SOURCE_BRANCH="feature/page-hero-remote-backgrounds-current-2026-09-30"
DEPLOY_BRANCH="feature/page-hero-remote-backgrounds-live-2026-09-30"

die() {
  echo "ABBRUCH: $*" >&2
  exit 1
}

cd "$APP"

echo "Aktueller Branch: $(git branch --show-current)"
echo "Aktueller HEAD:   $(git rev-parse HEAD)"

# Erlaubt ist aktuell genau eine lokale tracked Änderung:
# public/.htaccess enthält den absichtlich live gesetzten Loadout-Challenges-Rewrite.
tracked_changes="$(git diff --name-only)"
unexpected_tracked="$(printf '%s\n' "$tracked_changes" | sed '/^$/d' | grep -v '^public/\.htaccess$' || true)"

if [[ -n "$unexpected_tracked" ]]; then
  die "Unerwartete tracked Änderungen vorhanden: $unexpected_tracked"
fi

git diff --cached --quiet || die "Gestagte Änderungen im Backend-Checkout vorhanden."

[[ -r .env ]] || die ".env ist für den ausführenden Benutzer nicht lesbar."
runuser -u www-data -- test -r .env || die ".env ist für www-data nicht lesbar. Deploy wird vor Cache-Änderungen gestoppt."

HTACCESS_HASH_BEFORE=""
if [[ -f public/.htaccess ]]; then
  HTACCESS_HASH_BEFORE="$(sha256sum public/.htaccess | cut -d' ' -f1)"
fi

git fetch origin "$SOURCE_BRANCH"

# Diese vier Remote-Config-Dateien müssen dem geprüften aktuellen Live-Stand
# entsprechen. Bei Abweichung wird nichts überschrieben.
declare -A EXPECTED_BLOBS=(
  ["app/Services/AppConfig/AppRemoteConfigService.php"]="7d437cb1bd99209091d68263baf44400f458ae99"
  ["app/Http/Controllers/Admin/AdminAppRemoteConfigController.php"]="0806e03d0c7a2e1a729a376746c0dfd1d94a8c87"
  ["app/Http/Controllers/Api/V1/AppRemoteConfigController.php"]="2510440f0e774f49ce45adc9cd126f128a72d0f3"
  ["resources/views/admin/app-remote-config/index.blade.php"]="7d1ea2de30bbc158627a680ef2ca71dcfb50ab08"
)

for file in "${!EXPECTED_BLOBS[@]}"; do
  current_blob="$(git hash-object "$file")"
  expected_blob="${EXPECTED_BLOBS[$file]}"
  [[ "$current_blob" == "$expected_blob" ]] || die "Datei wurde seit der Inventur geändert: $file ($current_blob statt $expected_blob). Nichts wurde überschrieben."
done

if git show-ref --verify --quiet "refs/heads/$DEPLOY_BRANCH"; then
  die "Lokaler Deploy-Branch $DEPLOY_BRANCH existiert bereits. Erst prüfen oder entfernen."
fi

BACKUP="/home/users/hunthub/backups/page-hero-remote-backgrounds-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"

for file in "${!EXPECTED_BLOBS[@]}"; do
  mkdir -p "$BACKUP/$(dirname "$file")"
  cp -a "$file" "$BACKUP/$file"
done
cp -a routes/api.php "$BACKUP/routes-api.php"
if [[ -f public/.htaccess ]]; then
  cp -a public/.htaccess "$BACKUP/public.htaccess"
fi

git switch -c "$DEPLOY_BRANCH"

for file in "${!EXPECTED_BLOBS[@]}"; do
  git show "origin/$SOURCE_BRANCH:$file" > "$file"
done

mkdir -p tests/Feature
git show "origin/$SOURCE_BRANCH:tests/Feature/AppRemoteHeroBackgroundsTest.php" > tests/Feature/AppRemoteHeroBackgroundsTest.php

python3 - <<'PY'
from pathlib import Path

path = Path("routes/api.php")
text = path.read_text()
route = "    Route::get('/appearance', [AppRemoteConfigController::class, 'appearance'])->name('appearance.show');\n"

if route not in text:
    anchor = "    Route::post('/auth/google/native', [ApiAuthController::class, 'nativeGoogleLogin'])->name('auth.google.native');\n\n"
    if anchor not in text:
        raise SystemExit("ABBRUCH: API-Routenanker wurde nicht gefunden. routes/api.php bleibt ungeändert.")
    text = text.replace(anchor, anchor + route + "\n", 1)
    path.write_text(text)
PY

echo "PHP Syntaxchecks ..."
php -l app/Services/AppConfig/AppRemoteConfigService.php
php -l app/Http/Controllers/Admin/AdminAppRemoteConfigController.php
php -l app/Http/Controllers/Api/V1/AppRemoteConfigController.php
php -l routes/api.php
php -l tests/Feature/AppRemoteHeroBackgroundsTest.php

git diff --check

echo "Feature-Test ..."
if php artisan list --raw 2>/dev/null | grep -qx 'test'; then
  php artisan test --filter=AppRemoteHeroBackgroundsTest
elif [[ -x vendor/bin/phpunit ]]; then
  vendor/bin/phpunit --filter AppRemoteHeroBackgroundsTest
else
  echo "HINWEIS: PHPUnit/Test-Runner ist im Production-Install nicht vorhanden. Feature-Test wird übersprungen."
fi

echo "Route prüfen ..."
php artisan route:list --path=api/v1/appearance

git add \
  app/Services/AppConfig/AppRemoteConfigService.php \
  app/Http/Controllers/Admin/AdminAppRemoteConfigController.php \
  app/Http/Controllers/Api/V1/AppRemoteConfigController.php \
  resources/views/admin/app-remote-config/index.blade.php \
  routes/api.php \
  tests/Feature/AppRemoteHeroBackgroundsTest.php

git commit -m "feat(appearance): manage page hero backgrounds remotely"

if [[ -n "$HTACCESS_HASH_BEFORE" ]]; then
  HTACCESS_HASH_AFTER="$(sha256sum public/.htaccess | cut -d' ' -f1)"
  [[ "$HTACCESS_HASH_BEFORE" == "$HTACCESS_HASH_AFTER" ]] || die "public/.htaccess wurde unerwartet verändert."
fi

echo "Laravel-Caches sauber neu aufbauen ..."
php artisan optimize:clear
php artisan config:cache

CONFIG_DB="$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | tail -n 1 | tr -d '\r')"
[[ -n "$CONFIG_DB" && "$CONFIG_DB" != "laravel" ]] || die "Config-Cache enthält keine gültige Produktionsdatenbank."

echo "Deploy-Branch pushen ..."
git push -u origin "$DEPLOY_BRANCH"

echo
echo "PAGE HERO REMOTE BACKGROUNDS LIVE"
echo "Backup: $BACKUP"
echo "Branch: $(git branch --show-current)"
echo "HEAD:   $(git rev-parse HEAD)"
echo "Admin:  https://hnt.rocks/admin/app-remote-config"
echo "API:    https://hnt.rocks/api/v1/appearance"
