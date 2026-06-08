# Patch 488 - Feed Internal HNT Links

## Ziel
Interne HNT.rocks-Links im Socialite-Feed sollen klickbar sein und eine lokale Vorschau erhalten. Externe Links bleiben bewusst normaler Text und werden nicht automatisch verlinkt oder geladen.

## Umsetzung
- Neuer zentraler Renderer `App\Support\FeedTextRenderer` für Socialite-Feed-Texte.
- Interne Links werden erkannt:
  - `https://hnt.rocks/...`
  - `https://www.hnt.rocks/...`
  - `hnt.rocks/...`
  - relative interne Pfade wie `/cup-feedback`
- Externe URLs bleiben escaped Text.
- Absolute interne URLs werden als relative Links ausgegeben, z. B. `https://hnt.rocks/cup-feedback` -> `/cup-feedback`.
- Posts bekommen für den ersten internen Link eine lokale Vorschaukarte.
- Kommentare bekommen klickbare interne Links, aber keine Vorschaukarte, damit Kommentar-Threads kompakt bleiben.
- AJAX-Updates für eigene Posts aktualisieren HTML und Vorschaukarte ohne Reload.

## Geänderte Dateien
- `app/Support/FeedTextRenderer.php`
- `app/Http/Controllers/Feed/FeedController.php`
- `app/Http/Controllers/Feed/FeedCommentController.php`
- `app/Http/Controllers/Feed/FeedTranslationController.php`
- `resources/views/themes/socialite/feed/partials/post-card.blade.php`
- `resources/views/themes/socialite/feed/partials/comment.blade.php`
- `resources/views/themes/socialite/partials/tail.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Installation
```bash
composer dump-autoload
php artisan optimize:clear
php artisan view:clear
```

## Test
1. Im Feed einen Beitrag mit `https://hnt.rocks/cup-feedback` erstellen.
2. Prüfen: Link ist klickbar, Vorschaukarte erscheint.
3. Im Feed einen Beitrag mit `https://google.com` erstellen.
4. Prüfen: externer Link bleibt normaler Text, keine Vorschau.
5. Einen bestehenden Beitrag bearbeiten und internen Link ergänzen.
6. Prüfen: Vorschau erscheint ohne Reload.
