# AI community manager MVP

This feature is staged in an isolated branch. It is **not deployed**. The migration only adds two new tables and `users.is_ai_character` (default `false`); it creates no users or posts. Existing users and feed posts are not modified by the migration.

## Safe rollout sequence (separate, explicitly approved operation)

1. Review the migration and take the normal database backup. Test against a disposable local database first. Do not point local Artisan commands at production.
2. Deploy code and run the migration under the normal approved release process. Keep `AI_COMMUNITY_ENABLED=false` and `AI_COMMUNITY_AUTO_GENERATE=false`.
3. Run `php artisan ai-community:seed --dry-run` and inspect the exact ten names. Then run `php artisan ai-community:seed` once. It only creates disabled persona definitions; reruns preserve all admin edits and never create user accounts.
4. In ACP → KI Community, review each persona. An administrator can explicitly provision each synthetic account. Provisioning refuses to reuse an existing username, assigns an unguessable random password and an address beneath the reserved `.invalid` domain, sets `is_ai_character`, and creates a public profile with the permanent disclosure. There is no bulk account provisioning.
5. Configure `OPENAI_API_KEY` and a positive `AI_COMMUNITY_MONTHLY_BUDGET_USD`. Set `AI_COMMUNITY_ENABLED=true` only when administrators are ready to generate drafts. Keep `AI_COMMUNITY_AUTO_GENERATE=false` for manual generation. Confirm account disclosure on the public profile before publishing any approved draft.
6. Admins edit or reject drafts, explicitly approve them, then explicitly publish. Publish verifies the synthetic account, public profile, and disclosure in a transaction. It writes one feed post with AI declaration fields. It does not create likes, reactions, comments, media, referrals, cup participation, or other engagement.

## Configuration and guardrails

| Variable | Default | Meaning |
| --- | --- | --- |
| `AI_COMMUNITY_ENABLED` | `false` | Master generation switch; manual generation also requires the character to be enabled. |
| `AI_COMMUNITY_AUTO_GENERATE` | `false` | Optional hourly draft scheduler; never publishes. |
| `OPENAI_API_KEY` | unset | Server side key; never included in views or logs. |
| `AI_COMMUNITY_MODEL` | `gpt-4.1-mini` | Chat Completions JSON mode model. |
| `AI_COMMUNITY_MONTHLY_BUDGET_USD` | `5` | Estimated monthly spend ceiling. |
| `AI_COMMUNITY_INPUT_COST_PER_MILLION_USD` | `0.40` | Operator maintained estimate; verify current pricing before enabling. |
| `AI_COMMUNITY_OUTPUT_COST_PER_MILLION_USD` | `1.60` | Operator maintained estimate; verify current pricing before enabling. |

The generator locks requests, rejects duplicate normalized text, limits all generated drafts to eight per Berlin calendar week, and checks estimated monthly spend before and after API response. Cost estimates are not billing records. Generated content is capped at 700 characters. Human review must reject first person match claims, unverified patch or event claims, invented user interactions, or any claim that would mislead readers. No external source is supplied for patch verification, so prompts forbid current patch claims entirely.

The optional schedule checks weekdays during 09:00–20:59 Berlin time, with varied eligible hours. It creates drafts only. Production scheduler configuration and cache locking must be checked during rollout; leave auto generation disabled until that review is complete.

Character profiles disclose in both the stored headline and bio. The profile model enforces public visibility and disclosure on every model save for a flagged synthetic user. The API user payload includes `is_ai_character` and a disclosure string. Human member listing and ACP user totals exclude synthetic accounts. No feed card badge was added.

## Rollback

Disable both generation flags immediately. Stop scheduling the command. Preserve draft and published post audit history. Do not run the down migration after publishing because it would remove disclosure flags and draft links; handle any data removal only under a separately approved plan.

## Local verification

Feature tests cover admin access, fake HTTP generation, duplicate blocking, approval, account disclosure, and single publication. Run on a disposable local test database only: `php artisan test --filter=AiCommunityAdminTest`. Never use production DB credentials for tests.
