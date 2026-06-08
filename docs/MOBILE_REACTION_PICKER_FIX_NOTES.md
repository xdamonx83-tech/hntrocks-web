# Patch 62: Mobile Reaction Picker Fix

Scope: CSS-only hotfix for mobile feed reaction UI.

Changed file:
- `public/assets/vikinger/css/hunthub-vikinger-start.css`

What it fixes:
- The mobile reaction picker no longer stretches into an oversized pill.
- Reaction icons keep compact fixed dimensions on mobile.
- The large white browser focus/tap frame on feed action buttons is suppressed.
- Keyboard focus still gets a smaller red focus-visible ring instead of the intrusive white outline.

No Blade, JS, route, controller or database changes.
No migration required.
