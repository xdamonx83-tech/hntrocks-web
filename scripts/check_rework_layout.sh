#!/usr/bin/env bash
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
cd "$ROOT"

REWORK_DIR="resources/views/themes/rework"
SIDEBAR_PARTIAL="$REWORK_DIR/partials/sidebar.blade.php"
TOPBAR_PARTIAL="$REWORK_DIR/partials/topbar.blade.php"
RIGHT_WIDGETS_PARTIAL="$REWORK_DIR/partials/right-widgets.blade.php"

fail=0

report_block() {
  local title="$1"
  local output="$2"
  if [ -n "$output" ]; then
    echo
    echo "FAIL: $title"
    echo "$output"
    fail=1
  fi
}

if [ ! -f "$SIDEBAR_PARTIAL" ]; then
  echo "FAIL: missing $SIDEBAR_PARTIAL"
  fail=1
fi

if [ ! -f "$TOPBAR_PARTIAL" ]; then
  echo "FAIL: missing $TOPBAR_PARTIAL"
  fail=1
fi

if [ ! -f "$RIGHT_WIDGETS_PARTIAL" ]; then
  echo "FAIL: missing $RIGHT_WIDGETS_PARTIAL"
  fail=1
fi

sidebar_inline="$(
  grep -R '<aside class="sidebar"' -n "$REWORK_DIR" --include='*.blade.php' 2>/dev/null \
    | grep -v "^$SIDEBAR_PARTIAL:" || true
)"
report_block "page-local Rework sidebar markup found. Use themes.rework.partials.sidebar." "$sidebar_inline"

topbar_inline="$(
  grep -R '<header class="topbar"' -n "$REWORK_DIR" --include='*.blade.php' 2>/dev/null \
    | grep -v "^$TOPBAR_PARTIAL:" || true
)"
report_block "page-local Rework topbar markup found. Use themes.rework.partials.topbar." "$topbar_inline"

right_inline="$(
  grep -R '<aside class="right-col"' -n "$REWORK_DIR" --include='*.blade.php' 2>/dev/null \
    | grep -v "^$RIGHT_WIDGETS_PARTIAL:" || true
)"
report_block "page-local Rework right widget markup found. Use themes.rework.partials.right-widgets." "$right_inline"

while IFS= read -r file; do
  [ -f "$file" ] || continue

  # Only complete page templates with their own document shell are checked here.
  if ! grep -q '<!DOCTYPE html>' "$file"; then
    continue
  fi

  # Maps are the explicit exception, even if they get moved into rework later.
  case "$file" in
    *"/maps/"*) continue ;;
  esac

  if ! grep -q "themes.rework.partials.sidebar" "$file"; then
    echo
    echo "FAIL: $file is a Rework page shell but does not include themes.rework.partials.sidebar"
    fail=1
  fi

  if ! grep -q "themes.rework.partials.topbar" "$file"; then
    echo
    echo "FAIL: $file is a Rework page shell but does not include themes.rework.partials.topbar"
    fail=1
  fi

  if grep -q 'content-grid' "$file" && ! grep -q "themes.rework.partials.right-widgets" "$file"; then
    echo
    echo "FAIL: $file uses content-grid but does not include themes.rework.partials.right-widgets"
    fail=1
  fi
done < <(find "$REWORK_DIR" -type f -name '*.blade.php' | sort)

if [ "$fail" -ne 0 ]; then
  echo
  echo "Rework layout guard failed."
  exit 1
fi

echo "OK: Rework layout guard passed."
