# Patch 509 – Feed Reaction Modal + Picker Cleanup

## Scope
- Web/Laravel Socialite only.
- No Android files.
- No database changes.
- No migration.

## Changes
- Removed the forced red button background from the feed reaction picker emoji buttons.
- Added a clickable reaction count for feed posts.
- Added a compact HNT-styled modal that lists users who reacted to a post.
- Added reaction filter tabs in the modal: all reactions and per reaction type.
- Added localized labels for the modal in German and English.

## Files
- app/Http/Controllers/Feed/FeedReactionController.php
- routes/web.php
- resources/views/themes/socialite/feed/partials/post-card.blade.php
- resources/views/themes/socialite/partials/tail.blade.php
- resources/views/themes/socialite/partials/head.blade.php
- public/assets/socialite/css/hnt-app-palette.css
- resources/lang/de/ui.php
- resources/lang/en/ui.php
