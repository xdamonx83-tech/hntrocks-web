# Patch 54: Security Headers

Dieser Patch ergänzt bewusst konservative HTTP-Sicherheitsheader über eine Laravel-Middleware.

## Aktivierte Header

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()`
- `Strict-Transport-Security` nur wenn `HH_SECURITY_HSTS_ENABLED=true` und HTTPS aktiv ist

## Bewusst nicht hart aktiviert

Eine erzwingende `Content-Security-Policy` ist in diesem Stand nicht aktiv, weil der aktuelle Blade-/Vikinger-Stand noch mehrere Inline-Skripte, Inline-Styles und eine externe Icon-CSS-Quelle nutzt. Eine harte CSP könnte deshalb Login, Feed, Cups, Modals oder Vikinger-JS brechen.

Für spätere Tests kann eine Report-Only-CSP über `.env` gesetzt werden:

```env
HH_SECURITY_CSP_REPORT_ONLY="default-src 'self'; img-src 'self' data: https:; font-src 'self' data: https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; script-src 'self' 'unsafe-inline'; connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
```

## HSTS

Für Produktion ist in `.env.production.example` HSTS aktiviert. Vorher muss sicher sein:

- hnt.rocks läuft dauerhaft per HTTPS
- keine benötigte Subdomain läuft nur per HTTP
- `includeSubDomains` und `preload` bleiben aus, solange das nicht bewusst geprüft wurde

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
composer dump-autoload
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Test

```bash
curl -I https://hnt.rocks/login
```

Erwartete Header:

- `x-content-type-options: nosniff`
- `x-frame-options: SAMEORIGIN`
- `referrer-policy: strict-origin-when-cross-origin`
- `permissions-policy: ...`
- `strict-transport-security: max-age=31536000` nur wenn HSTS in `.env` aktiv ist
