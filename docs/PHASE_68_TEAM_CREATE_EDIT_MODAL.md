# Phase 68 – Team Create/Edit as Vikinger Modal

Basis: latest accepted Laravel state through Phase 67.

Changed:
- resources/views/teams/create.blade.php
- resources/views/teams/edit.blade.php
- resources/views/teams/partials/team-form.blade.php
- resources/lang/de/ui.php
- resources/lang/en/ui.php
- public/assets/vikinger/js/hunthub-start.js
- public/assets/vikinger/css/hunthub-vikinger-start.css

Notes:
- Team create/edit now render as a large Vikinger-style manage-group modal instead of a normal Account Hub page.
- Existing routes, controller logic and database schema are unchanged.
- The form still uses the existing team store/update endpoints.
- Modal sidebar tabs switch between Team Info, Avatar/Cover, Settings, Members and Delete Team when available.
- Avatar/cover previews and live name/tagline preview from Phase 67 remain intact.
