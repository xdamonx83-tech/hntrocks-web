# Patch 502: Lock HNT app palette

- Disables the old Socialite browser/OS dark-mode auto switch.
- Keeps the HNT app palette active regardless of browser/system dark-mode preference.
- Removes the `dark` class at startup and marks the page as `data-hnt-theme=locked`.
- Updates Socialite `script.js` so it no longer writes `light`, `dark`, or system preference values every page load.
- Adds CSS safeguards for native form controls and forced-colors handling.

No routes, controllers, models, migrations, or Android files changed.
