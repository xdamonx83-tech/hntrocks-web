# Patch 191 Laravel - Public Profile API v1

Adds a mobile API endpoint for public user profiles:

- GET /api/v1/users/{user:username}

Returns:

- user
- profile_summary with progress, counts, badges, quests, friends preview and teams preview
- viewer state with friendship status placeholders

No migrations and no composer install required.
