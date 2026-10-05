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
  # Resume only the exact known test worktree.
  test -f "$DIR/.git" || {
    echo "STOPP: Existing location is not a linked Git worktree."; exit 1;
  }
  test "$(git -C "$DIR" rev-parse --show-toplevel)" = "$DIR" || {
    echo "STOPP: Incorrect worktree location."; exit 1;
  }
fi

# The first runner left ONLY a vendor symlink to production. Remove the
# symlink (never follow it) before checking for dirty Git files or using
# Composer. Existing independently installed test dependencies are retained.
if [ -L "$DIR/vendor" ]; then
  test "$(readlink "$DIR/vendor")" = "$LIVE/vendor" || {
    echo "STOPP: Unexpected vendor symlink."; exit 1;
  }
  unlink "$DIR/vendor"
elif [ -e "$DIR/vendor" ] && [ ! -d "$DIR/vendor" ]; then
  echo "STOPP: Unexpected vendor path."; exit 1
fi

# Never overwrite modified source. An existing worktree-local vendor/
# directory is expected after Composer installs test dependencies.
test -z "$(git -C "$DIR" status --porcelain --untracked-files=normal | grep -Ev '^\?\? vendor/

test ! -e "$DIR/.env" || {
  echo "STOPP: No production .env allowed in test worktree."; exit 1;
}
php -m | grep -iq '^pdo_sqlite$' || {
  echo "STOPP: PHP extension pdo_sqlite is missing."; exit 1;
}
if [ ! -x "$DIR/vendor/bin/phpunit" ]; then
  command -v composer >/dev/null || {
    echo "STOPP: Composer not on PATH."; exit 1;
  }
  echo "=== Install DEV dependencies in isolated worktree only ==="
  (
    cd "$DIR"
    COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_DEV=0 \
      composer install --no-interaction --prefer-dist --no-progress \
        --no-scripts --no-plugins
  )
fi
test -x "$DIR/vendor/bin/phpunit" || {
  echo "STOPP: Worktree PHPUnit still unavailable."; exit 1;
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
 || true)" || {
  echo "STOPP: Worktree has additional changes:"
  git -C "$DIR" status --short
  exit 1
}
git -C "$DIR" switch --detach "$BR"

test ! -e "$DIR/.env" || {
  echo "STOPP: No production .env allowed in test worktree."; exit 1;
}
php -m | grep -iq '^pdo_sqlite$' || {
  echo "STOPP: PHP extension pdo_sqlite is missing."; exit 1;
}
if [ ! -x "$DIR/vendor/bin/phpunit" ]; then
  command -v composer >/dev/null || {
    echo "STOPP: Composer not on PATH."; exit 1;
  }
  echo "=== Install DEV dependencies in isolated worktree only ==="
  (
    cd "$DIR"
    COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_DEV=0 \
      composer install --no-interaction --prefer-dist --no-progress \
        --no-scripts --no-plugins
  )
fi
test -x "$DIR/vendor/bin/phpunit" || {
  echo "STOPP: Worktree PHPUnit still unavailable."; exit 1;
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
