# Rework Sidebar V2 Prototype

Static scoped prototype for the existing Rework sidebar.

## Scope

Changed/created files:

- `resources/views/themes/rework/partials/sidebar.blade.php`
- `public/assets/themes/rework/sidebar-v2.css`
- `public/assets/themes/rework/sidebar-v2.js`

## Intent

- Replace only the left sidebar visual structure.
- Keep the existing Rework layout, topbar, right widgets, feed cards, controllers, routes and models untouched.
- Move Profile into the lower account group with Settings and Logout.
- Add expanded/collapsed behavior with localStorage persistence.
- Use a blue active pill for the first visual test, close to the provided sidebar reference.

## Notes

This is a prototype PR. Do not merge until visually checked in browser.
