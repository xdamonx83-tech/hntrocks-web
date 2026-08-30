# Arcade Phase B: matches and Hunt Wins

Phase B adds private, server-authoritative multiplayer storage without reusing lobby, cup, or messaging tables. `arcade_invitations` retains invitation lifecycle data; `arcade_matches` contains current state and its monotonic version; `arcade_match_players` assigns extensible positive seats; and `arcade_match_moves` is the immutable move audit. User references are nullable and use `nullOnDelete`; match history is not cascade-deleted with a user.

## Lifecycle

An accepted friendship may create a `casual` or `ranked` invitation only when that mode and the game are active. Pending invitations expire after `arcade.invitation_ttl_minutes`. They transition to `accepted`, `declined`, `cancelled`, or lazily to `expired`. Acceptance locks the invitation and creates exactly one `waiting_ready` match with the inviter in seat 1 and invitee in seat 2. Both players explicitly ready; each newly recorded ready state increments the version, and the second ready also initializes and starts the match. Matches then become `active`, and finally `finished` with player `win`/`loss` or `draw` results.

## Hunt Wins state and moves

`board` is six rows by seven columns. Rows are stored **top-to-bottom** (row 0 is the top and row 5 the bottom); columns are zero-based `0..6`. Values are `0` empty, `1` seat 1, and `2` seat 2. State also contains `turn_seat`, `move_count`, `winner_seat`, and `draw`. The server drops each token into the greatest available row index and alone determines turns, horizontal/vertical/both diagonal wins, and draws.

Moves require a client-generated `client_move_id` and `column`. A match row lock serializes turn changes. `(match_id, client_move_id)` and `(match_id, sequence)` are unique. An identical retry returns current authoritative state without another audit row; changed data with the same ID is HTTP 409. Every accepted state transition increments `version` exactly once.

## API and realtime

All invitation and match routes are under `/api/v1/arcade` and `api.token`. Only invitation sender/recipient may perform their respective action; only participants can view, ready, or move, and identity/seat are always derived from authentication. Private channel `arcade.match.{matchId}` authorizes participants only. `ArcadeMatchUpdated` (`arcade.match.updated`) is dispatched after commit and carries authoritative state and version.

Phase B deliberately does not add matchmaking, public histories, spectators, UI, bots, rewards/economy changes, Elo/MMR, leaderboards, stats, achievements, seasons, tournaments, or web-game sessions. Existing push notification integration is deferred: invitation persistence is complete, while notification copy/settings would require a separately agreed product addition. The `hunt-wins` catalog seed remains `disabled` and this backend cannot be used publicly until a later phase enables it.
