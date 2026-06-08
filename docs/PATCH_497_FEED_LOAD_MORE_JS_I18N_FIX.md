# Patch 497 - Feed Load More JS i18n Fix

## Scope
- Web/Laravel Socialite only.
- Fixes the feed "Mehr laden" button after the i18n cleanup.
- Removes one legacy Socialite template JS console error caused by missing demo-only file inputs.

## Changes
- Exposes the existing Socialite i18n labels from the composer script as `window.socialiteI18n`.
- Makes the feed load-more script read `loading` and `tryAgain` from `window.socialiteI18n` with safe fallbacks.
- Guards legacy upload listeners in `public/assets/socialite/js/script.js` so missing demo elements do not throw `addEventListener` errors.

## Not changed
- No routes.
- No controllers.
- No database.
- No migrations.
- No Android files.
