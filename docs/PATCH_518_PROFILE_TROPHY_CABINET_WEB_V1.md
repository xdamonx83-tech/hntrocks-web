# Patch 518 – Profile Trophy Cabinet Web v1

## Scope
- Web/Laravel only.
- Socialite profile only.
- No database changes.
- No Android changes.

## Changes
- Added a new profile section `/profile/trophies` and `/u/{user}/trophies`.
- Added a Hunter trophy cabinet to the Socialite profile.
- Shows Cup placements, Cup points, bounty tokens, kills, badges and Moments.
- Shows Moment of the Week highlights when present.
- Shows best published Moment fallback when no spotlight exists.
- Added a compact trophy preview card to the profile sidebar.
- Added DE/EN language keys.
- Added HNT palette CSS for the trophy cabinet.

## Install
```bash
php artisan route:clear
php artisan optimize:clear
php artisan view:clear
```

No migration is required for this patch.
