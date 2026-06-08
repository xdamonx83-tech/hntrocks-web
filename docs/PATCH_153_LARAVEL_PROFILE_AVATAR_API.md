# Patch 153 - Laravel Profil Avatar API

Adds a small authenticated mobile API endpoint for native Android profile avatar upload.

## Route

POST /api/v1/me/avatar

Multipart field:
- avatar: jpg/jpeg/png/webp image

The endpoint uses the existing MediaService, existing moderation/allow checks, existing profile avatar upload limit, deletes the previous avatar file, stores the new asset as public profile_avatar, and returns the same user/counts response shape as /api/v1/me.

No migration. No Composer dependency.
