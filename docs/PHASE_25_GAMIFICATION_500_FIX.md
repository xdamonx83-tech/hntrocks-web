# Phase 25 – Gamification 500 Fix

Dieser Hotfix korrigiert einen Blade-Syntaxfehler in `resources/views/gamification/index.blade.php`.

Fehlerursache:

```blade
{{ $done ? 'is-done' : ' }}
```

Korrektur:

```blade
{{ $done ? 'is-done' : '' }}
```

Keine Migrationen nötig. Danach `php artisan view:clear` und `php artisan optimize:clear` ausführen.
