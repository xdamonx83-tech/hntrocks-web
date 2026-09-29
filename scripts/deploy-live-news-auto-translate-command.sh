#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/news-auto-translate-command-2026-09-29"
EXPECTED_BASE="760d78820ef376d47260425f35e6f75d99db277f"
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
  echo "ABBRUCH: Lokaler HEAD ist nicht der Remote-HEAD."
  echo "Lokal:  $LOCAL_HEAD"
  echo "Remote: $REMOTE_HEAD"
  exit 1
fi

if ! git merge-base --is-ancestor "$EXPECTED_BASE" HEAD; then
  echo "ABBRUCH: Erwartete Live-Basis fehlt."
  exit 1
fi

echo "Branch: $CURRENT_BRANCH"
echo "HEAD: $LOCAL_HEAD"

git diff --check "$EXPECTED_BASE..HEAD"
php -l app/Console/Commands/TranslateNewsArticle.php
php artisan about >/dev/null
php artisan list --raw | grep -q '^hnt:news-translate '

php artisan view:clear
php artisan route:clear
php artisan cache:clear

echo "NEWS AUTO TRANSLATE COMMAND LIVE"
echo "HEAD: $(git rev-parse HEAD)"
