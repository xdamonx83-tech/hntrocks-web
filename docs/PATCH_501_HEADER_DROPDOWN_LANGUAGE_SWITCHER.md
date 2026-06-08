# Patch 501 - Header Dropdown Language Switcher

Scope: Socialite web header only.

Changes:
- Removed the unused Night mode row from the user dropdown.
- Added a compact DE/EN language switcher in the same position.
- Uses the existing `/language/{locale}` route via `route('locale.switch', ...)`.
- Keeps the selected locale highlighted.
- Translated remaining visible dropdown labels for Security, Privacy, Account, Login and Logout via existing `ui` keys.
- Added small palette CSS for the language pills.
- Bumped `hnt-app-palette.css` cache version to `v=501`.

No routes, controllers, models, migrations or database changes.
