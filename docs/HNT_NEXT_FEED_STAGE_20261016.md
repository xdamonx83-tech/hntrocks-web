# HNTv3 /next/feed — integration stage (2026-10-16)

## Current state
- Source branch: `feature/hntv3-vite-next-feed-20261016`; **not merged**.
- Source implementation: `routes/next.php`, `app/Http/Controllers/Next/NextFeedController.php`, `resources/views/next/feed.blade.php`, `resources/css/next/feed.css`, `resources/js/next/feed.js`.
- Isolated tooling: `vite.next.config.js`, `npm run build:next`, production output `public/build/next`.
- `routes/web.php` in the branch only adds `require __DIR__ . '/next.php';` at the end.
- Existing `/feed`, all other routes, and old web-facing styles are intentionally unmodified.
- `/next/feed` requires normal Laravel authentication. There is **no additional admin allowlist**, per user request. Its HTML contains `noindex,nofollow,noarchive`.
- New feed is **READ-ONLY** in this first pass. There are no writes to the database; links to original post details can still open the existing live UI.
- The preview delegates to the existing `FeedController::index()` to reuse query, visibility, filters, eager loading and permissions rather than making a second feed data layer.
- The current Blade/CSS is an integration foundation, **not yet a pixel-identical replacement** of the final HNTv3 static HTML. Media viewer, composer actions, comment modal and sharing still need the existing actual modules integrated.

## Diff against production
Live server path: `/home/users/hunthub/www/hnt.rocks`. SSH alias `hnt-codex` logs in as `codex`, with read-only access to Laravel production files; `sudo -n` is not allowed. **Do not escalate, loosen permissions, or overwrite unrelated files.**
Live deployment path contains NO `.git` repo.
Compared immutable GitHub commit `7c3e901eec43cefca01940c3d6853173df7bc65b` with 5 selected live files:
- Exact match: `package.json`, `FeedController.php`, `HntTheme.php`.
- Differences: `composer.json` (GitHub has additional dev tooling) and `routes/web.php` (GitHub has many additional live routes and imports).
**Never copy or check out the branch's entire `routes/web.php` over the server file.** In a privileged deployment, back up server `routes/web.php` and append only the single include for `routes/next.php`. Keep composer and all other live files unchanged. Check `php artisan route:list --path=next` and `php artisan route:list --path=feed` after change.

## Build verification
Isolated SSH stage:
`/home/codex/hnt-next-stage-20261016`.
From commit `22b95c73ebef36149b3450771c546fda597171a7`, copied each new source file to staging and ran:
- `php -l app/Http/Controllers/Next/NextFeedController.php` — pass
- `php -l routes/next.php` — pass
- `npm install --no-audit --no-fund --ignore-scripts` — pass
- `npm run build:next` — pass, Vite 6.4.4, manifest and hashed CSS/JS under `public/build/next`.
The tested lockfile was captured and committed to the branch. It pins the npm build.

## Remaining work before shared access
1. Obtain an authorized **write-capable deployment account** (or have an admin deploy new files without touching old code). `codex` cannot deploy to production.
2. Back up server `routes/web.php`; copy the isolated sources, assets, and one-line include using an authorized deployment method.
3. Build on the server or deploy built assets to `public/build/next`. Verify manifest paths, PHP controller, routes, and **rendered** /next/feed under a logged-in session.
4. Bring in the actual final HNTv3 HTML/CSS/JS from local `HNTv3` previews, replacing the initial shell; integrate real media, comments, share and composer with existing backend endpoints, keeping the legacy site available as fallback.
5. Never merge blindly: reconcile original GitHub/server drift first.

## Relevant HNTv3 references
Local Windows design workspace: `G:\Webseiten C\HNTrocks\Theme\v6\HNTv3`. Follow `HNT_DESIGN_LOCK_V1.md`, `HNT_PROJECT_ORIENTATION_20261016.md`, the original `index.html` and feed component JS/CSS.
