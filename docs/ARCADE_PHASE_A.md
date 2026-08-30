# Arcade Phase A

The additive `arcade_games` catalog is the remote source of truth for native and controlled HTTPS web games. DE/EN columns intentionally follow the existing remote-content convention. RU/ES can be added additively as nullable columns and resource fallbacks; a shared translation table may replace this only when the wider product adopts one.

`settings` and `reward_settings` are internal structured data and are deliberately absent from the admin form. Phase A performs no reward or economy operation. Web launch URLs are restricted by `ARCADE_TRUSTED_WEB_HOSTS` (default `games.hnt.rocks`), and remote native executables/plugins are unsupported.

`min_client_version` is returned but does not affect `is_playable` yet because authenticated API requests do not provide a reliable Flutter version. A later phase should extend the existing app remote-config/version contract and compare the verified request version centrally rather than creating another version mechanism.

Phase A contains no invitations, matches, players, moves, gameplay, realtime channels, matchmaking, rating, leaderboards, stats, rewards, seasons, lobbies, or web-game sessions/authentication.
