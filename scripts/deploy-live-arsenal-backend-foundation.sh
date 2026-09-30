#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH='feature/arsenal-backend-foundation-20260930'
BASE_HEAD='9fdd4ba019c52138b21b5e8cee6465601ad145d9'
WORKDIR='/home/users/hunthub/www/hnt.rocks'
: "${ARSENAL_REVIEWED_HEAD:?Set ARSENAL_REVIEWED_HEAD to the reviewed 40-character commit hash}"
[[ "$ARSENAL_REVIEWED_HEAD" =~ ^[0-9a-f]{40}$ ]] || { echo 'ABORT: invalid reviewed HEAD'; exit 1; }
cd "$WORKDIR"

branch="$(git branch --show-current)"
head="$(git rev-parse HEAD)"
[[ "$branch" == "$EXPECTED_BRANCH" ]] || { echo "ABORT: unexpected branch: $branch"; exit 1; }
git diff --quiet && git diff --cached --quiet || { echo 'ABORT: tracked changes present'; git status --short; exit 1; }
git fetch origin "$EXPECTED_BRANCH"
remote_head="$(git rev-parse "origin/$EXPECTED_BRANCH")"
[[ "$head" == "$remote_head" ]] || { echo "ABORT: HEAD differs from reviewed remote branch ($head vs $remote_head)"; exit 1; }
[[ "$head" == "$ARSENAL_REVIEWED_HEAD" ]] || { echo "ABORT: HEAD differs from approved commit ($head vs $ARSENAL_REVIEWED_HEAD)"; exit 1; }
git merge-base --is-ancestor "$BASE_HEAD" HEAD || { echo 'ABORT: expected backend base is absent'; exit 1; }
[[ -f .env ]] || { echo 'ABORT: .env missing'; exit 1; }
[[ "$(stat -c '%a %U %G' .env)" == '640 hunthub www-data' ]] || { echo 'ABORT: .env ownership or mode differs from expected'; exit 1; }

git diff --check "$BASE_HEAD" HEAD
php -l database/migrations/2026_09_30_120000_create_equipment_tables.php
php -l app/Services/Equipment/EquipmentSyncService.php
php -l app/Http/Controllers/Api/V1/ArsenalController.php
if php -m | grep -qi '^pdo_sqlite$' && [[ -x vendor/bin/phpunit ]]; then
  vendor/bin/phpunit tests/Feature/ArsenalFoundationTest.php tests/Unit/EquipmentSourceTest.php
else
  [[ "${ARSENAL_TESTS_VERIFIED_HEAD:-}" == "$head" ]] || {
    echo 'ABORT: local SQLite tests unavailable; set ARSENAL_TESTS_VERIFIED_HEAD only after external tests passed for this exact HEAD.'
    exit 1
  }
  echo "Integration tests externally verified for $head"
fi

echo "Branch: $branch"
echo "HEAD: $head"
echo 'Migration preview:'
php artisan migrate --pretend --force
echo 'Applying additive Arsenal migration:'
php artisan migrate --force
echo 'Arsenal backend foundation deployed. Run arsenal:sync --dry-run after data review.'
