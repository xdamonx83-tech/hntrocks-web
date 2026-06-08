# Patch 92 – Lokale Fonts und lokale Phosphor Icons

Dieser Patch entfernt zur Laufzeit die externen Google-Font-Imports aus `styles.min.css` und ersetzt die externe Phosphor-CDN-Einbindung im Head durch lokale Assets.

## Geänderte/neue Dateien

- `resources/views/partials/head.blade.php`
- `resources/views/partials/vikinger/head.blade.php`
- `public/assets/vikinger/css/styles.min.css`
- `public/assets/vikinger/css/hnt-local-fonts.css`
- `public/assets/vikinger/fonts/phosphor/regular/style.css`
- `scripts/install-local-web-assets.sh`
- `docs/PATCH_92_LOCAL_FONT_ASSETS.md`

## Wichtig

Die Font-Binärdateien sind nicht im Patch-ZIP enthalten. Sie werden einmalig auf dem Server per Script geladen und danach lokal von `hnt.rocks` ausgeliefert.

## Deployment

```bash
cd /home/users/hunthub/www/hnt.rocks
bash scripts/install-local-web-assets.sh
php artisan view:clear
php artisan optimize:clear
chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Danach prüfen

```bash
grep -RInE 'fonts\.googleapis\.com|fonts\.gstatic\.com|cdn\.jsdelivr\.net/npm/@phosphor-icons/web' \
  resources/views/partials/head.blade.php \
  resources/views/partials/vikinger/head.blade.php \
  public/assets/vikinger/css/styles.min.css \
  public/assets/vikinger/css/hnt-local-fonts.css \
  public/assets/vikinger/fonts/phosphor/regular/style.css
```

Wenn der Befehl keine Ausgabe erzeugt, laden die geprüften Laufzeitdateien keine Google Fonts und keine Phosphor-CDN-Datei mehr direkt.
