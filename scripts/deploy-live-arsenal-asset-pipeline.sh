#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/arsenal-asset-pipeline-20260930"
BASE_HEAD="73b877f6397c854c933aa7f6055e8cac43088e4a"
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
BACKUP="/home/users/hunthub/backups/arsenal-assets-$STAMP"
mkdir -p "$BACKUP"
cp -a app/Http/Controllers/Api/V1/ArsenalController.php "$BACKUP/ArsenalController.php"
cp -a app/Models/EquipmentItem.php "$BACKUP/EquipmentItem.php"
cp -a app/Models/EquipmentSkin.php "$BACKUP/EquipmentSkin.php"
cp -a resources/views/admin/arsenal "$BACKUP/admin-arsenal"

git switch "$EXPECTED_BRANCH"
git merge --ff-only "origin/$EXPECTED_BRANCH"

HEAD="$(git rev-parse HEAD)"
[[ "$HEAD" == "$REMOTE_HEAD" ]] || { echo "ABORT: local HEAD differs from remote"; exit 1; }
git merge-base --is-ancestor "$BASE_HEAD" HEAD || { echo "ABORT: expected Admin Arsenal QA base is absent"; exit 1; }

php -l app/Services/Equipment/EquipmentAssetImportService.php
php -l app/Console/Commands/ImportArsenalFanKitAssets.php
php -l app/Models/EquipmentItem.php
php -l app/Models/EquipmentSkin.php
php -l app/Http/Controllers/Api/V1/ArsenalController.php
php artisan list | grep -q "arsenal:assets:import-fankit"

if [[ ! -L public/storage && ! -d public/storage ]]; then
  echo "NOTICE: public/storage link missing; run php artisan storage:link before publishing imported assets."
fi

echo "ARSENAL ASSET PIPELINE LIVE"
echo "Backup: $BACKUP"
echo "Branch: $(git branch --show-current)"
echo "HEAD: $HEAD"
echo "No assets imported yet."
