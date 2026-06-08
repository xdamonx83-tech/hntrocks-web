# Vikinger Template Mapping

Stand: Phase 22

Diese Datei ist eine kompakte Dokumentation. Die ausführliche maschinenlesbare Mapping-Quelle liegt in:

```text
config/vikinger.php
```

Die Admin-Ansicht dazu liegt unter:

```text
/admin/vikinger-mapping
```

## Grundregeln

- Vikinger ist visuelle/strukturelle Referenz, nicht technische Quelle.
- Laravel bleibt technische Wahrheit.
- Marketplace wird nicht übernommen.
- Groups werden Teams.
- Design wird modulweise umgesetzt.

## Hauptmapping

| Hunthub-Bereich | Vikinger-Referenz |
|---|---|
| Globale Shell | `newsfeed.html`, `overview.html` |
| Newsfeed / Wall | `newsfeed.html` |
| Profil | `profile-timeline.html`, `profile-about.html`, `profile-friends.html`, `profile-photos.html`, `profile-videos.html`, `profile-badges.html` |
| Mitglieder / Spieler finden | `members.html` |
| Teams | `groups.html`, `group-timeline.html`, `group-info.html`, `group-members.html`, `group-events.html`, `hub-group-management.html`, `hub-group-invitations.html` |
| LFG | Pattern aus `members.html`, `groups.html`, `newsfeed.html` |
| Team-LFG | Pattern aus `groups.html`, `group-members.html`, `hub-group-management.html` |
| Nachrichten | `hub-profile-messages.html` |
| Benachrichtigungen | `hub-profile-notifications.html` |
| Mediathek | `profile-photos.html`, `profile-photos-inside.html`, `profile-videos.html` |
| Moments | `profile-videos.html`, `streams.html` plus eigene Hunthub-Reels-Komponente |
| Cups | `events.html`, `events-daily.html`, `events-weekly.html`, `group-events.html` |
| Referrals | `quests.html`, `overview.html` |
| Level / Badges / Quests | `badges.html`, `quests.html`, `profile-badges.html` |
| Account / Settings | `hub-account-info.html`, `hub-account-password.html`, `hub-account-settings.html`, `hub-profile-info.html`, `hub-profile-social.html` |
| Admin | Nur grobe Card-/Overview-Pattern; Admin bleibt funktional priorisiert |

## Ausgeschlossen

- `marketplace.html`
- `marketplace-category.html`
- `marketplace-product.html`
- `marketplace-cart.html`
- `marketplace-checkout.html`
- `profile-store.html`
- `hub-store-account.html`
- `hub-store-downloads.html`
- `hub-store-items.html`
- `hub-store-statement.html`

## Nächster Designschritt

Phase 23: Vikinger-Shell.

Erst danach folgen Feed, Profil, Teams/LFG und Spezialmodule.
