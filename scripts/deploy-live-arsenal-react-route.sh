#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/arsenal-react-route-20260930"
BASE_HEAD="46c8d791c734c63318d435cc13af402886cfa643"
WORKDIR="/home/users/hunthub/www/hnt.rocks"

cd "$WORKDIR"

mapfile -t tracked_changes < <({ git diff --name-only; git diff --cached --name-only; } | sort -u)
unexpected_changes=()
for changed in "${tracked_changes[@]}"; do
  [[ "$changed" == "public/.htaccess" ]] || unexpected_changes+=("$changed")
done
if (( ${#unexpected_changes[@]} )); then
  echo "ABORT: unexpected tracked changes present:"
  printf "  %s\n" "${unexpected_changes[@]}"
  exit 1
fi
if printf "%s\n" "${tracked_changes[@]}" | grep -qx "public/.htaccess"; then
  echo "Preserving known local public/.htaccess change."
fi

git fetch origin "$EXPECTED_BRANCH"
REMOTE_HEAD="$(git rev-parse "origin/$EXPECTED_BRANCH")"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP="/home/users/hunthub/backups/arsenal-react-route-$STAMP"
mkdir -p "$BACKUP"
cp -a routes/web.php "$BACKUP/web.php"

git switch "$EXPECTED_BRANCH"
git merge --ff-only "origin/$EXPECTED_BRANCH"

HEAD="$(git rev-parse HEAD)"
[[ "$HEAD" == "$REMOTE_HEAD" ]] || { echo "ABORT: local HEAD differs from remote"; exit 1; }
git merge-base --is-ancestor "$BASE_HEAD" HEAD || { echo "ABORT: expected Arsenal asset pipeline base is absent"; exit 1; }

php -l routes/web.php
ROUTES_OUTPUT="$(php artisan route:list --path=arsenal)"
printf '%s\n' "$ROUTES_OUTPUT"
printf '%s\n' "$ROUTES_OUTPUT" | grep -q 'arsenal.react.index' || { echo "ABORT: public Arsenal React route is not registered."; exit 1; }

echo "ARSENAL REACT ROUTE LIVE"
echo "Backup: $BACKUP"
echo "Branch: $(git branch --show-current)"
echo "HEAD: $HEAD"
