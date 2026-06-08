# Patch 520 — Approved Outbound Links / freigegebene externe Links

## Ziel
Externe Links bleiben im Feed weiterhin nicht automatisch klickbar. Für geprüfte Partner-Links, Discord-Cups oder externe Eventseiten kann der Admin stattdessen einen internen HNT-Link anlegen.

Beispiel:

- Feed-Link: `https://hnt.rocks/out/discord-cup`
- Ziel: `https://discord.gg/...`

Beim Öffnen erscheint zuerst eine HNT-Zwischenseite mit Hinweis, danach kann der Nutzer bewusst zur externen Seite wechseln.

## Neu
- Adminseite: `/admin/outbound-links`
- Öffentliche Zwischenseite: `/out/{slug}`
- Weiterleitung: `/out/{slug}/go`
- Neue Tabelle: `approved_outbound_links`
- Feed-Preview erkennt `/out/...` als internen geprüften Link.

## Datenschutz / Sicherheit
- Keine automatische externe Vorschau im Feed.
- Externe Direktlinks bleiben unverlinkt.
- Nur vom Admin freigegebene `/out/...` Links sind klickbar.
- Es wird nur ein aggregierter Klickzähler gespeichert, keine IP- oder User-Agent-Logs.
