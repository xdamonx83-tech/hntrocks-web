# Patch 503: Auth Login/Register App Palette

## Ziel
Login und Register optisch an die aktuelle HNT-App/Web-Palette angleichen.

## Änderungen
- Auth-Layout nutzt eigenes HNT-Auth-CSS `hnt-auth-palette.css?v=503`.
- Login/Register linke Formularfläche wird dunkel statt weiß.
- Inputs, Checkboxen, Sprachumschalter, Buttons, Alerts und Social-Login-Buttons wurden auf HNT-Farben angepasst.
- Hero-Bild rechts bleibt bestehen, bekommt aber dunklere Overlay-/Kontrastbehandlung.
- Browser/System-Darkmode wird auch im Auth-Layout blockiert.
- `theme-color` von Weiß auf `#070605` gesetzt.

## Geänderte Dateien
- `resources/views/layouts/auth.blade.php`
- `public/assets/socialite/css/hnt-auth-palette.css`
- `docs/PATCH_503_AUTH_LOGIN_REGISTER_APP_PALETTE.md`

## Installation
```bash
php artisan optimize:clear
php artisan view:clear
```

Danach Browser hart neu laden.
