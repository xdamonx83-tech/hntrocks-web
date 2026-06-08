# hnt.rocks Launch SEO Files

Patch 53 adds static SEO discovery files under `public/`:

- `public/robots.txt`
- `public/sitemap.xml`

## Current scope

The sitemap intentionally includes only routes that are public in the checked Laravel route state:

- `/`
- `/login`
- `/register`
- `/impressum`
- `/datenschutz`
- `/nutzungsbedingungen`
- `/netiquette`

Most application pages are currently protected by the Laravel `auth` middleware in `routes/web.php` and are therefore excluded from the sitemap and disallowed in `robots.txt`.

## Important note for Cups / Hall of Fame

In the checked route state, `/cups` and `/hall-of-fame` are inside the authenticated route group. They are not included as public sitemap URLs yet.

If Cup pages or the Hall of Fame should become public for launch/SEO, add a separate route/controller patch first and only then add them to the sitemap.

## Deployment check

The webroot must point to Laravel's `public/` directory. After deployment, verify:

- `https://hnt.rocks/robots.txt`
- `https://hnt.rocks/sitemap.xml`
