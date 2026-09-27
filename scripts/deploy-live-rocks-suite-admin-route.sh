#!/usr/bin/env bash
set -euo pipefail

TARGET_BRANCH="fix/rocks-suite-routes-admin-live-2026-09-27"
EXPECTED_BASE_BRANCH="feature/admin-functions-live-2026-09-27"
EXPECTED_BASE_HEAD="f04e0dfa07b87cbe50c118e7503a44d276c3f02c"
WORKDIR="/home/users/hunthub/www/hnt.rocks"

cd "$WORKDIR"

CURRENT_BRANCH="$(git branch --show-current)"
CURRENT_HEAD="$(git rev-parse HEAD)"

echo "Current branch: $CURRENT_BRANCH"
echo "Current HEAD: $CURRENT_HEAD"

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "ABBRUCH: Lokale TRACKED Änderungen vorhanden:"
  git status --short
  exit 1
fi

if [ "$CURRENT_BRANCH" != "$TARGET_BRANCH" ]; then
  if [ "$CURRENT_BRANCH" != "$EXPECTED_BASE_BRANCH" ] || [ "$CURRENT_HEAD" != "$EXPECTED_BASE_HEAD" ]; then
    echo "ABBRUCH: Unerwarteter Backend-Stand."
    echo "Erwartet: $EXPECTED_BASE_BRANCH @ $EXPECTED_BASE_HEAD"
    exit 1
  fi
fi

BACKUP="/home/users/hunthub/backups/rocks-suite-admin-route-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -a public/.htaccess "$BACKUP/.htaccess"

if [ "$CURRENT_BRANCH" != "$TARGET_BRANCH" ]; then
  if git show-ref --verify --quiet "refs/heads/$TARGET_BRANCH"; then
    git switch "$TARGET_BRANCH"
  else
    git switch --track -c "$TARGET_BRANCH" "origin/$TARGET_BRANCH"
  fi
fi

git pull --ff-only origin "$TARGET_BRANCH"
git diff --check

grep -Fq 'RewriteRule ^ready-lobbies/[^/]+/feedback/?$ app/index.html [L]' public/.htaccess || {
  echo "ABBRUCH: Ready-Feedback-Rewrite fehlt."
  exit 1
}

grep -Fq 'RewriteRule ^rocks(?:/(?:shop|inventory))?/?$ app/index.html [L]' public/.htaccess || {
  echo "ABBRUCH: Rocks-Rewrite fehlt."
  exit 1
}

echo "ROCKS / SHOP / INVENTORY ROUTES LIVE"
echo "Backup: $BACKUP"
echo "HEAD: $(git rev-parse HEAD)"
