# Patch 493 — Socialite i18n hardcoded text cleanup v1

## Scope
Small first pass for visible mixed-language strings introduced or exposed around recent Socialite work.

## Changed
- Replaced hardcoded header search/create-dropdown labels with `ui.*` language keys.
- Added missing JS i18n data attributes for chat-tab open/send error fallbacks.
- Replaced hardcoded Cup Feedback admin texts with language keys.
- Replaced several visible hardcoded profile labels from the Socialite profile view with existing/new language keys.
- Added German and English keys to `resources/lang/*/ui.php`.

## Not changed
- No routes.
- No controllers.
- No database/migrations.
- No CSS/layout rewrite.
- No global mass rewrite of every legacy/admin string.
