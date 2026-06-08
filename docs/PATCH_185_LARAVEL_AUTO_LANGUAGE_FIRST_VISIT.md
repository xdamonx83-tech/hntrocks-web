# Patch 185 – Laravel Auto Language Detection on First Visit

Laravel-only patch.

## Changed

- `app/Http/Middleware/SetLocale.php`

## Behavior

Locale priority is now:

1. existing session locale
2. existing `locale` cookie
3. first-visit browser detection via `Accept-Language`
4. fallback to English

Browser languages starting with `de` are treated as German:

- `de`
- `de-DE`
- `de-AT`
- `de-CH`
- `de-LI`
- `de-LU`
- other `de-*` variants

All other languages default to English.

The detected locale is stored in session and written to the existing `locale` cookie, so the user is not re-detected on every request. The existing `/language/de` and `/language/en` switcher continues to override the automatic detection.
