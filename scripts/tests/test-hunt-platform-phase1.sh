#!/usr/bin/env bash
set -euo pipefail
LIVE=/home/users/hunthub/www/hnt.rocks
DIR=/home/users/hunthub/worktrees/hnt-hunt-steam-test
BR=origin/feature/hunt-platform-connections-20261005

echo "=== Hunt Steam backend: isolated worktree ==="
test -d "$LIVE/.git" -o -f "$LIVE/.git" || {
  echo "STOPP: Laravel Git checkout not found."; exit 1;
}
test -d "$LIVE/vendor" && test -f "$LIVE/vendor/autoload.php" || {
  echo "STOPP: Composer vendor dependencies unavailable."; exit 1;
}
test ! -e "$DIR" || {
  echo "STOPP: Test worktree already exists; do not remove it blindly."; exit 1;
}

git -C "$LIVE" fetch origin \
  "+refs/heads/feature/hunt-platform-connections-20261005:refs/remotes/origin/feature/hunt-platform-connections-20261005"
mkdir -p "$(dirname "$DIR")"
git -C "$LIVE" worktree add --detach "$DIR" "$BR"
ln -s "$LIVE/vendor" "$DIR/vendor"

echo "=== PHP syntax checks ==="
for f in \
  config/hunt_platform.php \
  database/migrations/2026_10_05_120000_create_hunt_game_connections.php \
  app/Models/HuntGameConnection.php \
  app/Models/HuntGameLinkAttempt.php \
  app/Services/Hunt/SteamOpenIdVerifier.php \
  app/Services/Hunt/SteamHuntStatsProvider.php \
  app/Http/Controllers/Hunt/SteamLinkCallbackController.php \
  app/Http/Controllers/Api/V1/HuntGameAccountsController.php \
  app/Console/Commands/SyncHuntGameConnections.php \
  routes/api-hunt-accounts.php \
  tests/Feature/HuntSteamGameConnectionTest.php \
  tests/bootstrap-hunt-worktree.php
do
  php -l "$DIR/$f"
done

echo "=== Targeted tests with isolated SQLite in-memory ==="
(
  cd "$DIR"
  # No production .env or bootstrap/cache copies.
  test ! -e .env
  APP_ENV=testing \
    DB_CONNECTION=sqlite \
    DB_DATABASE=:memory: \
    SESSION_DRIVER=array \
    CACHE_STORE=array \
    "$DIR/vendor/bin/phpunit" \
      --configuration "$DIR/phpunit.xml" \
      --bootstrap "$DIR/tests/bootstrap-hunt-worktree.php" \
      --filter HuntSteamGameConnectionTest \
      tests/Feature/HuntSteamGameConnectionTest.php
)
echo "HUNT-STEAM-PHASE1 TESTS PASSED"
