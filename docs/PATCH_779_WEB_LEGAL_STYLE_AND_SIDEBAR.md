# Patch 779 — Web: Legal pages style + legal sidebar dropdown

## Ziel
Korrigiert zwei Web-Probleme im aktuellen HNT/Lens-Preview-Design:

1. Das Sidebar-Dropdown „Rechtliches“ öffnete am unteren Rand nach unten und wurde dadurch abgeschnitten.
2. Die Rechts-/Legal-Seiten nutzten zwar das HNT-Preview-Layout, hatten aber keine ausreichenden Legal-spezifischen Styles und wirkten dadurch ungestaltet.

## Geänderte Dateien

- `public/assets/themes/hnt_preview/styles.css`
- `resources/views/themes/hnt_preview/partials/head.blade.php`

## Nicht geändert

- Keine Android-App-Dateien.
- Kein Trophyroom.
- Keine Routen.
- Keine Controller.
- Keine Models.
- Keine Datenbank/Migration.
- Keine Composer-Abhängigkeiten.
- Keine Inhalte der Rechtstexte.

## Details

- Neues Styling für `.hnt-legal-window`, `.hnt-legal-main`, `.hh-legal-shell`, `.hh-legal-layout`, `.hh-legal-article`, `.hh-legal-hero`, `.hh-legal-content`, `.hh-legal-sidebar`, `.hh-legal-nav`.
- Die Legal-Seiten blenden die rechte Widget-Sidebar im Legal-Kontext per CSS aus und nutzen eine saubere zweispaltige Legal-Struktur.
- Das Legal-Dropdown in der linken Sidebar öffnet jetzt nach oben, hat eine maximale Höhe und ist scrollbar.
- CSS-Version wurde auf `?v=779` erhöht.

## Installation

```bash
php artisan view:clear
php artisan optimize:clear

chown -R hunthub:hunthub /home/users/hunthub/www/hnt.rocks
chmod -R 775 storage bootstrap/cache
```

## Testplan

1. `/impressum` öffnen.
2. Prüfen, ob die Seite im HNT/Lens-Stil mit Hero, Card, Textabständen und Legal-Navigation dargestellt wird.
3. `/datenschutz`, `/nutzungsbedingungen`, `/netiquette`, `/account-deletion`, `/child-safety-standards` prüfen.
4. Linke Sidebar unten „Rechtliches“ öffnen.
5. Prüfen, ob das Dropdown nach oben öffnet und alle Einträge erreichbar sind.
6. Mobile Ansicht kurz prüfen.
