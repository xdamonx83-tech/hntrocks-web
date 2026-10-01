# HNT Equipment Database – Backend foundation

## Architecture

Laravel owns the equipment data and comparison rules. React and Flutter use the same public `/api/v1/arsenal` API. Stable database IDs and slugs allow later favorites and loadout relations. No React UI or live deployment is included here.

The `EquipmentSourceInterface` isolates source retrieval. `HuntifyEquipmentSource` reads four public structured JavaScript datasets, validates their exact registration wrapper, then decodes only the JSON payload. `EquipmentSyncService` normalizes factual values, writes each item in its own transaction, and records changes. A failed or incomplete feed cannot mark unseen items as missing. A single-item sync also cannot mark other items missing.

## Source discovery (30 September 2026)

The public [HuntWiki page](https://wiki.huntify.win/) loads `Hunt/data/modules.js` and `Framework/services/data.js`. The latter documents the dataset registration bridge `window.Hunt.data.register`. We fetched and parsed:

| Source | Item count | Size at discovery |
|---|---:|---:|
| `Hunt/modules/Weapons/data.js` | 156 | 870,858 bytes |
| `Hunt/modules/Tools/data.js` | 22 | 28,285 bytes |
| `Hunt/modules/Consumables/data.js` | 31 | 40,078 bytes |
| `Hunt/modules/Skins/data.js` | 918 | 779,707 bytes |

The weapon feed includes IDs, families, classes, stats, ammo, falloff envelopes, trait IDs, skin IDs, and `Hunt/assets/icons/...` image paths. The feeds contain prose fields (`lore`, `description`, ammo `notes`); the importer **does not ingest those fields**. The public `stats-def.js` helped verify stat units and directions. These are current observed structures, not a guaranteed third-party contract. The importer fails clearly if the wrapper or response format changes.

Review of the actual structured classification fields: all 156 weapons provide `category` and `weaponType`; all 22 tools and 31 consumables provide `category`. The nine consumable boost items have the structured `Boon` label and `consumableboost` in their internal `gameId`. Tools have no `weaponType`, `type`, `class`, `subgroup`, or `usage` field in this feed. Comparison groups use `weaponType` for weapons (with `category` for `Undetermined`), `category` for tools and consumables, and `Boon`/`gameId` for shots. `Melee` and `Melee / Throwable` tools share `tool:melee`; other tool categories remain separate. Example groups are `weapon:rifle`, `weapon:pistol`, `tool:healing`, `consumable:explosive`, and `consumable:shot`. Names and translated text never determine a group. Unknown category values retain their slug as a separate type-prefixed group.

Image usage rights and any fan kit license have **not** been verified. `equipment_items` and `equipment_skins` have nullable `original_asset_url`, `local_asset_path`, and `license_note` fields. Sync only stores the original reference internally and preserves any later local path or license note. It does not download, copy, hotlink, or publish Huntify images. API `image_url` stays null until a licensed import pipeline can map a local path to a public HNT asset URL for both React and Flutter. Skin IDs and available names/rarities are imported from the separate structured feed. Game patch history is separate from import changes but is not yet populated.

## Data model

`equipment_items` holds typed core fields, stable slug, comparison group, source ID/hash/status, and limited source facts. `equipment_families`, `equipment_translations`, `equipment_stat_definitions`, `equipment_stats`, `equipment_ammo`, `equipment_falloff_points`, `equipment_traits`, `equipment_skins`, and `equipment_patch_history` hold related data. `equipment_sources`, `equipment_sync_runs`, and `equipment_sync_changes` provide provenance and audit history. Stats are restricted to a known definition catalog; this is not an arbitrary EAV store. Ammo falloff is stored as distance and calculated damage points, never SVG. The envelope's damage multiplier is multiplied by the ammo damage value.

Description translations are separate rows for `de`, `en`, `es`, and `ru`; the sync currently generates `de` and `en` with different factual templates for weapons, tools, and consumables. German class, ammo, and category labels use small internal maps with source-label fallback. The API falls back to English for untranslated ES/RU locales. `description_is_manual` protects an admin override from later syncs. The generator reads only structured facts. It does not use source prose or an external AI service.

## Sync

Run `php artisan arsenal:sync --dry-run` first. It prints New, Changed, Unchanged, Missing, and Errors without writing to the database. `php artisan arsenal:sync` persists changes. `--item=SOURCE_ID` or `--item=slug` narrows development imports and never archives other items. Missing items are marked `source_status=missing`; no rows are deleted. Source changes are distinguished from game patch history. A partial sync is visible in `equipment_sync_runs` and does not archive unseen items.

## API

- `GET /api/v1/arsenal` supports `type=weapon|tool|consumable|ammo`, search, category, class, ammo, family, comparison_group, sort, page, per_page, and locale. It returns compact paginated records.
- `GET /api/v1/arsenal/categories` and `/classes` provide filter choices and accept the same optional `type` filter.
- `GET /api/v1/arsenal/{slug}` provides relations, numeric stats, ammo, falloff points, traits, skins, and translations.
- `GET /api/v1/arsenal/{slug}/related` uses the comparison group and family.
- `GET /api/v1/arsenal/compare?items=slug1,slug2[,slug3]` validates two or three distinct active items in the same group. It returns comparison directions, values, differences, ammo, and falloff curves. Invalid sets return HTTP 422.

Numbers such as price, slot size, stat values, ammo damage/velocity, and falloff distance/damage are JSON numbers. Detail and compare responses explicitly select public fields; they omit Eloquent pivot data and internal foreign keys. External image references remain internal.

## Current limits

No external source is stable by contract. No image license has been confirmed. Full unlock progression, historical game patches, full multilingual descriptions, favorite endpoints, and admin controls remain future work. The current description rules are factual and intentionally modest. The third-party feed may contain data errors, so a representative manual review is required before publishing imported values.

The deploy script requires `ARSENAL_REVIEWED_HEAD` set to the exact reviewed commit. It checks branch, remote HEAD, tracked changes, `.env` ownership/mode, syntax, and tests before running the additive migration. It never pulls, clears caches, deletes untracked files, edits `.env`, or starts a sync. If SQLite tests cannot run on the server, `ARSENAL_TESTS_VERIFIED_HEAD` must match the same HEAD after external tests passed. Arrange a database backup and confirm the deployment environment before running it; no deployment was performed in this phase.

The Arsenal feature tests use only the new migration on an in-memory SQLite database. An unrelated older project migration drops a named foreign key, which SQLite cannot execute. On the development host, the matching SQLite PHP extension was loaded temporarily from `/tmp`; no system PHP configuration was changed. A production MySQL migration has not been executed or verified.


## Asset pipeline

Public Arsenal APIs expose images only from HNT's local public storage. Imported source URLs remain metadata and are never hotlinked.

Officially approved bulk assets can be staged locally and matched automatically:

```bash
php artisan arsenal:assets:import-fankit /path/to/unpacked/fankit --dry-run
php artisan arsenal:assets:import-fankit /path/to/unpacked/fankit
```

The importer accepts PNG, JPEG and WebP up to 20 MB, validates the actual image MIME type, matches exact normalized filenames against the item's slug/name/external ID and the basename of `original_asset_url`, writes matched files to `storage/app/public/arsenal/items`, and records the Crytek Fan Kit license/source note. Ambiguous or unmatched files are skipped rather than guessed.

The public API returns `image_url` only when `local_asset_path` exists on the public disk. Huntify URLs stay internal as source metadata.

Crytek's official Fan Kit page explicitly lists weapon images for website/fan use:
https://www.huntshowdown.com/news/download-the-hunt-fan-kit

The 2018 kit may not cover every current Hunt item, so missing assets remain intentionally null until an approved source is available.


## wiki.gg source prototype

The long-term source direction is to prefer the Hunt: Showdown 1896 wiki on wiki.gg for structured equipment details and use Huntify only as a temporary fallback/control source.

The prototype is deliberately read-only and works through the MediaWiki API rather than bulk HTML scraping:

```bash
php artisan arsenal:wiki-sync --item=1865-carbine --dry-run
```

It resolves HNT items to wiki page titles such as:

- `Weapons/1865_Carbine`
- `Weapons/1865_Carbine/Aperture`
- `Tools/Throwing_Spear`
- `Consumables/Frag_Bomb`

The parser currently reads structured infobox values, recommended Traits, detected ammo types, skin infobox titles and Update History rows. The command compares these values with the current HNT database and performs no writes.

This prototype must be verified against live wiki.gg pages before any bulk migration or source switch. Source prose/descriptions are not imported. The future production sync should keep HNT-generated descriptions, preserve manual overrides, rate-limit API requests and record source attribution/revision metadata.

## Phase 2 canonical data foundation (development branch, 1 October 2026)

This section describes code in `feature/arsenal-canonical-foundation-20261001`. No live migration, wiki import, media download or deployment has been performed.

### Canonical data and source priority

HNT tables and the existing API are the contract for React and Flutter. Huntify and wiki.gg remain independent source adapters. A source payload is normalized and stored as an immutable snapshot before an explicitly reviewed merge can update canonical item or stat values. Source prose, raw page HTML and full wikitext are excluded from snapshots. Existing translation descriptions remain under `description_is_manual`; wiki import never edits translations.

Manual overrides always win. A changed existing value with no provenance for the incoming source requires review. A changed value owned by the same source can update; a missing canonical value can be created. A different source cannot replace a value merely because its page resolves. Provenance is keyed by item and field, initially for item columns and `stat.<key>`. Relations need their own reviewed policy before automated replacement. Huntify resync preserves manual and other-source item/stat values and retains wiki.gg metadata in item facts.

Existing production Huntify rows predate field provenance. A non-dry-run Huntify resync of such a row is blocked until its baseline is reviewed and marked; the new sync records `__baseline` only when it creates an item itself. This conservative gate prevents a legacy/manual canonical value from being silently claimed as Huntify-owned. A dry-run remains available for review. Any later backfill of legacy provenance requires a separate approved plan.

`effectiveRange` keeps its current meaning and existing values. wiki.gg `Drop Range` maps only to the new `dropRange` definition. `recoil` remains the public key for the wiki.gg Vertical Recoil field. No horizontal recoil value or unit is invented. The additive migration also inserts the three definitions already present in the code catalog: `effectDuration`, `swapSpeed` and `throwStamina`.

### Snapshots, provenance and history

`equipment_source_snapshots` stores a normalized JSON payload, source identity, revision, URL, content hash and fetch time. `identity_hash` deduplicates equal payloads for the same source and item. Application-level model guards reject update and delete operations. `equipment_field_provenance` records source, snapshot, revision, manual override and verification time for each canonical field. The existing `equipment_sync_changes` remains an import activity log.

`equipment_patch_history` remains a game-history presentation table. wiki.gg textual update notes are not treated as numeric changes and Phase 2 does not import those notes. `EquipmentSnapshotDiffService` derives structured old/new values from two same-item, same-source snapshots; later publication into patch history requires a separate review.

### Import plan and apply

`php artisan arsenal:wiki-import --type=weapon --item=drilling --dry-run` emits item, field, current value, source value, source, revision, action, confidence and blocked reason. Actions are `UNCHANGED`, `CREATE`, `UPDATE`, `SKIP`, `BLOCKED_MANUAL`, `BLOCKED_AMBIGUOUS` and `REVIEW_REQUIRED`. A field marked for review is never applied. Unknown or unmigrated stats are skipped. Ammo relation values remain review-only in this foundation. Trait sets and wiki.gg history notes are observed but not written. The command disallows bulk `--apply` and requires one `--item` plus `--reviewed-revision=<ID>` from a preceding dry-run; a changed revision aborts.

Canonical apply and media apply are separate commands. `--media` on `arsenal:wiki-import --dry-run` displays the media plan; `--media --apply` is rejected. Reviewed downloads use `arsenal:wiki-media --item=<slug> --apply --reviewed-revision=<ID>`.

Every search-resolved page requires mapping review even when its score is above 70; scores below 70 are blocked as ambiguous. This is stricter than the earlier bulk coverage audit.

No family mismatch is auto-applied. `equipment_family_aliases` supports a reviewed source alias to an existing HNT family, with a unique normalized alias per source. No aliases are seeded automatically. For example, a reviewed `Mosin-Nagant` wiki.gg alias may point to the existing `Mosin-Nagant M1891` HNT family without renaming it.

Representative cases for human review from the 95 audit differences are `Bornheim Nr3` / `Bornheim No. 3`, `Caldwell Conversion` / `Conversion`, `Caldwell Pax` / `Pax`, `Mosin Nagant M1891` / `Mosin-Nagant`, `Winfield M1873` / `Ranger 73`, `Winfield 1887` / `Terminus`, `Specter Cavalry` / `1865 Carbine` on two variants, and the opaque Huntify family ID on `wood-axe` / wiki `Wood Axe`. Punctuation-only aliases and apparent renames need different evidence; the mechanism does not infer either.

### Resolver and world items

`fists` remains active until its product role is decided; it may be a system/base combat item rather than normal selectable equipment. `choke-cowboy-beetle`, `fire-cowboy-beetle` and `stalker-cowboy-beetle` remain active pending manual verification as standalone consumables versus beetle skins, aliases or variants. Their failed wiki resolution never deletes or deactivates canonical records.

The current HNT rows classify `fists` as a zero-price, zero-slot melee weapon, supporting the base-item hypothesis but not proving intended UI behavior. The three Cowboy Beetles are active `consumable:recon` rows with prices 22, 57 and 45. wiki.gg's Choke Beetle page lists Cowboy Choke Beetle in its gallery; corresponding Fire and Stalker mapping still needs a source check. Do not collapse or remove these rows from that observation alone.

`maxim-m1895`, `sledgehammer`, `wood-axe`, `shovel` and `pitchfork` retain `item_type=weapon` for API compatibility. Their wiki pages are under `World_Items/`. A reviewed subtype/category is preferred over a new top-level `world_item` type, because clients already filter the three established top-level types. No subtype has been populated automatically.

The current HNT rows already have `category=World` for all five. Existing user loadouts store weapon labels as strings rather than foreign keys to `equipment_items`, while the Arsenal API and React types enumerate weapon/tool/consumable. The current `weapon:rifle` comparison group for Maxim may need an explicit world-item comparison rule later; no group is changed in this phase.

### wiki.gg media policy

The public API continues to expose local HNT storage paths only; original wiki URLs remain private metadata. Media dry-run reports base and skin actions with match and image confidence. Exact skin-name matching is preferred; unique high-confidence token matching can follow. Ambiguous matches and inferred image files need review. Existing local images are skipped when the source hash is unchanged; changed or unknown hashes require review before replacement. The media pipeline stores source page, file name and URL, description URL, revision, MIME, SHA1, import time and available rights metadata in namespaced image facts. No image is automatically deleted or overwritten. The apply command requires the reviewed wiki revision.

### Controlled test set and bulk prerequisites

The proposed maximum-five server test set is `drilling` (base weapon), `drilling-hatchet` (variant), `throwing-spear` (tool), `frag-bomb` (consumable) and `1865-carbine` (skin-rich weapon). All five resolved directly in the audit without a family mismatch. The Drilling family has a base weapon, variant and at least three existing skins, making it suitable for a separate media dry-run. Each write must follow a record-level backup, revision-pinned plan review, single-item apply, DB verification and API verification.

A bulk import requires reviewed family aliases, a documented provenance policy for relations, verified image matching and rights metadata, successful small-item tests, an approved DB backup and an explicit deployment decision. The new migration is required before any Phase 2 import command can write on the server.
