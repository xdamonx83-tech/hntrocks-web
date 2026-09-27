#!/usr/bin/env bash
set -euo pipefail

APP="/home/users/hunthub/www/hnt.rocks"
SOURCE_BRANCH="feature/admin-functions-2026-09-27"
LIVE_BASE_BRANCH="fix/ready-lobby-feedback-direct-route-live-2026-09-27"
LIVE_BASE_HEAD="315cf929db0937e83d8ff566a74dbfc349c3d8b0"
DEPLOY_BRANCH="feature/admin-functions-live-2026-09-27"
MIGRATION="database/migrations/2026_09_27_000001_create_profile_moderation_tables.php"

die() {
  echo "ABBRUCH: $*" >&2
  exit 1
}

cd "$APP"

CURRENT_BRANCH="$(git branch --show-current)"
CURRENT_HEAD="$(git rev-parse HEAD)"

echo "Aktueller Branch: $CURRENT_BRANCH"
echo "Aktueller HEAD:   $CURRENT_HEAD"

[[ "$CURRENT_BRANCH" == "$LIVE_BASE_BRANCH" ]] || die "Erwartet $LIVE_BASE_BRANCH, gefunden $CURRENT_BRANCH."
[[ "$CURRENT_HEAD" == "$LIVE_BASE_HEAD" ]] || die "Live-HEAD hat sich geändert. Erst aktuellen Stand neu abgleichen."

git diff --quiet || die "Tracked Änderungen im Live-Checkout vorhanden."
git diff --cached --quiet || die "Gestagte Änderungen im Live-Checkout vorhanden."

echo "Hole Admin-Branch ..."
git fetch origin "$SOURCE_BRANCH"

git merge-base --is-ancestor "$CURRENT_HEAD" "origin/$SOURCE_BRANCH"   || die "Admin-Branch basiert nicht mehr auf dem aktuellen Live-HEAD."

echo "Erzeuge separaten Live-Deploy-Branch ..."
if git show-ref --verify --quiet "refs/heads/$DEPLOY_BRANCH"; then
  die "Lokaler Deploy-Branch $DEPLOY_BRANCH existiert bereits. Bitte zuerst prüfen."
fi

git switch -c "$DEPLOY_BRANCH"
git merge --ff-only "origin/$SOURCE_BRANCH"

echo "Syntaxchecks ..."
php -l app/Http/Controllers/Admin/AdminUserController.php
php -l app/Http/Controllers/Admin/AdminProfileModerationController.php
php -l app/Services/ProfileModerationService.php
php -l app/Models/ProfileModerationFlag.php
php -l app/Models/ProfileModerationEvent.php
php -l routes/web.php
php -l "$MIGRATION"

echo "Laravel-Caches leeren ..."
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo "Profilmoderations-Migration ausführen ..."
php artisan migrate --force --path="$MIGRATION"

echo "Admin-Routen prüfen ..."
php artisan route:list --path=profile-moderation

echo "Deploy-Branch zu GitHub pushen ..."
git push -u origin "$DEPLOY_BRANCH"

echo
echo "FERTIG."
echo "Branch: $(git branch --show-current)"
echo "HEAD:   $(git rev-parse HEAD)"
echo "Admin öffnen: https://hnt.rocks/admin"
echo "Profilmoderation: https://hnt.rocks/admin/profile-moderation"
