# Community Loadouts — Web / Flutter parity checklist (2026-10-11)

## Status
- Web community feature: React draft PR in `hntrocks-react-next`, source branch `feature/community-loadouts-web-20261011`.
- Laravel API: draft branch `feature/community-loadouts-20261011`.
- Production: **not deployed**; migration **not run**.
- Current random generator at `/loadout-generator/` is a standalone deployed feature, not the community editor. Saving generated equipment directly to the community is a follow-up.
- Flutter app parity: **not yet audited**. Do not mark anything absent until actual app source/routes are compared.

## Shared server endpoints (under /api/v1, authenticated via api.token)
| Web flow | Method and path |
| --- | --- |
| Community overview, newest / popular / discussed + search | GET `loadouts?sort=...&search=...&page=...` |
| My loadouts | GET `loadouts/mine` |
| Loadout detail | GET `loadouts/{id}` |
| Create | POST `loadouts` |
| Edit | PATCH `loadouts/{id}` |
| Delete | DELETE `loadouts/{id}` |
| Vote / withdraw vote | POST `loadouts/{id}/vote` with `value` = 1, -1 or 0 |
| Read comments | GET `loadouts/{id}/comments?page=...` |
| Add comment | POST `loadouts/{id}/comments` with `body` |
| Delete permitted comment | DELETE `loadouts/{id}/comments/{commentId}` |

## Flutter gap audit (later)
- [ ] Locate Flutter app repository and confirm installed version / actual release branch.
- [ ] Inventory all existing Flutter routes/screens and API clients.
- [ ] Compare feature list: community grid/search, three sorts, detail, creation, editing, deletion, ratings, comments, own loadouts.
- [ ] Compare shared user identity and API auth behavior.
- [ ] Reuse exact backend DTOs and validation limits; do not create separate app-only storage.
- [ ] Check locale DE/EN/ES/RU and mobile layouts.
- [ ] Check moderation, error states, inaccessible/private loadouts, vote withdrawal and self-vote denial.
- [ ] Check deep links: `/loadouts/{id}` and user profiles.
- [ ] Add Flutter tests and check actual device navigation.
- [ ] Verify parity against production endpoint behavior **after** Laravel migration/deployment.

## Deployment safeguards
1. Test and review backend migration against a **test database**, not production data.
2. Deploy backend first; run a controlled migration with backup/rollback plan.
3. Deploy only the seven-plus changed frontend files in the current live redesign, never replace the dirty server worktree with an old branch.
4. Verify authenticated list/create/vote/comment flows and the legacy three-slot profile loadouts.
5. Integrate the standalone random loadout generator's Save/Publish flow in a separate scoped change.
6. Verify Flutter parity before claiming full cross-platform support.
