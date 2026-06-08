# Patch 165 — Laravel Team-LFG Create API v1

Adds mobile API support for creating Team-LFG posts.

## Routes

- `GET /api/v1/team-lfg/manageable-teams`
- `POST /api/v1/team-lfg`

## Notes

- `player_seeks_team` can be created without a team.
- `team_seeks_players` requires `team_id` and only allows teams where the authenticated user is active owner/officer.
- Uses existing `TeamLfgPost`, `TeamResource`, `TeamLfgPostResource`, `GamificationService`, and `MentionService`.
- No migration and no Composer dependency changes.
