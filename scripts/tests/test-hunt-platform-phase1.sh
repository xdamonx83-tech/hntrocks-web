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
git -C "$LIVE" fetch origin \
  "+refs/heads/feature/hunt-platform-connections-20261005:refs/remotes/origin/feature/hunt-platform-connections-20261005"
mkdir -p "$(dirname "$DIR")"

if [ ! -e "$DIR" ]; then
  git -C "$LIVE" worktree add --detach "$DIR" "$BR"
else
  # Resume only the exact dedicated worktree we created earlier.
  test -f "$DIR/.git" || {
    echo "STOPP: Existing path is not a linked Git worktree."; exit 1;
  }
  test "$(git -C "$DIR" rev-parse --show-toplevel)" = "$DIR" || {
    echo "STOPP: Wrong worktree location."; exit 1;
  }
  test -z "$(git -C "$DIR" status --porcelain)" || {
    echo "STOPP: Worktree has changes; will not overwrite."; exit 1;
  }
  git -C "$DIR" switch --detach "$BR"
fi

# The first test runner linked production vendor read-only in intent.
# Composer must never write through that symlink into the LIVE checkout.
if [ -L "$DIR/vendor" ]; then
  test "$(readlink "$DIR/vendor")" = "$LIVE/vendor" || {
    echo "STOPP: Unexpected vendor symlink."; exit 1;
  }
  rm -- "$DIR/vendor" # Removes only the symlink, NOT live dependencies.
elif [ -e "$DIR/vendor" ] && [ ! -d "$DIR/vendor" ]; then
  echo "STOPP: Unexpected vendor entry."; exit 1
fi

test ! -e "$DIR/.env" || {
  echo "STOPP: Worktree must NOT use any production .env."; exit 1;
}
php -m | grep -iq '^pdo_sqlite
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
 || {
  echo "STOPP: PHP extension pdo_sqlite is missing."; exit 1;
}

if [ ! -x "$DIR/vendor/bin/phpunit" ]; then
  command -v composer >/dev/null || {
    echo "STOPP: Composer not found on server PATH."; exit 1;
  }
  echo "=== Install test dependencies ONLY into worktree ==="
  (
    cd "$DIR"
    APP_ENV=testing \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_DEV=0 \
      composer install --no-interaction --prefer-dist --no-progress \
      --no-scripts --no-plugins
  )
fi
test -x "$DIR/vendor/bin/phpunit" || {
  echo "STOPP: PHPUnit was not installed in test worktree."; exit 1;
}

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
