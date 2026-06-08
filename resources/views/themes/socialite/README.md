# HNT Socialite Theme

This directory is the optional Socialite theme layer for v2.hnt.rocks.

Rules:
- Keep existing controllers, routes and database schema intact.
- Add Socialite Blade overrides here page by page.
- If a themed view does not exist, `App\Support\HntTheme::resolve()` falls back to the existing Laravel view.
- Do not overwrite production views until a themed page is stable.

Example future path:

```php
return \App\Support\HntTheme::view('feed.index', $data);
```

With `HH_THEME_ENABLED=true` and `HH_THEME=socialite`, Laravel will first look for:

```text
resources/views/themes/socialite/feed/index.blade.php
```

If it is missing, it falls back to:

```text
resources/views/feed/index.blade.php
```
