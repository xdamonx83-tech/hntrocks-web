#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/teams-overview-api-2026-09-26"
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

git diff --check
php -l app/Http/Controllers/Api/V1/ApiTeamsController.php
php -l app/Http/Resources/Api/TeamResource.php

php artisan view:clear
php artisan cache:clear

echo "TEAMS OVERVIEW API DEPLOY FERTIG"
echo "HEAD: $(git rev-parse HEAD)"
