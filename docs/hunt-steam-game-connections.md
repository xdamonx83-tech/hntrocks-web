# HNT.ROCKS: Steam Hunt statistics — backend phase 1

This does NOT change HNT login. A user authenticated via an ordinary HNT API bearer token
requests a Steam OpenID URL, opens it in a browser, and returns to the profile editor
after successful Steam-account verification.

## Configuration (not deployed yet)

APP_URL=https://hnt.rocks
HH_HUNT_STEAM_LINK_ENABLED=true
HH_STEAM_WEB_API_KEY=... (existing Steam Web API key, never commit it)

Keep Steam linking disabled until the profile editor and privacy notice are ready.
No Steam OAuth client secret is needed for OpenID itself.

## Authenticated API endpoints

- GET /api/v1/me/game-accounts
- POST /api/v1/me/game-accounts/steam/start => authorize_url, expires_in
- POST /api/v1/me/game-accounts/steam/sync
- DELETE /api/v1/me/game-accounts/steam

Use the normal bearer-token API; navigate to authorize_url in a browser (not HNT login).
Steam callback: /game-accounts/steam/callback?state=...
Uses a one-use 10-minute state tied to the initiating HNT user, then redirects
to /profile/edit?steam_connection=<status>. Always query GET /me/game-accounts
to establish connection status; never trust the URL parameter alone.

## Privacy and data

Only SteamID64, provider display name if available, and Hunt: Showdown 1896
statistics from game app 594650 are stored. Private or absent Steam data become
null (not zero), and errors are stored as generic codes. No other Steam games,
friend lists, emails, OAuth tokens or passwords are queried or persisted.

## Sync

Laravel scheduler calls hnt:hunt:steam-sync hourly, refreshing accounts no more
than once per 24 hours, with a 1-hour failed-call backoff and up to 50 accounts
per run. Existing production schedule:run must already be running.

## Before production

Run scripts/tests/test-hunt-platform-phase1.sh to build a detached test worktree
with no production .env and phpunit.xml's in-memory SQLite DB. Test or review
the Steam callback, private profile, missing API key and rate limits.
Migrate only after tests and code review. React UI is a separate phase.
