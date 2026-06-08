# Patch 182 Laravel: Team archivieren API v1

- Adds `POST /api/v1/teams/{team:slug}/archive`.
- Only the active team owner may archive the team.
- Archives by setting `status=archived` and soft-deleting the team, matching the existing web behavior.
- Extends team detail viewer payload with `viewer.can_archive`.
