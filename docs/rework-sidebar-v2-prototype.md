# Rework Sidebar V2 Prototype

Scoped prototype for the existing Rework sidebar, now based on the uploaded functional Figma sidebar HTML/CSS/JS.

## Scope

Changed/created files:

- `resources/views/themes/rework/partials/sidebar.blade.php`
- `public/assets/themes/rework/sidebar-v2.css`
- `public/assets/themes/rework/sidebar-v2-tune.css`
- `public/assets/themes/rework/sidebar-v2.js`
- `docs/rework-sidebar-v2-prototype.md`

## Intent

- Replace only the left sidebar visual structure.
- Keep the existing Rework layout, topbar, right widgets, feed cards, controllers, routes, models, API and Flutter app untouched.
- Use the Figma sidebar behavior: expanded/collapsed, floating glass panel, arrow toggle, submenu/flyout behavior.
- Keep the current demo menu labels for visual tuning.
- Keep Phosphor icons.

## Current tune notes

- The Add New Task CTA is removed for this pass.
- A small tune layer pulls the Rework content closer to the floating sidebar.
- The colors are shifted closer to the darker green/black Figma reference.
- The expanded messages area is styled as a dark card with message rows and an All messages button.
- The collapsed rail keeps only centered icons and message avatars.

## Notes

This is a prototype PR. Do not merge until visually checked in browser.

The sidebar intentionally keeps these Figma baseline values:

- floating 256px expanded panel
- 104px collapsed rail
- 80px backdrop blur
- 28px border radius
