<?php

// Curated from the public Bayou Index 1865 page and its public, versioned JS.
// The zero-range/gravity model is Bayou's published calculation, not a claim
// that Hunt's internal flight rules or head damage multiplier are verified.
return [
    'slug' => '1865-carbine',
    'observed_at' => '2026-10-03',
    'source_url' => 'https://bayouindex.com/weapons/1865-carbine/',
    'data_url' => 'https://bayouindex.com/_app/immutable/chunks/DvIjM2m5.js',
    'data_sha256' => '28766b55af11b70931dfde72113279c040a6e1511ddaccdef6d35c3f96a0f3a7',
    'model_url' => 'https://bayouindex.com/_app/immutable/chunks/Bxa8H_2i.js',
    'model_sha256' => '7b89ebff0467f418801965d7c17f2e6492eb537770139213ea8b34cbb1a48cca',
    'page_module_url' => 'https://bayouindex.com/_app/immutable/nodes/45.es6QUS6r.js',
    'page_module_sha256' => 'd8a90c316f60fba3365bb0748c10c61b881d27a77692b1e5211e8b677b48ee76',
    'source_build_id' => '25344406',
    'source_generated_at' => '2026-10-01',
    'reference_aim' => 'top_of_head',
    'head_size_m' => 0.2235,
    // Midpoints between the public hunterBody zone edges, relative to head top.
    'zone_offsets_m' => ['head' => 0.0, 'upper_torso' => 0.2490, 'torso' => 0.6646, 'legs' => 0.8941],
    'shared_stats' => [
        'baseDamage' => 112.0,
        'upperTorsoMultiplier' => 1.3,
        'torsoMultiplier' => 1.2,
        'armMultiplier' => 0.9,
        'legMultiplier' => 0.8,
    ],
    'modes' => [
        'basic-medium-0' => [
            'source_ammo_id' => 'DD63778130000',
            'source_name' => 'Stock',
            'velocity_mps' => 340.0,
            'loaded' => 7, 'reserve' => 21,
            'card_damage' => 145.0, 'display_damage' => 146.0,
            'card_drop_range_m' => 115.0, 'computed_drop_range_m' => 117.0,
            'falloff' => [[30, 1.0], [80, 0.64], [130, 0.51]],
        ],
        'fullmetaljacket-medium-1' => [
            'source_ammo_id' => 'DD63485EAF267',
            'source_name' => 'FMJ',
            'velocity_mps' => 272.0,
            'loaded' => 7, 'reserve' => 21,
            'card_damage' => 145.0, 'display_damage' => 146.0,
            'card_drop_range_m' => 105.0, 'computed_drop_range_m' => 103.0,
            'falloff' => [[40, 1.0], [90, 0.64], [130, 0.53]],
        ],
        'subsonic-medium-2' => [
            'source_ammo_id' => 'DD63A375A0000',
            'source_name' => 'Subsonic',
            'velocity_mps' => 242.0,
            'loaded' => 7, 'reserve' => 25,
            'card_damage' => 145.0, 'display_damage' => 146.0,
            'card_drop_range_m' => 95.0, 'computed_drop_range_m' => 96.0,
            'falloff' => [[30, 1.0], [80, 0.64], [130, 0.51]],
        ],
    ],
    'flight' => [
        'model' => 'bayou_public_zero_range_gravity_v1',
        'zero_range_m' => 45.0,
        'gravity_mps2' => -10.0,
        'pre_gravity_mps2' => 0.0,
        'air_resistance_per_s' => 0.0,
        'max_distance_m' => 221,
        'source_model_verified' => true,
        'game_rules_verified' => false,
    ],
];
