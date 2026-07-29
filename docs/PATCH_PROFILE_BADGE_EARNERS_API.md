# Profile badge earners API

Adds one authenticated read-only endpoint for the React profile badge cards:

- `GET /api/v1/users/{username}/profile-badge-earners`

The response contains, per badge owned by the viewed profile, the total number of active users who earned it and at most five user previews. Existing profile endpoints and database tables remain unchanged.
