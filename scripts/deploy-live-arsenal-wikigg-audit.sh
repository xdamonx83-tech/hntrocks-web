#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/arsenal-wikigg-audit-20261001"
BASE_HEAD="dd7f2890d88cf252588fb64773fa7925cda3d38c"
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
BACKUP="/home/users/hunthub/backups/arsenal-wikigg-audit-$STAMP"
mkdir -p "$BACKUP"
cp -a app/Services/Equipment/WikiGgEquipmentSource.php "$BACKUP/WikiGgEquipmentSource.php"
cp -a app/Services/Equipment/WikiGgMediaImportService.php "$BACKUP/WikiGgMediaImportService.php"
cp -a app/Console/Commands/SyncArsenalWikiGgMedia.php "$BACKUP/SyncArsenalWikiGgMedia.php"

git switch "$EXPECTED_BRANCH"
git merge --ff-only "origin/$EXPECTED_BRANCH"

HEAD="$(git rev-parse HEAD)"
[[ "$HEAD" == "$REMOTE_HEAD" ]] || { echo "ABORT: local HEAD differs from remote"; exit 1; }
git merge-base --is-ancestor "$BASE_HEAD" HEAD || { echo "ABORT: expected wiki.gg family/image base is absent"; exit 1; }

php -l app/Services/Equipment/WikiGgEquipmentSource.php
php -l app/Services/Equipment/WikiGgMediaImportService.php
php -l app/Console/Commands/SyncArsenalWikiGgMedia.php
php -l app/Console/Commands/AuditArsenalWikiGg.php
php artisan list | grep -q "arsenal:wiki-audit"
php artisan list | grep -q "arsenal:wiki-media"

echo "ARSENAL WIKIGG AUDIT LIVE"
echo "Backup: $BACKUP"
echo "Branch: $(git branch --show-current)"
echo "HEAD: $HEAD"
echo "No Arsenal rows changed by deploy."
