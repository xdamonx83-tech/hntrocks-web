# Phase 68b – Team Modal Layout Fix

Fixes the Team create/edit modal layout from Phase 68.

Changed:
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

No routes, controllers, Blade structure, migrations, or Composer dependencies changed.

Cause: Vikinger base `.popup-box.mid` and nested `.popup-box-body .popup-box-sidebar/.popup-box-content` rules constrained the modal to the smaller popup layout. This patch hardens width, max-width, sidebar/content columns and scroll behavior only for `.hh-team-modal`.
