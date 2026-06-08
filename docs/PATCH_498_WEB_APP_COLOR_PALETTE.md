# Patch 498 – Web App Color Palette

Ziel: Socialite-Webseiten optisch näher an die native Android-App bringen, ohne Routen, Controller, Models, Datenbank oder alte Vikinger-Dateien zu ändern.

Android-Palette aus `HntTheme.kt`:

- `HntBg`: `#070605`
- `HntSurface`: `#12100D`
- `HntSurface2`: `#1C1712`
- `HntText`: `#F2ECE1`
- `HntMuted`: `#A79A8A`
- `HntGold`: `#E0B45A`
- `HntRed`: `#B83B32`

Geändert:

- Neue Socialite-CSS-Datei: `public/assets/socialite/css/hnt-app-palette.css`
- Socialite-Layout bekommt Body-Scope `hh-hnt-web-palette`
- Socialite-Head lädt die neue Palette nach `style.css`

Bewusst nicht geändert:

- Keine Laravel-Logik
- Keine API
- Keine Android-Dateien
- Keine Vikinger-Views
- Keine Tailwind-Builds
- Keine Migration
