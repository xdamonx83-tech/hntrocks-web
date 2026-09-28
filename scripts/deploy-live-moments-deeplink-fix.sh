#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/moments-deeplink-fix-2026-09-28"
BASE_HEAD="322ea5a981f16882fdea5e0f0c39c5a422ecafd6"
WORKDIR="/home/users/hunthub/www/hnt.rocks"

cd "$WORKDIR"

CURRENT_BRANCH="$(git branch --show-current)"
if [ "$CURRENT_BRANCH" != "$EXPECTED_BRANCH" ]; then
  echo "ABBRUCH: Falscher Branch: $CURRENT_BRANCH"
  exit 1
fi

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "ABBRUCH: Lokale TRACKED Änderungen vorhanden:"
  git status --short
  exit 1
fi

git fetch origin "$EXPECTED_BRANCH"
LOCAL_HEAD="$(git rev-parse HEAD)"
REMOTE_HEAD="$(git rev-parse "origin/$EXPECTED_BRANCH")"

if [ "$LOCAL_HEAD" != "$REMOTE_HEAD" ]; then
  echo "ABBRUCH: Lokaler HEAD ist nicht der aktuelle Remote-HEAD."
  echo "Lokal:  $LOCAL_HEAD"
  echo "Remote: $REMOTE_HEAD"
  exit 1
fi

git diff --check "$BASE_HEAD..HEAD"

while IFS= read -r file; do
  [ -z "$file" ] && continue
  php -l "$file" >/dev/null
done < <(git diff --name-only "$BASE_HEAD..HEAD" -- '*.php')

php artisan about >/dev/null
php artisan route:list --name=moments.show >/dev/null

if [ -x vendor/bin/phpunit ]; then
  php vendor/bin/phpunit tests/Feature/MomentVisibilityTest.php
else
  echo "INFO: PHPUnit ist auf Produktion nicht installiert; Feature-Test wird hier übersprungen."
fi

php artisan view:clear
php artisan route:clear
php artisan cache:clear

echo "MOMENTS DEEPLINK BACKEND LIVE"
echo "HEAD: $(git rev-parse HEAD)"
