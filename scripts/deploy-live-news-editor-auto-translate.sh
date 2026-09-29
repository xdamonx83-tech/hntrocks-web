#!/usr/bin/env bash
set -euo pipefail

EXPECTED_BRANCH="feature/news-editor-auto-translate-2026-09-29"
BASE_HEAD="c872c77d267db19b99cee77aad76b8594a4624cb"
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
  echo "ABBRUCH: Lokaler HEAD ist nicht der aktuelle Remote-HEAD."
  echo "Lokal:  $LOCAL_HEAD"
  echo "Remote: $REMOTE_HEAD"
  exit 1
fi

if ! git merge-base --is-ancestor "$BASE_HEAD" HEAD; then
  echo "ABBRUCH: Erwartete Live-Basis fehlt."
  exit 1
fi

echo "Branch: $CURRENT_BRANCH"
echo "HEAD: $LOCAL_HEAD"

git diff --check "$BASE_HEAD..HEAD"

php -l app/Services/Translation/FeedTranslationService.php
php -l app/Services/Translation/NewsArticleTranslationService.php
php -l app/Http/Controllers/Api/V1/NewsArticleAdminController.php
php -l app/Console/Commands/TranslateNewsArticle.php
php -l routes/api.php

php artisan about >/dev/null
php artisan list --raw | grep -q '^hnt:news-translate '
php artisan route:list --path='api/v1/admin/news/articles' | grep -q 'translate'

php artisan view:clear
php artisan route:clear
php artisan cache:clear

echo "NEWS EDITOR AUTO TRANSLATE BACKEND LIVE"
echo "HEAD: $(git rev-parse HEAD)"
