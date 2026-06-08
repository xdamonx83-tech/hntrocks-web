# Patch 531 – Öffentliches Profil 500-Fix

## Problem

Fremde/öffentliche Profilseiten wie `/u/{username}` konnten mit HTTP 500 abbrechen.
Der Laravel-Log zeigte:

`Undefined variable $request` in `app/Http/Controllers/Profile/ProfileController.php` innerhalb der `visible_feed_posts_count`-`loadCount`-Closure.

## Ursache

Die Closure nutzt `$request->user()`, hatte aber nur `$isOwnProfile` im `use(...)`-Scope. Dadurch war `$request` innerhalb der normalen anonymen Closure nicht verfügbar. Arrow Functions erfassen Variablen automatisch, normale Closures aber nicht.

## Änderung

Alle betroffenen `visible_feed_posts_count`-Closures im `ProfileController` erfassen jetzt zusätzlich `$request`:

`use ($isOwnProfile, $request)`

Betroffen sind die Profilansichten:

- `show`
- `about`
- `friends`
- `badges`
- `teams`

## Keine Änderung

- Keine Route geändert
- Keine View geändert
- Keine Migration
- Keine SEO-Änderung
- Keine Android-Änderung

## Prüfung

`php -l app/Http/Controllers/Profile/ProfileController.php` ist sauber.
