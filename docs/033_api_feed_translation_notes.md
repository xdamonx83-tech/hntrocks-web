# 033 – API feed translation for mobile app

- Added token-authenticated API routes for feed post/comment translation.
- Added translation meta/source language to API feed post and comment resources.
- Reuses the existing FeedTranslationController and FeedTranslationService; no new translation provider logic was invented.
