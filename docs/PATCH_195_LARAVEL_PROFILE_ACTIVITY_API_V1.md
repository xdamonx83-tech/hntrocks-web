# Patch 195 Laravel – Profil-Aktivität API v1

Adds profile activity previews to the mobile profile summary.

## Affected responses
- `GET /api/v1/me`
- `GET /api/v1/users/{username}`
- profile responses after profile/avatar/cover updates
- public profile responses after friend actions

## New fields under `profile_summary`
- `recent_posts`
- `recent_comments`

## Notes
- No migration.
- No Composer dependency.
- Only published personal feed posts are exposed.
- Public profile responses do not expose private posts/comments.
