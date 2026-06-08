# Patch 525 – Profil-SEO / öffentliche Profil-Darstellung v1

## Ziel
Öffentliche Socialite-Profile sollen beim Teilen und in Suchmaschinen nicht mehr mit generischen Meta-Daten erscheinen, sondern mit echten Profildaten.

## Änderungen

- Dynamische SEO-Titel für Profile:
  - Name
  - Username
  - Hunter-Profil / Hunter Profile
  - HNT.rocks
- Dynamische Meta Description:
  - nutzt zuerst Profil-Headline
  - danach Bio
  - danach reale Profilstatistik als Fallback
- Canonical URLs:
  - öffentliche Profilseiten zeigen auf `/u/{username}` bzw. den passenden öffentlichen Profil-Tab
  - eigene/private Profilansichten bleiben `noindex,follow`
  - Design-Preview bleibt `noindex,follow`
- OpenGraph/Twitter:
  - Profilseiten nutzen `og:type=profile`
  - `profile:username`
  - Coverbild als bevorzugtes Share-Bild
  - Avatar als Fallback
  - HNT Default-OG-Bild als letzter Fallback
- Strukturierte Daten:
  - `ProfilePage`
  - `Person`
  - einfache InteractionCounter für Feed-Beiträge und Moments
- Neue DE/EN UI-Texte für SEO-Fallbacks ergänzt.

## Nicht geändert

- Keine neuen Routen.
- Keine Migration.
- Keine Controller-Änderung.
- Keine Änderung an sichtbarem Profil-Layout.
- Keine dynamische PNG-Hunter-Card. Das bleibt ein späterer Schritt.

## Geänderte Dateien

- `resources/views/themes/socialite/profile/show.blade.php`
- `resources/lang/de/ui.php`
- `resources/lang/en/ui.php`

## Prüfung

- PHP-Lint für Sprachdateien sauber.
- Browser-Rendering wurde in dieser Umgebung nicht live geprüft.
