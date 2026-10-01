#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/arsenal-wikigg-media-prototype-20261001"
BASE_HEAD="8e4b06ba309a6844f827bcf9d9b89a0849f64467"
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
BACKUP="/home/users/hunthub/backups/arsenal-wikigg-media-$STAMP"
mkdir -p "$BACKUP"
cp -a app/Http/Controllers/Api/V1/ArsenalController.php "$BACKUP/ArsenalController.php"
cp -a app/Models/EquipmentSkin.php "$BACKUP/EquipmentSkin.php"

git switch "$EXPECTED_BRANCH"
git merge --ff-only "origin/$EXPECTED_BRANCH"

HEAD="$(git rev-parse HEAD)"
[[ "$HEAD" == "$REMOTE_HEAD" ]] || { echo "ABORT: local HEAD differs from remote"; exit 1; }
git merge-base --is-ancestor "$BASE_HEAD" HEAD || { echo "ABORT: expected wiki.gg prototype base is absent"; exit 1; }

php -l app/Services/Equipment/WikiGgEquipmentSource.php
php -l app/Services/Equipment/WikiGgMediaImportService.php
php -l app/Console/Commands/SyncArsenalWikiGgMedia.php
php -l app/Http/Controllers/Api/V1/ArsenalController.php
php -l app/Models/EquipmentSkin.php
php -l database/migrations/2026_10_01_150000_add_facts_to_equipment_skins.php
php artisan list | grep -q "arsenal:wiki-media"

echo "Migration preview:"
php artisan migrate --pretend --path=database/migrations/2026_10_01_150000_add_facts_to_equipment_skins.php
echo "Applying additive skin facts migration:"
php artisan migrate --force --path=database/migrations/2026_10_01_150000_add_facts_to_equipment_skins.php

echo "ARSENAL WIKIGG MEDIA PROTOTYPE LIVE"
echo "Backup: $BACKUP"
echo "Branch: $(git branch --show-current)"
echo "HEAD: $HEAD"
echo "No wiki.gg media imported yet."
