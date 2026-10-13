# AI community manager MVP

This feature is staged in an isolated branch. It is **not deployed**. The migration only adds two new tables and `users.is_ai_character` (default `false`); it creates no users or posts. Existing users and feed posts are not modified by the migration.

## Safe rollout sequence (separate, explicitly approved operation)

1. Review the migration and take the normal database backup. Test against a disposable local database first. Do not point local Artisan commands at production.
2. Deploy code and run the migration under the normal approved release process. Keep `AI_COMMUNITY_ENABLED=false` and `AI_COMMUNITY_AUTO_GENERATE=false`.
3. Run `php artisan ai-community:seed --dry-run` and inspect the exact ten names. Then run `php artisan ai-community:seed` once. It only creates disabled persona definitions; reruns preserve all admin edits and never create user accounts.
4. In ACP → KI Community, review each persona. An administrator can explicitly provision each synthetic account. Provisioning refuses to reuse an existing username, assigns an unguessable random password and an address beneath the reserved `.invalid` domain, sets `is_ai_character`, and creates a public profile with the permanent disclosure. There is no bulk account provisioning.
5. The manager prefers `OPENAI_API_KEY`, and falls back to the existing `HH_TRANSLATION_OPENAI_API_KEY` when no dedicated key is configured; no key copy is necessary. Configure a positive `AI_COMMUNITY_MONTHLY_BUDGET_USD`. Set `AI_COMMUNITY_ENABLED=true` only when administrators are ready to generate drafts. Keep `AI_COMMUNITY_AUTO_GENERATE=false` for manual generation. Confirm account disclosure on the public profile before publishing any approved draft.
6. Admins edit or reject drafts, explicitly approve them, then explicitly publish. Publish verifies the synthetic account, public profile, and disclosure in a transaction. It writes one feed post with AI declaration fields. It does not create likes, reactions, comments, media, referrals, cup participation, or other engagement.

## Configuration and guardrails

| Variable | Default | Meaning |
| --- | --- | --- |
| `AI_COMMUNITY_ENABLED` | `false` | Master generation switch; manual generation also requires the character to be enabled. |
| `AI_COMMUNITY_AUTO_GENERATE` | `false` | Optional hourly draft scheduler; never publishes. |
| `AI_COMMUNITY_REVIEW_ENABLED` | `false` | Optional independent AI editorial score on an admin-selected draft; requires the master switch and an API key. |
| `OPENAI_API_KEY` | unset | Server side key; never included in views or logs. |
| `AI_COMMUNITY_MODEL` | `gpt-4.1-mini` | Chat Completions JSON mode model. |
| `AI_COMMUNITY_MONTHLY_BUDGET_USD` | `5` | Estimated monthly spend ceiling. |
| `AI_COMMUNITY_INPUT_COST_PER_MILLION_USD` | `0.40` | Operator maintained estimate; verify current pricing before enabling. |
| `AI_COMMUNITY_OUTPUT_COST_PER_MILLION_USD` | `1.60` | Operator maintained estimate; verify current pricing before enabling. |

The generator locks requests, checks the most recent 100 draft texts for exact or near duplicates and repeated openings, limits all generated drafts to eight per Berlin calendar week, and checks estimated monthly spend before and after API response. Up to 12 recent texts are included in the prompt as negative examples. The output guard rejects empty or over-700-character text, links, and common fabricated match or audience claims. It is deliberately narrow enough to allow hypothetical first person language. Human review remains necessary for semantic repetition, language quality, factual accuracy, unverified patch or event claims, and subtle invented interactions. No external source is supplied for patch verification, so prompts forbid current patch claims entirely. Cost estimates are not billing records.

## Human fact-attention hints for drafts (V9 follow-up)

V9's private eight-post audit ran the **same Laravel structural quality guard** as the feature's generator against the original JSON file: six structurally passed; the two posts with redundant questions (V9-03 and V9-08) were blocked. No database/feed writes occurred. The six passes **do not** mean the posts are natural or game-factually correct.

The feature branch now has an additional **deterministic, advisory-only** `AiCommunityClaimRiskInspector` which looks for narrowly recognizable patterns that deserve a human check: statements linking crow alerts to mere opponent proximity, proposed Choke Bomb timers/effect displays, or claims citing specific patches/official announcements. It shows an escaped **"Manuelle Faktenprüfung empfohlen"** note on the admin draft card. Normal opinions such as choosing Romero versus Vetterli are not flagged. This is not a complete fact checker and can both miss bad claims and flag legitimate hypotheticals; it never writes status, calls OpenAI, requests the internet or publishes anything. Human approval, public synthetic-profile disclosure and a separate publish step remain mandatory.

The isolated regression run after adding this warning layer: **33 PHPUnit tests, 440 assertions passed**, including no-HTTP/no-feed admin display coverage. Database shut down after tests. No production migration, account, feed or site writes.

## Source-reviewed Hunt facts and limited pilot release plan (13 October 2026)

The generator now receives only **topic-matched, narrow, source-reviewed context** from `AiCommunityVerifiedFacts::forAngle()`, never a wholesale free-form unverified patch narrative. Four Crytek official references reviewed on 2026-10-13 cover (1) animal/crow sound traps, (2) the map names from the official Player Guide, (3) the burning-extinguishing effect described for Choke Clouds, and (4) CrySpatial directional audio. Each includes original URL, title, review date and an explicit caution about old information and **not** assuming current rotation, balance numbers, effect timers, or patch changes. For unsupported topics the returned list is empty; prompt instructions prohibit inventing facts. The admin shows the relevant original source links on its draft cards so editors can independently inspect claims.

**Verification:** 36 PHPUnit tests / 470 assertions passed on disposable database; database stopped; no production write. This is only a narrow source-gathering aid, **not a real-time fact checker**. The separate AI editorial reviewer has repeatedly failed calibration and cannot grant publishing permission.

**Pilot release gate (NOT COMPLETE):** No automation and no production deploy yet. Before first posts: (1) review current production-vs-feature backend diff and create a verified MariaDB and source backup, (2) test migration and auth/approval in isolated staging, (3) review and deploy backend with `AI_COMMUNITY_ENABLED=false` and `AI_COMMUNITY_AUTO_GENERATE=false`, (4) integrate the React disclosed automated-author label on both standard and redesign feeds, including shared/permalink views, while preserving unrelated dirty production React worktree, (5) provision a small number of **publicly identified automated** accounts in admin only with explicit operator authorization, (6) generate and fact-check two or three drafts manually, (7) explicitly approve and publish one at a time and verify public rendering and reporting/moderation. Keep auto-generation and auto-publish OFF during this pilot. Do **not** misrepresent synthetic activity as real users or fake reactions/comments.

React presentation is being prepared in separate draft PR xdamonx83-tech/hntrocks-react-next#150; backend PR xdamonx83-tech/hntrocks-web#196 is also draft. A separate Flutter parity audit follows the web pilot; it should include `ai_user_declared`, `ai_label_visible` and `author.is_ai_character` rendering, plus correct visibility of disclosures.

## Optional editorial review

With `AI_COMMUNITY_ENABLED=true`, `AI_COMMUNITY_REVIEW_ENABLED=true`, and a configured key, an admin can click **KI-Qualität prüfen** on an unreviewed draft. A separate low-temperature JSON call returns PASS, REVISE, or REJECT, naturalness and Hunt-accuracy scores from 1–5, and a short reason. The reviewer considers awkward translations, artificial phrasing, invented UI/items/mechanics, tone, and length using the stored editorial angle and style. Draft content is untrusted input. It never rewrites, approves, rejects, or publishes a draft; human approval and a separate publish action remain mandatory. Scores can be mistaken, and the reviewer does not verify facts against external sources.

Each review adds an estimated API charge (up to about 240 output tokens plus prompt tokens, priced using the configured per-million-token estimates). Generation and review share the monthly estimated budget and request lock. Editing a draft clears its displayed decision and scores so it can be checked again, while retaining accumulated token/cost accounting. The estimated costs are not provider billing records. This is an unreleased feature-branch migration change; if the migration has run elsewhere, add a forward migration there before deployment.

## Editorial variety (v3)

The model no longer repeats one type of tactical tip. Each draft receives a stored `post_kind` from eight weighted, editorially distinct formats: community question, short opinion, gear choice, hunter/skin discussion, map conversation, light Bayou humor, hypothetical feature wish, and sound/atmosphere. The two most recent formats are excluded from the next choice, and repeated formats receive lower weights. In the ACP, each draft shows its kind for human review.

The purpose is casual, grounded Hunt conversation — a question about favorite hunters, a short weapon take, or a map-atmosphere observation — not polished strategic essays. Facebook/Reddit screenshots may inform broad topic types but no real participant's content is copied or impersonated. Never fabricate a match report, screenshot, owned item, friend, endorsement or reaction. Public synthetic-profile disclosure and separate real-member metrics remain mandatory. Content sources for news and media must be independently verified, not invented by generation.

This is an **unreleased branch-only change**. The format column was added to the still-unapplied feature migration; if a staging database already ran that migration, add a compatible forward migration before deployment. Run isolated PHP and database tests and review fresh actual OpenAI outputs before enabling anything.

## Reviewed conversation angles (v4 staging)

Before generation, select one of 24 manually reviewed conversation focuses across the eight post kinds. Drafts keep the chosen `editorial_angle` identifier so a single focus is not repeatedly suggested. The prompt includes that focus and a short allowlist of relevant game terms, rather than asking the model to invent background facts. The source for each focus is code-reviewed editorial planning, **not independently verified live patch information**. The human admin must still reject wrong details, unnatural wording and repetitive content. An automated character must not claim personal matches, friendships or screenshots.

The `editorial_angle` column was added to the feature branch migration only. It is not deployed. If this feature migration has already run in any staging environment, use a separate forward migration instead of silently changing an applied migration. Private sample scripts do not connect to the application DB or write feed posts.

## Writing structures (v5 staging)

Three screenshots from Reddit and international and German Hunt Facebook communities informed **general patterns only**: brief direct headline questions, very short reactions, conversational German discussion starters, and specific requests for opinions. The style library varies sentence structure and target length while the existing post kind and reviewed editorial angle still choose the topic. It prefers a mix of roughly 20–300 characters, questions and statements, avoids the last two available structures, and reduces weight for recently repeated lengths and endings. A draft stores its `style_key`; ACP shows its descriptive label for review.

No participant names, exact wording, images, captions, or personal details from the screenshots were copied into code or stored as training data. This iteration uses hand-written editorial patterns. It performs **no automatic neural training and no vision model call**. Typos are never inserted to imitate a human. Automated characters cannot claim to have played, captured media, owned equipment, sought teammates, or witnessed an event. A news reaction requires an independently verified source; a media caption requires an approved attachment. Neither source nor attachment exists in the current generator, so those styles are unavailable for automatic selection. Human review remains required for factual accuracy and natural wording.

The nullable `style_key` column is part of the feature-only migration and has not been applied in production. If this migration was already applied to any separate staging database, add a forward migration there before deployment.

## V5 benchmark regression: prompt metadata and factual accuracy (13 Oct 2026)

The fifth private sample batch produced 10 outputs at an estimated $0.003176. Several outputs leaked the literal label `statement` into the body; another inaccurately characterized Choke Bombs as generic `crowd control`. The isolated Python probe had an explicit `Ending: statement` instruction and was **not identical** to the Laravel generation prompt, so its samples do not establish the quality of the production feature.

The Laravel prompt now explains punctuation naturally without a suffix label. The draft quality guard rejects trailing style metadata, endings incompatible with a selected question/statement structure, and a known misleading Choke Bomb description. These are narrow regressions and **not** a complete in-game fact checker. All automatically generated text remains a draft requiring human review.

For any next live-model benchmark, export and test the **actual** messages from `AiCommunityGenerationService::promptContext()` on an isolated, up-to-date checkout; never duplicate/rewrite the prompt into a standalone Python script. The benchmark must never create members, store drafts in the live DB, or publish feed posts. Use a separate credential-safe test harness and retain a record of the selected `post_kind`, `editorial_angle`, and `style_key`.

## Editorial reviewer calibration (13 October 2026)

An independent private V6 reviewer test scored **all ten posts as PASS and all ten naturalness scores as 5/5** (approximate estimated API spend $0.001993). This conflicts with the manual V6 assessment (2 publishable without revision, 4 needing edits, 4 rejected). The original advisory reviewer was therefore too permissive and **must not be used as an automatic publishing gate**.

The reviewer prompt now grades DE/EN Hunt posts against a concrete five-point naturalness rubric and negative calibration examples (overly instructional Crow commentary, literal Bayou-to-Bucht translation, synthetic atmosphere metaphors). A result of PASS requires both naturalness and Hunt plausibility >=4. The server also conservatively downgrades a model PASS to REVISE when its own scores fail the threshold. No automatic posting or editing is enabled. Its Hunt plausibility score is **not verified game-fact checking**.

**Verification:** 26 isolated Laravel tests passed (416 assertions) including an inconsistent PASS downgrade and the existing generation budget checks. A separate read-only V7 private benchmark runner reuses the ten saved V6 texts and verbatim updated Laravel reviewer prompts. Its exact sample outputs are not yet evaluated. No news source, sample scraping, DB writing or production deployment is involved. Follow the usual root-only script checksum procedure and do not overwrite previous private benchmark files.

## Reviewer calibration benchmark (V7, October 2026)

The V7 reviewer using the stronger rubric still gave **9 of 10 posts PASS** (one REVISE; estimated cost $0.003261). The original, provisional human V6 editorial judgment accepted only **two without changes**, marked four for revision, and found four unsuitable. Seven of the nine model PASS calls are therefore unsupported by this subjective human reference: only **2/9 (22.2%)** of model PASS judgments match human-ready labels. This small, non-blind set is not a statistically reliable measure of overall accuracy; the V7 reviewer prompt also contains examples from this set, so this is *training/calibration* evidence, not a held-out validation score.

The script `scripts/ai_community_reviewer_calibration.py` provides a **strict offline, no-API, no-database comparison** of these ten named *synthetic* character examples against local manual labels. Run it from the feature tree as:

```bash
python3 scripts/ai_community_reviewer_calibration.py /path/to/private-v7-editorial-results.json --gate
```

It reads an operator-supplied private review result JSON and computes exact agreement, incorrect PASS count and PASS precision. Gate succeeds only with PASS precision >=80%, zero human-rejected texts with model PASS, and at least one model PASS. It is expected to **fail for V7**. The labels are subjective early editorial assessments and should be reviewed by a human before using them as acceptance standards.

Before trusting any automated advisory scoring, assemble a separate held-out set of new DE/EN posts (including good natural posts, borderline edits, and game-mechanics errors), have a knowledgeable human label it **without seeing AI ratings**, then compare with the scorer. Never calibrate and evaluate on the same examples. Irrespective of scores, generated content remains a disclosed automated draft requiring explicit human approval; no automatic publishing.

## Blind V8 hold-out evaluation (prepared; not run yet)

After V7 scored 9/10 private drafts PASS while the provisional editorial reference accepted only 2/10 without edits, we prepared a **new 20-post blind assessment**. It contains 10 DE and 10 EN examples, 14 distinct editorial angles, and diverse writing structures. The export is built from the **actual** `AiCommunityGenerationService::promptContext()` using `scripts/ai_community_blind_prompt_export.php` without Laravel app bootstrap, production env, DB, or users. The private exported prompt digest is checked before the separate root-only OpenAI generation runner (20 calls maximum, estimated $0.05 limit). No test posts are published.

**Human evaluation must be collected FIRST.** A self-contained offline HTML review file is available on the authorized Windows desktop at `C:\Users\xdamo\Documents\HNT\hnt_v8_blind_review.html`. It loads the generated private JSON locally, allows a human to label each post as PASS/REVISE/REJECT with an optional note and exports a CSV. No remote calls, third-party resources, browser storage, or tracking. Keep results local until intentionally supplied for the benchmark.

The offline `scripts/ai_community_blind_benchmark.py` can also generate the CSV score sheet and subsequently evaluate a **separately obtained** reviewer JSON. It validates exactly 20 IDs, the 10/10 language split, complete human labels, and text SHA-256 identity. It refuses to overwrite existing human labels; its acceptance gate checks >=80% precision among model PASS verdicts and **zero** human-rejected posts with a model PASS. A PASS here is not automatic permission to publish. Five blind-benchmark offline tests pass; the full offline regression suite currently has eight passing tests.

Do not show independent AI review results before human labels are saved. Do not tune the review prompt on V8 and then claim V8 itself is an unbiased evaluation set. Changes live only in the feature branch: no production deploy, schema write, synthetic-account provisioning, or feed activity.

## Quality preview

ACP → KI Community → **Qualitätsvorschau** opens a read-only view of the exact prompt context for one persona, including recent draft snippets as negative examples. It performs database reads only: no OpenAI call, draft, account, or post is created. It is a test prompt, **not an AI output preview or evidence of model quality**. Without a configured key, generation is disabled. This staging work used fake OpenAI HTTP only; actual API quality and cost have not been tested. Seed voice changes apply only to newly inserted personas because the seeder intentionally preserves existing admin edits. Review existing persona settings manually if adopting the revised voices.

The optional scheduler checks hourly during 11:00–22:59 Berlin time, including weekends. It has a 6% hourly chance on weekdays and 8% on weekends; it creates no more than one draft per Berlin calendar day, subject to the existing global eight-draft-per-week limit. It creates drafts only and never publishes. Production scheduler configuration and cache locking must be checked during rollout; leave auto generation disabled until that review is complete.

Character profiles disclose in both the stored headline and bio. The profile model enforces public visibility and disclosure on every model save for a flagged synthetic user. The API user payload includes `is_ai_character` and a disclosure string. Human member listing and ACP user totals exclude synthetic accounts. No feed card badge was added.

## Rollback

Disable both generation flags immediately. Stop scheduling the command. Preserve draft and published post audit history. Do not run the down migration after publishing because it would remove disclosure flags and draft links; handle any data removal only under a separately approved plan.

## Local verification

Feature tests cover admin access, fake HTTP generation, missing-key behavior, read-only preview, duplicate blocking, approval, account disclosure, and single publication. Unit tests cover prompt content and output quality checks. Run on a disposable local test database only: `php artisan test --filter='AiCommunity(AdminTest|DraftQualityGuardTest)'`. Never use production DB credentials for tests. The current worktree verification uses `php -l` and `git diff --check`; PHPUnit needs a separately configured ephemeral MariaDB and local vendor dependencies.
