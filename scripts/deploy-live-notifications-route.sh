#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="fix/notifications-react-route-2026-09-26"
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

BACKUP="/home/users/hunthub/backups/notifications-route-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -a public/.htaccess "$BACKUP/.htaccess"

php artisan view:clear
php artisan cache:clear

echo "NOTIFICATIONS ROUTING LIVE"
echo "Backup: $BACKUP"
echo "HEAD: $(git rev-parse HEAD)"
