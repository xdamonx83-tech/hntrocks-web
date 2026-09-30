#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/arsenal-source-image-cache-20260930"
BASE_HEAD="f3e5c57170f2d2e738f63ede7dc9122efd3c0817"
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
BACKUP="/home/users/hunthub/backups/arsenal-source-image-cache-$STAMP"
mkdir -p "$BACKUP"
cp -a app/Http/Controllers/Api/V1/ArsenalController.php "$BACKUP/ArsenalController.php"
cp -a app/Models/EquipmentItem.php "$BACKUP/EquipmentItem.php"

git switch "$EXPECTED_BRANCH"
git merge --ff-only "origin/$EXPECTED_BRANCH"

HEAD="$(git rev-parse HEAD)"
[[ "$HEAD" == "$REMOTE_HEAD" ]] || { echo "ABORT: local HEAD differs from remote"; exit 1; }
git merge-base --is-ancestor "$BASE_HEAD" HEAD || { echo "ABORT: expected Arsenal React route base is absent"; exit 1; }

php -l app/Services/Equipment/EquipmentSourceAssetCacheService.php
php -l app/Console/Commands/CacheArsenalSourceAssets.php
php artisan list | grep -q "arsenal:assets:cache-source"

echo "ARSENAL SOURCE IMAGE CACHE LIVE"
echo "Backup: $BACKUP"
echo "Branch: $(git branch --show-current)"
echo "HEAD: $HEAD"
echo "No images cached yet."
