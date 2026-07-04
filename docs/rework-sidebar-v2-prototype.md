# Rework Sidebar V2 Prototype

Scoped prototype for the existing Rework sidebar, now based on the uploaded functional Figma sidebar HTML/CSS/JS.

## Scope

Changed/created files:

- `resources/views/themes/rework/partials/sidebar.blade.php`
- `public/assets/themes/rework/sidebar-v2.css`
- `public/assets/themes/rework/sidebar-v2.js`
- `docs/rework-sidebar-v2-prototype.md`

## Intent

- Replace only the left sidebar visual structure.
- Keep the existing Rework layout, topbar, right widgets, feed cards, controllers, routes, models, API and Flutter app untouched.
- Use the Figma sidebar behavior: expanded/collapsed, floating glass panel, arrow toggle, submenu/flyout behavior.
- Keep HNT navigation labels and Phosphor icons.
- Move Profile into the lower account group with Settings and Logout.

## Notes

This is a prototype PR. Do not merge until visually checked in browser.

The sidebar intentionally uses the Figma values as the visual baseline:

- floating 256px expanded panel
- 104px collapsed rail
- `rgba(15, 9, 12, .56)` glass background
- 80px backdrop blur
- 28px border radius
- orange/dark shadow glow
