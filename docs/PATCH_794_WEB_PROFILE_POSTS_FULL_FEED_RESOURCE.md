# Patch 794 Web API - Profile posts return full feed resource

- Profile posts API now returns the same feed post resource shape used by the normal feed.
- This includes full media arrays, author data, viewer flags, poll/gif/shared-post data and media previews when available.
- No routes, migrations or views changed.
