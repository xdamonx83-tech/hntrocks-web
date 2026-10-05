# Hunt-only Xbox / PlayStation integration — prerequisites

**Not deployed. Both platforms stay disabled on production.** Existing
Steam, profile, sidebar, login and statistics are unchanged.

## Microsoft / Xbox (supported identity flow, restricted Xbox data)

Microsoft documents the *Xbox services sign-in for title websites*
flow with Microsoft Entra ID registration, Microsoft account OAuth2,
Xbox User Token and XSTS token exchange.

Official Microsoft documentation:
- https://learn.microsoft.com/en-us/gaming/gdk/docs/services/fundamentals/s2s-auth-calls/service-authentication/live-website-authentication
- https://learn.microsoft.com/en-us/gaming/gdk/docs/services/fundamentals/s2s-auth-calls/s2s-calls/live-title-service-calls-xbox-live
- https://learn.microsoft.com/en-us/gaming/gdk/docs/services/fundamentals/s2s-auth-calls/custom-service-config/web-services/live-web-services

**Important restrictions:**
1. Microsoft's GDK XSTS documentation specifically prohibits storing a
   user's XUID without *express Microsoft approval through a developer
   account manager (DAM)*. A player clicking Connect does not substitute
   for this approval. Never turn on the XUID storage flag before that.
2. Entra application registration is not the same as Xbox Services title
   access. Reading Hunt: Showdown 1896 playtime/achievements can require
   publisher-controlled Xbox services configurations and data access
   permissions. Do not assume that a Microsoft OAuth token grants them.
3. HNT.ROCKS is not the Hunt publisher. Obtain the relevant authorization,
   permissions and accessible, documented Hunt statistics endpoints
   before enabling Xbox statistics.
4. Do not infer zero hours or zero achievements from a 403, empty response
   or inaccessible title data.

### Account registration — only once eligibility is established

- Microsoft Entra **App registrations** > New registration
- Name: HNT.ROCKS Game Account Linking
- Supported accounts: **Personal Microsoft accounts only**
- Platform: **Web**; redirect URL exactly:
  `https://hnt.rocks/game-accounts/xbox/callback`
- Grant appropriate **XboxLive.signin** consent and create client
  credentials. Keep client secret only in server .env.
- Required feature configuration (do not set authorization flags
  without written permissions):

```
HH_HUNT_XBOX_LINK_ENABLED=false
HH_HUNT_XBOX_CLIENT_ID=
HH_HUNT_XBOX_CLIENT_SECRET=
HH_HUNT_XBOX_XUID_STORAGE_AUTHORIZED=false
HH_HUNT_XBOX_HUNT_STATS_AUTHORIZED=false
```

Backend staging routes under normal authenticated HNT bearer tokens:

- POST /api/v1/me/game-accounts/xbox/start
- DELETE /api/v1/me/game-accounts/xbox
- GET /game-accounts/xbox/callback (one-use state, **no HNT login**)

Backend exchanges codes only with fixed official Microsoft/Xbox URLs,
does not store MSA access/refresh or Xbox auth tokens, and refuses to
link when the XUID permission flag is false.

**Current limit:** Xbox identity verification can be tested in CI,
but no approved Hunt statistic feed has been verified. Newly verified
Xbox identities would show null Hunt stats, explicitly not fake values.
**Keep the feature disabled** until actual approved Hunt data access exists.

## Sony / PlayStation

Sony does not offer an unrestricted supported public PSN OAuth,
Hunt playtime and trophies API for third-party community sites.
Access requires an appropriate official agreement/partner interface.

Do **not** collect NPSSO cookies, copied PSN session tokens, account
passwords, or borrow Sony's mobile application's private OAuth client.
Public PSN usernames alone cannot prove ownership.

Sony PlayStation Partners: https://partners.playstation.net/
Official partner information:
https://sonyinteractive.com/en/news/blog/showing-your-game-to-playstation/

Until approved: keep PlayStation marked **Coming soon** and never claim
an unverified PSN username is connected.

## Product contract

All three platforms use a common UI **only for Hunt: Showdown 1896**:
playtime minutes, achieved/total achievements, and status/unavailable
explanations. A platform's unavailable stats remain null; no other
game lists or global platform gamercards are exposed.

Next step: confirm availability of official Xbox and Sony developer
credentials and the exact Hunt title-specific API permissions, then
implement and validate read-only Hunt statistics separately in a test
environment. Do not release the staged branch until these are verified.
