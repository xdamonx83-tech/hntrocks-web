# Patch 527b - Minimal Home / SEO-Einstieg

Korrektur zu Patch 527. Die große öffentliche Landingpage wird nicht weiter als Designbasis genutzt.

## Ziel

- `/` bleibt für Gäste indexierbar.
- Eingeloggte Nutzer werden weiterhin in den Feed geleitet.
- Gäste sehen nur eine kompakte, login-nahe Startseite statt einer großen Marketing-Landingpage.
- Die SEO-Grundlage aus Patch 523/526 bleibt erhalten.

## Änderungen

- LandingPageController verschlankt: keine Featured-Cups/Loadouts/Moments mehr, nur einfache öffentliche Kennzahlen.
- Startseiten-View auf das vorhandene Auth-Layout umgestellt.
- Layout ist optisch näher an Login/Register.
- Große Marketing-Landingpage-Struktur wird nicht mehr gerendert.
- `layouts.auth` unterstützt jetzt `@stack('head')`, damit die Startseite WebPage-JSON-LD setzen kann.
- `/` bleibt in der dynamischen Sitemap enthalten.

## Dateien

- app/Http/Controllers/Marketing/LandingPageController.php
- app/Http/Controllers/Seo/SitemapController.php
- resources/views/themes/socialite/landing/index.blade.php
- resources/views/layouts/auth.blade.php
- resources/lang/de/ui.php
- resources/lang/en/ui.php
- routes/web.php
