#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="fix/notifications-infinite-scroll-api-2026-09-26"
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

echo "Branch: $CURRENT_BRANCH"
echo "HEAD: $(git rev-parse HEAD)"

php -l app/Http/Controllers/Api/V1/ApiNotificationController.php
php artisan view:clear
php artisan cache:clear

echo "NOTIFICATIONS API DEPLOY FERTIG"
echo "HEAD: $(git rev-parse HEAD)"
