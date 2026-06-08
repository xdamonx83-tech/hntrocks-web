# Patch 180 – Laravel Team-LFG Applications Manage API v1

Adds mobile API endpoints for managing pending Team-LFG applications/invitations.

## Routes

- `POST /api/v1/team-lfg/{post}/applications/{application}/accept`
- `POST /api/v1/team-lfg/{post}/applications/{application}/reject`

## Behaviour

- For `team_seeks_players`: team owner/officer can accept or reject player applications.
- For `player_seeks_team`: post owner can accept or reject team invitations.
- Accepted `team_seeks_players` applications add the applicant as an active team member and update slots/status.
- Accepted `player_seeks_team` invitations add the post owner to the inviting team and mark the post filled.
- `TeamLfgPostResource` now exposes `pending_applications` to authorized managers/owners.
- Team-LFG index now also includes private posts owned/managed by the authenticated user.

No migration. No Composer dependency.
