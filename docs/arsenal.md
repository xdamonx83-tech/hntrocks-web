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
