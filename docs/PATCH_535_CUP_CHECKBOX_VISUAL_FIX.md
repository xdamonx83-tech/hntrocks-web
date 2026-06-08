# Patch 535 – Cup Checkbox Visual Fix

Fixes Socialite cup form checkboxes that were clickable but did not show a visible active state because template/global input styling hid the native checkbox UI.

Changed files:
- resources/views/cups/partials/cup-form.blade.php
- public/assets/socialite/css/hnt-app-palette.css
- resources/views/themes/socialite/partials/head.blade.php
