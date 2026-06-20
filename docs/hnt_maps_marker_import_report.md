# HNT Maps Marker Import Report
Total markers: **351**

## Types
- boss: 57
- bugs: 18
- cash: 29
- compound: 48
- spawn: 79
- supply: 46
- tarot: 2
- tower: 33
- wild: 39

## Maps
### stillwater-bayou

- Markers: 153
- Types: {'boss': 25, 'bugs': 6, 'cash': 10, 'compound': 16, 'spawn': 20, 'supply': 46, 'tarot': 2, 'tower': 12, 'wild': 16}

### lawson-delta

- Markers: 0
- Types: {}

### desalle

- Markers: 101
- Types: {'boss': 16, 'bugs': 5, 'cash': 10, 'compound': 16, 'spawn': 26, 'tower': 15, 'wild': 13}

### mammons-gulch

- Markers: 97
- Types: {'boss': 16, 'bugs': 7, 'cash': 9, 'compound': 16, 'spawn': 33, 'tower': 6, 'wild': 10}


## Notes
- Coordinates are copied as x=pos_x and y=pos_y from Huntmaps export.
- source_id is preserved for later DB/admin migration.
- source_image is preserved only as metadata for future cash screenshot migration; current frontend should not render it unless image assets exist.
- Lawson Delta export is still empty in the provided ZIP.
