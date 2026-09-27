#!/usr/bin/env bash
set -euo pipefail

ROOT="/home/users/hunthub/www/hnt.rocks"
BASE_HEAD="e7093ab2fbf00a8edf716d23fd3142b835f22cde"
TARGET_BRANCH="feature/news-editor-backend-2026-09-27"
TRANSFER_BRANCH="automation/news-editor-backend-patch-transfer-2026-09-27"
PATCH_PATH="patches/news-editor-backend-2026-09-27.patch"
PATCH_SHA256="6ceaf86a196dc4552393180275c575b2d2cf2adb73efc48248ada80d42318ec3"
WORKTREE="/home/users/hunthub/worktrees/hntrocks-news-editor-backend-2026-09-27"

cd "$ROOT"

if ! git diff --quiet || ! git diff --cached --quiet; then
  echo "ABBRUCH: Im Produktions-Checkout gibt es TRACKED Änderungen."
  git status --short
  exit 1
fi

git fetch origin

git cat-file -e "$BASE_HEAD^{commit}" || {
  echo "ABBRUCH: Basis-Commit fehlt: $BASE_HEAD"
  exit 1
}

if git show-ref --verify --quiet "refs/heads/$TARGET_BRANCH"; then
  echo "ABBRUCH: Lokaler Branch existiert bereits: $TARGET_BRANCH"
  exit 1
fi

if git show-ref --verify --quiet "refs/remotes/origin/$TARGET_BRANCH"; then
  echo "ABBRUCH: Remote-Branch existiert bereits: $TARGET_BRANCH"
  exit 1
fi

if [ -e "$WORKTREE" ]; then
  echo "ABBRUCH: Worktree-Pfad existiert bereits: $WORKTREE"
  exit 1
fi

ACTUAL_PATCH_SHA="$(git show "origin/$TRANSFER_BRANCH:$PATCH_PATH" | sha256sum | awk '{print $1}')"
if [ "$ACTUAL_PATCH_SHA" != "$PATCH_SHA256" ]; then
  echo "ABBRUCH: Patch-Prüfsumme stimmt nicht."
  echo "Erwartet: $PATCH_SHA256"
  echo "Gefunden: $ACTUAL_PATCH_SHA"
  exit 1
fi

mkdir -p "$(dirname "$WORKTREE")"
git worktree add -b "$TARGET_BRANCH" "$WORKTREE" "$BASE_HEAD"

cleanup_failed_am() {
  cd "$WORKTREE" || true
  git am --abort >/dev/null 2>&1 || true
  cd "$ROOT" || true
  git worktree remove --force "$WORKTREE" >/dev/null 2>&1 || true
  git branch -D "$TARGET_BRANCH" >/dev/null 2>&1 || true
}

cd "$WORKTREE"
if ! git show "origin/$TRANSFER_BRANCH:$PATCH_PATH" | git am; then
  echo "ABBRUCH: git am konnte den Codex-Patch nicht anwenden."
  cleanup_failed_am
  exit 1
fi

git diff --check "$BASE_HEAD..HEAD"

echo "Importierter Branch: $(git branch --show-current)"
echo "HEAD: $(git rev-parse HEAD)"
echo "Diff:"
git diff --stat "$BASE_HEAD..HEAD"

git push -u origin "$TARGET_BRANCH"

echo "NEWS BACKEND BRANCH AUF GITHUB"
echo "Branch: $TARGET_BRANCH"
echo "HEAD: $(git rev-parse HEAD)"
echo "Worktree: $WORKTREE"
echo "Production-Checkout wurde nicht umgeschaltet und keine Migration wurde ausgeführt."
