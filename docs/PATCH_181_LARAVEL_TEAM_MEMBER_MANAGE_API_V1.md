# Patch 181 – Laravel Team Member Manage API v1

Adds mobile API endpoints for native team member management:

- POST /api/v1/teams/{team:slug}/leave
- POST /api/v1/teams/{team:slug}/members/{member}/promote
- POST /api/v1/teams/{team:slug}/members/{member}/demote
- POST /api/v1/teams/{team:slug}/members/{member}/remove

Rules:

- Owners cannot leave their own team.
- Only owners can promote/demote officers.
- Owners can remove non-owner members.
- Officers can remove normal members only.
- Users must use the leave endpoint for their own membership.

The team detail payload now includes viewer.membership.id and viewer.can_leave.
