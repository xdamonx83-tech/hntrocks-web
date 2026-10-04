# Kamille marker source: phase 1 analysis

Checked on 2026-10-03 against the live structured source. No screenshots, map images, icons, descriptions or image URLs are copied into this repository.

## Endpoints and format

- Map list: `https://hunt.kamille.ovh/api/maps` (`maps[]`, IDs 1–4).
- Category manifest: `https://hunt.kamille.ovh/maps/cache/poi-types.json` (type keys, `categories`, labels and style metadata).
- Marker data: `https://hunt.kamille.ovh/maps/cache/data-{1,2,3,4}.json` (one JSON object per map; category arrays contain marker objects).
- The live page's JavaScript loads these cache JSONs. The importer fetches only the manifest and marker JSON, with a connection timeout of 4 seconds and a total timeout of 12 seconds per request.
- Selected marker entries have an `id` of 12 alphanumeric characters and a `c` pair. The `id` is used as `source_key`; no coordinate-derived identity is needed. All 502 selected-category entries had IDs unique within their map at review time. Future source behavior is still checked at parse time.
- Fields such as `u`, `d`, screenshots, image references and longer descriptions are ignored.

The manifest currently lists 23 types: `spawn`, `extraction`, `bounty_clash_extraction`, `armory`, `tower`, `big_tower`, `scout_tower`, `workbench`, `crashed_balloon`, `wild_target`, `brute`, `beetle`, `easter_egg`, `melee_weapon`, `cash_register`, `compound`, `supply_point`, `postal_supply`, `clockmaker_supply`, `firefighter_supply`, `medical_supply`, `military_supply` and `bileweaver_compound`. Only the selected mappings below are proposed for phase 1.

## Map and category mapping

| Source map ID | Source name | HNT slug |
| --- | --- | --- |
| 1 | Stillwater Bayou | `stillwater-bayou` |
| 2 | Lawson Delta | `lawson-delta` |
| 3 | DeSalle | `desalle` |
| 4 | Mammon's Gulch | `mammons-gulch` |

| Source category | Source label | HNT type | HNT subtype |
| --- | --- | --- | --- |
| `easter_eggs` | Easter Eggs | `easter_egg` | — |
| `wild_targets`, `boss=rotjaw` | Wild Targets | `wild_target` | `rotjaw` |
| `wild_targets`, `boss=hellborn` | Wild Targets | `wild_target` | `hellborn` |
| `brutes` | Brutes | `beast` | — |
| `beetles` | Beetles | `beetle` | — |
| `towers` | Hunting Towers | `tower` | `hunting` |
| `big_towers` | Watch Towers | `tower` | `watch` |
| `scout_towers` | Scout Towers | `tower` | `scout` |
| `workbenches` | Workbenches | `workbench` | — |

An absent or unknown `boss` value remains an unclassified wild target in the preview and is not assigned a subtype or proposed for import. Unexpected categories are skipped and logged. Missing expected categories, duplicate/missing IDs and malformed coordinates abort the preview.

## Coordinates

The live JavaScript uses Leaflet `CRS.Simple` with bounds `[[0,0],[4096,4096]]`. Marker `c` is `[latitude, longitude]`, meaning `[vertical Y, horizontal X]`. Both source and HNT use the southwest map corner as `(0,0)`, with X increasing right and Y increasing upward. HNT maps are 2048 × 2048. The verified transform is `hnt_x = c[1]/2`, `hnt_y = c[0]/2`; there is no rotation or reflection.

Independent existing HNT tower positions corroborate this on all four maps (source `c` → existing HNT `x,y`):

| Map | Source `c` | Existing HNT tower | Distance after transform |
| --- | --- | --- | ---: |
| Stillwater Bayou | `[850,1737]` | `[869.188,424.157]` | 1.09 px |
| Lawson Delta | `[2389,965]` | `[482.609,1195.13]` | 0.64 px |
| DeSalle | `[3700,2812]` | `[1408.43,1850.33]` | 2.45 px |
| Mammon's Gulch | `[1315,3334]` | `[1665.94,659.386]` | 2.16 px |

Existing compound labels were not suitable as precise anchors: several point to differing positions inside a compound. Tower matches were used to verify scale and orientation.

Seven Easter Eggs in the current source have coordinates outside `[0,4096]` (Lawson Delta 2, DeSalle 2, Mammon's Gulch 3). They are counted separately as out of bounds, skipped and never transformed by extrapolation. A source key skipped for this reason is not counted as “externally removed.”

## Phase 1 live-source preview

Run with the preview service against a fresh **isolated in-memory SQLite database**, with all four maps and all desired categories selected. These are source-side proposed additions, not a preview against the production database:

| Map | New | Changed | Unchanged | Externally removed | Unclassified | Out of bounds |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Stillwater Bayou | 123 | 0 | 0 | 0 | 0 | 0 |
| Lawson Delta | 123 | 0 | 0 | 0 | 0 | 2 |
| DeSalle | 131 | 0 | 0 | 0 | 0 | 2 |
| Mammon's Gulch | 118 | 0 | 0 | 0 | 0 | 3 |
| **Total** | **495** | **0** | **0** | **0** | **0** | **7** |

The public HNT Maps API reported current `tower`/`bugs`/`wild` counts of 12/6/18, 16/8/19, 15/5/13 and 6/7/10 for the four maps respectively. These are read-only API counts, not direct production database counts. The preview service queries the current database for its own legacy counts when run in the Admin Center.

Phase 1 had no execution route or command. The additive migration was prepared but has not been run on the production database. Cash markers, including community-submission markers, are outside all selectable import categories.

## Phase 2A write path (code and isolated tests only)

The Admin Center now has a separate POST execution route. After a read-only preview, the server stores the selection, source fingerprint, legacy counts, administrator ID and creation time in the session for at most ten minutes. The form requires a checked preview acknowledgement and the exact word `IMPORT`. Execution fetches the structured source again, rebuilds the normalized source plan and aborts before writing if its fingerprint differs. A failed source fetch or format check also aborts.

`MapMarkerImportExecutor` then writes only selected maps and categories in one database transaction. It identifies external markers by map, provider and source key, creates missing markers, updates changed markers in `sync` mode, leaves changed markers alone in `add_only` mode, and reports externally missing markers without deleting them. Unknown wild-target subtypes and out-of-bounds points remain skipped. The result contains per-map, per-category and total counts. No screenshots, images or descriptions are stored.

`MapMarkerImportProtection` blocks every write or legacy deletion for a marker of type `cash`, a `submission:` legacy key or a marker linked from `HntMapCashSpotSubmission`. The service refuses execution if its identity migration or the cash-submission table is absent. Legacy replacement is a separate optional choice, unchecked by default, requiring its own acknowledgement and `ERSETZEN`. It requires all source subtypes of the chosen target category, checks the legacy count against the confirmed preview, verifies every expected imported marker matches the source, and only then deletes selected-map `tower`, `bugs` or `wild` rows with `source_provider IS NULL` in the same transaction. A failed import or check rolls back the full run.

The prepared migration remains unchanged. Its nullable identity columns preserve legacy rows; the composite unique index prevents duplicates for non-null provider and source key. The key width is 64 + 120 characters plus map ID, within the common 767-byte utf8mb4 index limit (64×4 + 120×4 + 8 = 744 bytes). Rollback drops the index and columns. It has not been applied to production.

The separate React map filter already reads dynamic `markerTypes`, but its fallback color lookup still has fixed legacy type values. Before imported markers are displayed in the React redesign, map `easter_egg`, `workbench`, `beast`, `beetle`, `wild_target` and `tower` subtypes to registry/API colors and fallback circles in the verified current React checkout; preserve existing cash details, votes, comments and other map controls. No React repository files were changed in this phase.

## Phase 2B database-state gate and read-only command

The preview also computes a deterministic `database_fingerprint` from selected-provider rows in selected maps/categories and all legacy `tower`, `bugs` and `wild` rows in those maps. It includes row identity, source key/category, type/subtype, payload hash, position, labels, status and raw `updated_at`. Protected cash and submission markers are excluded from this importable state. The session stores this fingerprint alongside the source fingerprint. After reloading and validating the source, execution recalculates the database fingerprint before opening its write transaction and refuses a stale plan. Cash changes and unrelated categories do not invalidate a selected-category preview.

`php artisan hnt:maps:marker-import-preview --provider=kamille --all-maps --all-supported --mode=sync --json` uses only the existing read-only preview service. It never migrates, imports, updates, deletes or replaces markers. The JSON includes `READ ONLY / DRY RUN`, per-map category counts, legacy counts and distinct cash/submission protection counts. `--map` and `--category` can narrow a run. A production run requires an explicitly verified production configuration and the additive migration; neither is initiated by this command.

## Production gates after Phase 2A

1. Review source usage rights and the seven out-of-bounds entries; keep external attribution visible.
2. Explicitly approve the additive migration, apply it on production only in a separately authorized run, and check the schema/index result.
3. Create and review a fresh dry run against the production database, including counts, protected markers, skipped points and selected categories.
4. Separately approve and perform the first import only after that review. Confirm the created/updated/unchanged counts and public/API marker rendering.
5. Separately review and approve each desired legacy replacement with fresh counts. Keep the replacement options off until this review is complete.
6. Align the separate React map filter colors and circle fallbacks on its verified current server branch before enabling imported markers there.


## Optional import categories (2026-10-04)

The admin importer now also exposes structured source markers for HNT spawn, extraction
and supply types. These are opt-in by default; all previous category choices remain.

| Source category | HNT type | HNT subtype |
| --- | --- | --- |
| `spawns` | `spawn` | — |
| `extractions` | `extract` | `standard` |
| `bounty_clash_extractions` | `extract` | `bounty_clash` |
| `supply_points` | `supply` | `standard` |
| `postal_supplies` | `supply` | `postal` |
| `clockmaker_supplies` | `supply` | `clockmaker` |
| `firefighter_supplies` | `supply` | `firefighter` |
| `medical_supplies` | `supply` | `medical` |
| `military_supplies` | `supply` | `military` |

For new source IDs of types `spawn`, `extract` and `supply`, a preexisting same-type
HNT marker is considered a duplicate if within 7, 7 or 6 HNT map pixels, respectively.
This includes preexisting manual/legacy markers and imported markers from other
providers. Duplicates are **counted in the read-only preview** and skipped during
execution. Multiple nearby source IDs within one run are likewise deduplicated.
Existing HNT markers are not automatically matched, adopted, moved, overwritten or
deleted by proximity. Re-importing an already saved source ID continues to use its
stable source identity. Database fingerprints now include spatial candidate markers
for the selected types, preventing a stale preview from overriding newly changed
nearby HNT content. Cash/submission markers remain protected as before.

There is no automatic live import. Execute only after reviewing the selected maps,
category and duplicate counts in the admin preview, with the normal explicit
`IMPORT` confirmation; leave all optional legacy replacement boxes unchecked.


## Optional complete replacement of legacy HNT supplies (2026-10-04)

The admin import preview now separately shows the normal import counts and an
additional *supply replacement simulation* for each map. This option only
appears as enabled after selecting **Sync** and all six supply source
categories (or the parent Supply category). It defaults to **off** and needs
both the normal `IMPORT` approval and separate `ERSETZEN` confirmation.

If explicitly enabled, spatial duplicate detection excludes **only approved,
unprotected, provider-less legacy HNT `supply` rows**: these old markers are
about to be removed, so they must not suppress the Kamille replacements.
Newer or third-party provider-owned supplies and protected or pending HNT
markers continue to block nearby duplicates.

Execution then checks fresh source and database fingerprints, revalidates all
selected Kamille supply IDs (allowing nearby IDs to share a verified imported
Kamille marker within 6 HNT pixels), checks bounds and the previewed legacy
counts, and verifies every imported replacement. It refuses deletion if any
protected or unapproved legacy supply exists, an untrusted competing provider
blocks coverage, or any validation fails. In those cases the **entire
transaction rolls back**, including any new marker insertions. Only
`type=supply` rows with `source_provider IS NULL` on selected maps are
deleted after all checks. Other marker types, cash and linked community
submissions are never deleted or replaced. No live import or legacy deletion
is part of deploying this code.
