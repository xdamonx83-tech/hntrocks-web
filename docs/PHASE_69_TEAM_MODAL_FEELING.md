# Phase 69 — Team Create/Edit Modal-Feeling

Adds a safer modal-like behaviour to team create/edit without converting routes to a true AJAX modal.

Changed files:
- resources/views/teams/partials/team-form.blade.php
- public/assets/vikinger/js/hunthub-start.js
- public/assets/vikinger/css/hunthub-vikinger-start.css

Notes:
- Body scroll is locked while the team popup page is visible.
- Backdrop click closes to the existing close target.
- Escape closes the page-level modal, but first blurs active form controls.
- No controller, route, model, migration or database changes.
