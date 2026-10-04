# Admin V2: Semi-Boxed Integration (2026-10-04)

## Reference
- User-provided `material(1).zip` contains the **Velzon** HTML template.
- Requested layout: semi-boxed; `apps-projects-list.html` for project cards, `apps-chat.html` for internal admin chat.
- Preserve the existing HNT-ACP dark visual theme, sidebar, routing, controller behavior and auth middleware. Do not import the entire vendor theme into Laravel (global CSS and JS conflicts).
- Server runtime is source of truth: before integration or deployment, diff the current live files against the branch, never overwrite newer server changes.

## Implemented on feature/admin-v2-users-semi-boxed-20261004
- User list with real paginated Laravel accounts: status, admin role, moderation flags, filters, avatar, `@username`, edit and guarded sensitive actions.
- Edit screen restyled, retaining all existing forms and controller actions.
- Shared header menu displays the authenticated admin and links to collaboration pages, existing admin dashboard, and website.
- GET-only `/admin/collaboration/projects` and `/admin/collaboration/chat`, inside the existing `hnt.admin`-protected Laravel route group.
- Projects preview contains *explicitly marked* fabricated demonstration cards; no database writes, no project API.
- Chat preview has no message sending or persistence.
- Added feature tests for admin access and preview contents.

## Staging checklist — not yet executed on live server
1. Compare live tracked files, pending changes, git revisions and staged migrations with branch; stop on overlap.
2. Apply only nonconflicting changes, preferably via a fresh separate worktree / staging environment.
3. Run `php -l routes/web.php`, `php artisan route:list --path=admin`, `php artisan view:cache`, and `php artisan test --filter=AdminCollaborationPreviewTest`.
4. Verify as admin and as nonadmin; exercise existing user status, role and profile moderation forms on a nonproduction DB only.
5. Review the semi-boxed layout at desktop and mobile widths and ensure older admin views remain usable.
6. Deploy only after explicit user review. No production data mutations in this patch.
