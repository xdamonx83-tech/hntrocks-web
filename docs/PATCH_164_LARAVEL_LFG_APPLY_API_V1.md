# Patch 164 - Laravel LFG Apply API v1

Adds a mobile API endpoint for applying to a player LFG post.

## New route

- `POST /api/v1/lfg/{post}/apply`

## Request fields

- `message` optional string, max 800 chars

## Behavior

- Blocks closed/full posts.
- Blocks applying to your own LFG post.
- Blocks duplicate applications.
- Creates a `lfg_applications` row with `pending` status.
- Awards the existing `lfg_application_sent` gamification event.
- Sends the existing LFG application notification to the post owner.
- Returns the updated LFG post as `lfg` and `data`.

## Resource changes

`LfgPostResource` now includes a `viewer` object:

- `is_owner`
- `can_apply`
- `application`

This lets the native Android app show whether the current user can apply or already has a pending/accepted/rejected application.

No migration. No Composer install required.
