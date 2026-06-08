# HNT Preview Theme

Dieses Verzeichnis ist der sichere Preview-Layer für das neue HNT.rocks Template.

Regeln:
- Nur Views, die hier bewusst angelegt werden, können im Preview-Modus geladen werden.
- Fehlt eine View, fällt `App\Support\HntTheme::resolve()` zuerst auf das konfigurierte Preview-Fallback-Theme zurück.
- Aktuelles Fallback ist `socialite`, solange nichts anderes in `.env` gesetzt wird.
- Keine Demo-Daten aus den HTML-Referenzen als echte Logik übernehmen.
- Jede Seite wird später einzeln als Blade-View übertragen.

Patch 593 ergänzt nur die isolierte globale Preview-Shell:
- `layouts/app.blade.php`
- Sidebar links / Widgets rechts
- Composer- und Kommentar-Modal als visuelle Shell
- Admin-Testseite `themes.hnt_preview.preview.shell`

Diese Shell ersetzt noch keine echte Produktivseite.
