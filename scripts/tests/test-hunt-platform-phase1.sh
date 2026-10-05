#!/usr/bin/env bash
set -euo pipefail

LIVE=/home/users/hunthub/www/hnt.rocks
DIR=/home/users/hunthub/worktrees/hnt-hunt-steam-test
REF=origin/feature/hunt-platform-connections-20261005

echo "=== Hunt Steam: isolated test worktree ==="
test -d "$LIVE/.git" -o -f "$LIVE/.git" || {
  echo "STOPP: Laravel Git checkout fehlt."; exit 1;
}
test -f "$LIVE/vendor/autoload.php" || {
  echo "STOPP: Live-Laravel-Abhaengigkeiten fehlen."; exit 1;
}

git -C "$LIVE" fetch origin \
  "+refs/heads/feature/hunt-platform-connections-20261005:refs/remotes/origin/feature/hunt-platform-connections-20261005"

if [ ! -e "$DIR" ]; then
  mkdir -p "$(dirname "$DIR")"
  git -C "$LIVE" worktree add --detach "$DIR" "$REF"
else
  test -f "$DIR/.git" || {
    echo "STOPP: Vorhandenes Verzeichnis ist kein Git-Worktree."; exit 1;
  }
  test "$(git -C "$DIR" rev-parse --show-toplevel)" = "$DIR" || {
    echo "STOPP: Falscher Test-Worktree."; exit 1;
  }
fi

# An old test runner created this vendor symlink. Unlink only the
# verified symlink; never follow it into the production vendor folder.
if [ -L "$DIR/vendor" ]; then
  test "$(readlink "$DIR/vendor")" = "$LIVE/vendor" || {
    echo "STOPP: Unerwarteter vendor-Symlink."; exit 1;
  }
  unlink "$DIR/vendor"
fi

# No source changes or unexpected untracked files may be overwritten.
test -z "$(git -C "$DIR" status --porcelain)" || {
  echo "STOPP: Ungesicherte Worktree-Aenderungen:"
  git -C "$DIR" status --short
  exit 1
}
git -C "$DIR" switch --detach "$REF"

test ! -e "$DIR/.env" || {
  echo "STOPP: Test-Worktree enthaelt eine .env."; exit 1;
}
# Debian 13 / PHP 8.4: use SQLite only within the test process.
# Download and extract the Debian package; do not install it system-wide.
EXTRA_PHP=()
TEMP_EXT_DIR=""
if ! php -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);'; then
  command -v apt >/dev/null && command -v dpkg-deb >/dev/null || {
    echo "STOPP: apt oder dpkg-deb nicht vorhanden."; exit 1;
  }
  TEMP_EXT_DIR=$(mktemp -d /tmp/hnt-test-sqlite.XXXXXXXX)
  trap '[ -z "$TEMP_EXT_DIR" ] || rm -rf -- "$TEMP_EXT_DIR"' EXIT
  PHP_MINOR=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
  echo "=== SQLite-Modul nur in /tmp herunterladen ==="
  (
    cd "$TEMP_EXT_DIR"
    apt download "php${PHP_MINOR}-sqlite3"
  )
  PKG=$(find "$TEMP_EXT_DIR" -maxdepth 1 -name '*.deb' -type f -print -quit)
  test -n "$PKG" || { echo "STOPP: Kein SQLite-Debianpaket."; exit 1; }
  dpkg-deb -x "$PKG" "$TEMP_EXT_DIR/unpack"
  SQLITE_MODULE=$(find "$TEMP_EXT_DIR/unpack" -name pdo_sqlite.so -type f -print -quit)
  test -f "$SQLITE_MODULE" || { echo "STOPP: SQLite-Modul fehlt."; exit 1; }
  EXTRA_PHP=(-d "extension=$SQLITE_MODULE")
  php "${EXTRA_PHP[@]}" -r 'exit(extension_loaded("pdo_sqlite") ? 0 : 1);' || {
    echo "STOPP: Test-Modul passt nicht zur PHP-Version."; exit 1;
  }
fi
command -v composer >/dev/null || {
  echo "STOPP: Composer fehlt."; exit 1;
}

if [ ! -x "$DIR/vendor/bin/phpunit" ]; then
  echo "=== PHPUnit nur im Test-Worktree installieren ==="
  (
    cd "$DIR"
    COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_DEV=0 \
      composer install --no-interaction --prefer-dist --no-progress \
        --no-scripts --no-plugins
  )
fi
test -x "$DIR/vendor/bin/phpunit" || {
  echo "STOPP: PHPUnit nach Installation nicht gefunden."; exit 1;
}

echo "=== Syntax der neuen PHP-Dateien ==="
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

echo "=== PHPUnit: SQLite nur im Arbeitsspeicher ==="
(
  cd "$DIR"
  test ! -e .env
  APP_ENV=testing \
  DB_CONNECTION=sqlite \
  DB_DATABASE=:memory: \
  SESSION_DRIVER=array \
  CACHE_STORE=array \
    php "${EXTRA_PHP[@]}" "$DIR/vendor/bin/phpunit" \
      --configuration "$DIR/phpunit.xml" \
      --bootstrap "$DIR/tests/bootstrap-hunt-worktree.php" \
      --filter HuntSteamGameConnectionTest \
      tests/Feature/HuntSteamGameConnectionTest.php
)
echo "HUNT-STEAM-PHASE1 TESTS PASSED"
