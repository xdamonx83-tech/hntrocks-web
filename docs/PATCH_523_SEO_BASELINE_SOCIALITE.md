# Patch 523 – SEO Baseline Socialite

## Ziel
Technische SEO-Grundlage für HNT.rocks verbessern, ohne öffentliche/authentifizierte Bereiche blind umzubauen.

## Änderungen
- Gemeinsames SEO-Meta-Partial für Titel, Description, Robots, Canonical, OpenGraph, Twitter Card und JSON-LD hinzugefügt.
- Socialite-Head, alte App-Layouts und Auth-Layout nutzen jetzt dieselbe SEO-Grundlage.
- Default-OpenGraph-Bild ergänzt: `public/assets/socialite/images/seo/hnt-og-default.png`.
- Login/Register/Passwort/2FA-Seiten auf `noindex,follow` gesetzt.
- Approved-Outbound-Zwischenseite auf `noindex,follow` gesetzt.
- `robots.txt` um private, technische und Preview-Bereiche ergänzt.
- `sitemap.xml` aktualisiert: Login/Register entfernt, öffentliche Seiten wie App-Beta, Loadout-Challenges und Legal-Seiten ergänzt.

## Bewusst nicht geändert
- Keine dynamische Sitemap aus Datenbankinhalten.
- Keine Indexierung von Feed/Profil/Teams/LFG/Moments, da diese Bereiche aktuell laut Upload weitgehend authentifiziert/private App-Bereiche sind.
- Keine neue öffentliche Landingpage für `/`; die Root-Route redirectet weiterhin nach Login/Feed.
