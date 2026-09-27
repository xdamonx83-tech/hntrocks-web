#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="fix/ready-lobby-feedback-spa-route-2026-09-27"
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

BACKUP="/home/users/hunthub/backups/ready-feedback-route-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
cp -a public/.htaccess "$BACKUP/.htaccess"

grep -Fq 'RewriteRule ^ready-lobbies/[^/]+/feedback/?$ app/index.html [L]' public/.htaccess || {
  echo "ABBRUCH: Feedback-Rewrite fehlt."
  exit 1
}

echo "READY LOBBY FEEDBACK ROUTE LIVE"
echo "Backup: $BACKUP"
echo "HEAD: $(git rev-parse HEAD)"
