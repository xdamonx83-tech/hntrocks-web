# Patch 524 - Profile Hunter Card v1

## Ziel

Erste Profil-Vitrine als echte Blade/CSS-Komponente statt statischem Bild. Die Karte ist optisch an eine dunkle Hunt-/Tarot-Karte angelehnt, bleibt aber lesbar und responsiv.

## Geändert

- Dynamische Hunter-Card-Daten im ProfileController ergänzt.
- Neue Socialite-Profil-Partial `hunter-card.blade.php` erstellt.
- Hunter Card im rechten Profilbereich eingebunden.
- Neue i18n-Texte auf Deutsch und Englisch ergänzt.
- CSS für die dunkle Tarot-/Covenant-artige Hunter Card ergänzt.

## Datenquellen

Die Hunter Card nutzt vorhandene Daten:

- Nutzername, Avatar, Level und XP
- Profilrolle oder Spielstil
- Cup-Punkte, Bounties und Kills aus dem bestehenden Trophy-System
- akzeptierte Loadout-Challenge-Einreichungen
- freigeschaltete Badges
- beste Cup-Platzierung oder stärkster Moment als Footer-Hinweis

## Keine Änderungen

- Keine neue Migration
- Keine neuen Routen
- Keine neuen Tabellen
- Keine neue Upload- oder Bildgenerierungslogik
- Keine Änderung an Android

## Hinweis

Die Karte ist bewusst als HTML/Blade-Modul gebaut. Eine dynamische Share-PNG-Version kann später separat ergänzt werden.
