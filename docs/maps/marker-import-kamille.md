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

Phase 1 has no execution route or command. The additive migration is prepared but must not be run on the production database in this phase. Cash markers, including community-submission markers, are outside all selectable import categories.

## Phase 2 gates

1. Review source usage rights and the seven out-of-bounds entries; keep external attribution visible.
2. Review the phase 1 preview against the real production database and explicitly approve the additive migration before running it there.
3. Build a separate, explicitly gated execution service using the `(hnt_map_id, source_provider, source_key)` unique identity, compare content hashes and enforce selected-category and cash-submission exclusions in the write path.
4. Review and separately approve any replacement of legacy `tower`, `bugs` and `wild` markers. No automatic legacy deletion belongs to the importer.
5. Align the separate React map filter's fallback colors with the registry when imported marker display is enabled there. Its current filter reads the backend's dynamic `markerTypes` list, but its color lookup still contains fixed values for legacy types.
