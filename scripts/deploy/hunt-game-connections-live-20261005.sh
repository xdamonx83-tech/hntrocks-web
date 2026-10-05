#!/usr/bin/env bash
set -euo pipefail
umask 077
B=/home/users/hunthub/www/hnt.rocks
R=/home/users/hunthub/react/hntrocks-react-redesign
WR=/home/users/hunthub/worktrees/hnt-steam-release-react
BR=feature/hunt-platform-connections-20261005
BK="/home/users/hunthub/backups/hunt-steam-$(date +%Y%m%d-%H%M%S)"
for c in git php python3 npm rsync curl; do
  command -v "$c" >/dev/null || { echo "STOPP: $c fehlt"; exit 1; }
done
test -f "$B/.env" && test -f "$B/public/app/index.html" || {
  echo "STOPP: Live-Pfade fehlen"; exit 1;
}
test ! -e "$WR" || { echo "STOPP: Release-Worktree existiert"; exit 1; }
echo "=== GitHub / React vorbereiten ==="
git -C "$B" fetch origin "+refs/heads/$BR:refs/remotes/origin/$BR"
git -C "$R" fetch origin "+refs/heads/$BR:refs/remotes/origin/$BR"
mkdir -p "$(dirname "$WR")"
git -C "$R" worktree add --detach "$WR" "origin/$BR"
(
  cd "$WR"
  npm ci --ignore-scripts --no-audit --no-fund
  npm run build
)
test -s "$WR/dist/index.html" && test -d "$WR/dist/assets" || {
  echo "STOPP: React-Build fehlt"; exit 1;
}

NEW=(
  config/hunt_platform.php
  database/migrations/2026_10_05_120000_create_hunt_game_connections.php
  app/Models/HuntGameConnection.php
  app/Models/HuntGameLinkAttempt.php
  app/Services/Hunt/SteamOpenIdVerifier.php
  app/Services/Hunt/SteamHuntStatsProvider.php
  app/Http/Controllers/Api/V1/HuntGameAccountsController.php
  app/Http/Controllers/Hunt/SteamLinkCallbackController.php
  app/Console/Commands/SyncHuntGameConnections.php
  routes/api-hunt-accounts.php
)
OLD=(
  routes/api.php
  routes/web.php
  routes/console.php
  app/Http/Controllers/Api/V1/ApiMembersController.php
)
for f in "${NEW[@]}"; do
  test ! -e "$B/$f" || { echo "STOPP: $f existiert bereits"; exit 1; }
done
python3 - "$B" <<'PY'
from pathlib import Path
import sys
b=Path(sys.argv[1])
checks={
 "routes/api.php":["Route::middleware('api.token')->group(function (): void {"],
 "routes/web.php":["Route::get('/auth/{provider}/redirect'"],
 "app/Http/Controllers/Api/V1/ApiMembersController.php":[
   "use App\\Models\\Friendship;",
   "'twitch' => $twitch->statusForUrl($user->profile?->twitch_url),",
 ],
}
for name,markers in checks.items():
 s=(b/name).read_text()
 for marker in markers:
  if s.count(marker)!=1: raise SystemExit("STOPP: Live-Abweichung: "+name)
for name,marker in {
 "routes/api.php":"api-hunt-accounts.php",
 "routes/web.php":"/game-accounts/steam/callback",
 "routes/console.php":"hnt:hunt:steam-sync",
 "app/Http/Controllers/Api/V1/ApiMembersController.php":"'hunt_game_accounts' =>",
}.items():
 if marker in (b/name).read_text(): raise SystemExit("STOPP: Bereits integriert: "+name)
PY

mkdir -p -m 700 "$BK"
for f in "${OLD[@]}" .env public/app/index.html; do
  mkdir -p "$BK/$(dirname "$f")"
  cp -p "$B/$f" "$BK/$f"
done
# On failure, restore only the files this script modifies. Immutable JS
# chunks left behind are inert; old index.html activates previous bundles.
PATCHED=1
rollback() {
  rc=$?
  if [ "$rc" -ne 0 ] && [ "${PATCHED:-0}" = 1 ]; then
    echo "STOPP: Rollback der geaenderten Live-Dateien"
    for f in "${OLD[@]}" .env public/app/index.html; do
      cp -p "$BK/$f" "$B/$f" || :
    done
    for f in "${NEW[@]}"; do rm -f -- "$B/$f"; done
    (cd "$B"; php artisan config:clear >/dev/null 2>&1 || :; php artisan route:clear >/dev/null 2>&1 || :)
  fi
}
trap rollback EXIT

echo "=== Laravel gezielt einspielen ==="
for f in "${NEW[@]}"; do
  mkdir -p "$B/$(dirname "$f")"
  git -C "$B" show "origin/$BR:$f" > "$B/$f"
done
python3 - "$B" <<'PY'
from pathlib import Path
import sys
b=Path(sys.argv[1])
def replace(name,old,new):
 p=b/name; s=p.read_text()
 if s.count(old)!=1: raise SystemExit("STOPP: Ankerabweichung "+name)
 p.write_text(s.replace(old,new,1))
a="Route::middleware('api.token')->group(function (): void {"
replace("routes/api.php",a,a+"\n        require __DIR__.'/api-hunt-accounts.php';")
a="Route::get('/auth/{provider}/redirect'"
w="""// Hunt Steam profile-link callback (not HNT login).
Route::get('/game-accounts/steam/callback', \\App\\Http\\Controllers\\Hunt\\SteamLinkCallbackController::class)
    ->middleware('throttle:30,1')->name('hunt.game.steam.callback');

"""
replace("routes/web.php",a,w+a)
p=b/"routes/console.php"; s=p.read_text()
if "use Illuminate\\Support\\Facades\\Schedule;" not in s:
 s=s.replace("<?php","<?php\n\nuse Illuminate\\Support\\Facades\\Schedule;",1)
p.write_text(s+"\nSchedule::command('hnt:hunt:steam-sync --limit=50')->hourly()->withoutOverlapping();\n")
replace("app/Http/Controllers/Api/V1/ApiMembersController.php",
  "use App\\Models\\Friendship;",
  "use App\\Models\\Friendship;\nuse App\\Models\\HuntGameConnection;")
a="'twitch' => $twitch->statusForUrl($user->profile?->twitch_url),"
bdata="""'hunt_game_accounts' => config('hunt_platform.steam.enabled')
                ? HuntGameConnection::query()->where('user_id', $user->id)->get()
                    ->map(fn (HuntGameConnection $account): array => [
                        'provider' => $account->provider,
                        'provider_name' => $account->provider_name,
                        'hunt' => $account->hunt_stats,
                        'sync_status' => $account->sync_status,
                        'last_synced_at' => $account->last_synced_at?->toIso8601String(),
                    ])->values()->all()
                : [],"""
replace("app/Http/Controllers/Api/V1/ApiMembersController.php",a,a+"\n            "+bdata)
PY
for f in "${NEW[@]}" "${OLD[@]}"; do php -l "$B/$f" >/dev/null; done
echo "=== Migration / API-Pruefung ==="
(
 cd "$B"
 php artisan migrate --force --path=database/migrations/2026_10_05_120000_create_hunt_game_connections.php
 php artisan route:clear
 php artisan config:clear
 php artisan route:list --path=game-accounts --except-vendor
)
# Only enable Steam when a real API key already exists in .env.
if grep -Eq '^HH_STEAM_WEB_API_KEY="?[^"[:space:]]+' "$B/.env"; then
  python3 - "$B/.env" <<'PY'
import sys,re
from pathlib import Path
p=Path(sys.argv[1]); s=p.read_text()
v="HH_HUNT_STEAM_LINK_ENABLED=true"
if re.search(r"^HH_HUNT_STEAM_LINK_ENABLED=",s,re.M):
 s=re.sub(r"^HH_HUNT_STEAM_LINK_ENABLED=.*$",v,s,flags=re.M)
else:
 s=s.rstrip("\n")+"\n"+v+"\n"
p.write_text(s)
PY
  (cd "$B" && php artisan config:clear)
  echo 'STEAM_ENABLED=true'
else
  echo 'STEAM_ENABLED=false (API-Key fehlt; bitte separat konfigurieren)'
fi
echo '=== App-Release (alte Assets bleiben fuer Rollback) ==='
rsync -a "$WR/dist/assets/" "$B/public/app/assets/"
cp -p "$WR/dist/index.html" "$B/public/app/index.html"
curl -fsS --connect-timeout 5 --max-time 12 https://hnt.rocks/api/v1/health >/dev/null
PATCHED=0
echo 'HUNT-STEAM-RELEASE ERFOLGREICH'
echo "Backup: $BK"
