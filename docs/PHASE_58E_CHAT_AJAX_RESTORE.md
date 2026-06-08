# Phase 58e - Chat AJAX restored

Fix for Phase 58d regression:

- restores JSON response for `messages.store` so the right chat dock can send messages via AJAX again
- restores `read()` JSON endpoint for marking chat conversations as read
- keeps normal `user_notifications` for private message replies disabled
- keeps non-AJAX fallback redirect for the standalone `/messages` page
