<?php

namespace Tests\Feature;

use App\Models\EquipmentFamily;
use App\Models\EquipmentFamilyAlias;
use App\Models\EquipmentFieldProvenance;
use App\Models\EquipmentItem;
use App\Models\EquipmentSkin;
use App\Models\EquipmentSource;
use App\Models\EquipmentSourceSnapshot;
use App\Models\EquipmentStatDefinition;
use App\Services\Equipment\EquipmentSnapshotDiffService;
use App\Services\Equipment\EquipmentSourceSnapshotService;
use App\Services\Equipment\EquipmentStatCatalog;
use App\Services\Equipment\EquipmentSourceInterface;
use App\Services\Equipment\EquipmentSyncService;
use App\Services\Equipment\WikiGgImportService;
use App\Services\Equipment\WikiGgEquipmentSource;
use App\Services\Equipment\WikiGgMediaImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArsenalCanonicalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/2026_09_30_120000_create_equipment_tables.php',
            'database/migrations/2026_10_01_150000_add_facts_to_equipment_skins.php',
            'database/migrations/2026_10_01_200000_create_equipment_canonical_foundation.php',
        ]];
    }

    protected function setUp(): void
    {
        parent::setUp();
        app(EquipmentStatCatalog::class)->ensure();
    }

    private function item(): EquipmentItem
    {
        $source = EquipmentSource::create(['key' => 'huntify', 'name' => 'Huntify']);
        $family = EquipmentFamily::create(['key' => 'mosin-nagant-m1891', 'name' => 'Mosin-Nagant M1891']);
        return EquipmentItem::create([
            'source_id' => $source->id,
            'external_id' => 'mosin',
            'slug' => 'mosin-nagant',
            'name' => 'Mosin-Nagant',
            'item_type' => 'weapon',
            'comparison_group' => 'weapon:rifle',
            'family_id' => $family->id,
            'price' => 100,
            'source_status' => 'active',
            'facts' => ['release_pack' => 'existing'],
        ]);
    }

    private function wiki(array $overrides = []): array
    {
        return array_replace([
            'page_title' => 'Weapons/Mosin-Nagant',
            'page_url' => 'https://huntshowdown.wiki.gg/wiki/Weapons/Mosin-Nagant',
            'revision_id' => 12345,
            'revision_timestamp' => '2026-10-01T12:00:00Z',
            'resolution_method' => 'direct',
            'resolution_score' => 100,
            'family' => 'Mosin-Nagant',
            'name' => 'Mosin-Nagant',
            'price' => 120,
            'stats' => ['dropRange' => 115, 'damage' => 145],
            'recommended_traits' => [],
            'skins' => [],
            'patch_history' => [['patch' => 'Update 2.8', 'note' => 'Source prose']],
        ], $overrides);
    }

    public function test_drop_range_is_separate_and_existing_effective_range_is_untouched(): void
    {
        $item = $this->item();
        $effective = EquipmentStatDefinition::where('key', 'effectiveRange')->firstOrFail();
        $item->stats()->create(['stat_definition_id' => $effective->id, 'value' => 150]);

        $plan = app(WikiGgImportService::class)->plan($item, $this->wiki());
        $drop = collect($plan['rows'])->firstWhere('field', 'stat.dropRange');
        $this->assertSame('CREATE', $drop['action']);
        $this->assertNull(collect($plan['rows'])->firstWhere('field', 'stat.effectiveRange'));

        app(WikiGgImportService::class)->apply($item, $this->wiki());
        $this->assertEquals(150, $item->stats()->where('stat_definition_id', $effective->id)->value('value'));
        $dropDefinition = EquipmentStatDefinition::where('key', 'dropRange')->firstOrFail();
        $this->assertEquals(115, $item->stats()->where('stat_definition_id', $dropDefinition->id)->value('value'));
        $this->assertSame('Mosin-Nagant M1891', $item->fresh()->family->name);
    }

    public function test_manual_override_and_unowned_value_are_blocked_while_alias_confirms_family(): void
    {
        $item = $this->item();
        EquipmentFieldProvenance::create([
            'equipment_item_id' => $item->id, 'field_key' => 'price',
            'source_key' => 'manual', 'is_manual_override' => true,
        ]);
        EquipmentFamilyAlias::create([
            'equipment_family_id' => $item->family_id,
            'source_key' => 'wiki_gg', 'alias' => 'Mosin-Nagant',
            'alias_key' => EquipmentFamilyAlias::keyFor('Mosin-Nagant'),
        ]);
        $plan = app(WikiGgImportService::class)->plan($item, $this->wiki());
        $this->assertSame('BLOCKED_MANUAL', collect($plan['rows'])->firstWhere('field', 'price')['action']);
        $this->assertSame('UNCHANGED', collect($plan['rows'])->firstWhere('field', 'family')['action']);
        app(WikiGgImportService::class)->apply($item, $this->wiki());
        $this->assertSame(100, $item->fresh()->price);

        $item->provenance()->where('field_key', 'price')->delete();
        $this->assertSame('REVIEW_REQUIRED', collect(app(WikiGgImportService::class)->plan($item->fresh(), $this->wiki())['rows'])
            ->firstWhere('field', 'price')['action']);
    }

    public function test_snapshots_deduplicate_are_immutable_and_produce_structured_diffs(): void
    {
        $item = $this->item();
        $service = app(EquipmentSourceSnapshotService::class);
        $first = $service->recordWiki($item, $this->wiki());
        $same = $service->recordWiki($item, $this->wiki());
        $this->assertSame($first->id, $same->id);
        $this->assertSame('12345', $first->source_revision_id);
        $this->assertStringNotContainsString('Source prose', json_encode($first->normalized_payload));

        $second = $service->recordWiki($item, $this->wiki([
            'revision_id' => 12346,
            'stats' => ['dropRange' => 115, 'damage' => 150],
        ]));
        $this->assertSame(2, EquipmentSourceSnapshot::count());
        $diff = app(EquipmentSnapshotDiffService::class)->diff($first, $second);
        $this->assertSame([['field' => 'stat.damage', 'old_value' => 145, 'new_value' => 150,
            'source_key' => 'wiki_gg', 'from_snapshot_id' => $first->id, 'to_snapshot_id' => $second->id]], $diff);
        $this->expectException(\LogicException::class);
        $first->update(['payload_hash' => str_repeat('0', 64)]);
    }

    public function test_low_confidence_and_unresolved_items_never_change_canonical_state(): void
    {
        $item = $this->item();
        $wiki = $this->wiki(['resolution_score' => 55]);
        $plan = app(WikiGgImportService::class)->plan($item, $wiki);
        $this->assertSame('BLOCKED_AMBIGUOUS', collect($plan['rows'])->firstWhere('field', 'stat.damage')['action']);
        app(WikiGgImportService::class)->apply($item, $wiki);
        $this->assertSame(100, $item->fresh()->price);
        $this->assertSame('active', $item->fresh()->source_status);
        $this->assertSame(0, $item->stats()->count());
    }

    public function test_unknown_stat_and_search_fallback_are_review_only(): void
    {
        $item = $this->item();
        $wiki = $this->wiki([
            'resolution_method' => 'search',
            'resolution_score' => 91,
            'stats' => ['damage' => 145, 'unknownStat' => 42],
        ]);
        $rows = collect(app(WikiGgImportService::class)->plan($item, $wiki)['rows']);
        $this->assertSame('REVIEW_REQUIRED', $rows->firstWhere('field', 'stat.damage')['action']);
        $this->assertSame('SKIP', $rows->firstWhere('field', 'source_stat.unknownStat')['action']);
        app(WikiGgImportService::class)->apply($item, $wiki);
        $this->assertSame(0, $item->stats()->count());
        $this->assertFalse(EquipmentStatDefinition::where('key', 'unknownStat')->exists());
    }

    public function test_media_plan_preserves_local_image_and_requires_confident_skin_match(): void
    {
        $item = $this->item();
        $item->update([
            'local_asset_path' => 'arsenal/items/manual.webp',
            'facts' => ['wiki_gg' => ['image' => ['source_sha1' => 'same']]],
        ]);
        EquipmentSkin::create([
            'equipment_item_id' => $item->id, 'external_id' => 'skin-1',
            'name' => 'Spirit Caller', 'local_asset_path' => 'arsenal/skins/manual.webp',
        ]);
        $wiki = $this->wiki([
            'base_image_confidence' => 100,
            'base_image' => ['url' => 'https://huntshowdown.wiki.gg/image.png', 'sha1' => 'same'],
            'skins' => [[
                'name' => 'Spirit Caller', 'image_confidence' => 100,
                'image_file' => 'Spirit Caller.png',
                'image' => ['url' => 'https://huntshowdown.wiki.gg/skin.png', 'sha1' => 'changed'],
            ]],
        ]);
        $plan = app(WikiGgMediaImportService::class)->plan($item, $wiki);
        $this->assertSame('SKIP', $plan['base']['action']);
        $this->assertSame('MATCH', $plan['skins'][0]['action']);
        $this->assertSame(100, $plan['skins'][0]['match_confidence']);
        $this->assertSame('REVIEW_REQUIRED', $plan['skins'][0]['image_action']);
        $wiki['base_image']['sha1'] = 'changed';
        $base = app(WikiGgMediaImportService::class)->plan($item, $wiki)['base'];
        $this->assertSame('REVIEW_REQUIRED', $base['action']);
        $this->assertSame('Existing local image must be reviewed before replacement', $base['reason']);
        $wiki['skins'][0]['image_confidence'] = 65;
        $this->assertSame('REVIEW_REQUIRED', app(WikiGgMediaImportService::class)->plan($item, $wiki)['skins'][0]['image_action']);
    }

    public function test_drilling_exact_skin_filenames_are_confident_but_near_matches_and_duplicates_require_review(): void
    {
        $item = $this->item();
        $item->update(['name' => 'Drilling', 'slug' => 'drilling', 'local_asset_path' => 'arsenal/items/drilling.webp']);
        foreach (['Corrosion', "Soldier's Brother", 'Tomb Reign', 'Celestial Scream'] as $index => $name) {
            EquipmentSkin::create(['equipment_item_id' => $item->id, 'external_id' => 'drilling-skin-'.$index, 'name' => $name]);
        }

        $files = [
            'Model Drilling Corrosion.jpg',
            'Weapon 3D Drilling Corrosion.jpg',
            "Weapon 3D Drilling Soldier's Brother.jpg",
            'Weapon 3D Drilling Tomb Reign.jpg',
            'Model Drilling Celestial Scream.jpg',
            'Artwork Drilling Celestial Scream Skin Set.jpg',
            'Weapon 3D Drilling Celestial Scream.jpg',
        ];
        $wiki = $this->drillingImagePreview($item, $files);
        $this->assertSame([100, 100, 100, 100], array_column($wiki['skins'], 'image_confidence'));
        $plan = app(WikiGgMediaImportService::class)->plan($item, $wiki);
        $this->assertSame([100, 100, 100, 100], array_column($plan['skins'], 'match_confidence'));
        $this->assertSame(['IMPORT', 'IMPORT', 'IMPORT', 'IMPORT'], array_column($plan['skins'], 'image_action'));
        $this->assertSame('REVIEW_REQUIRED', $plan['base']['action']);
        $this->assertSame('Existing local image must be reviewed before replacement', $plan['base']['reason']);

        $near = $this->drillingImagePreview($item, ['Weapon 3D Drilling Corrosion Gold.jpg']);
        $this->assertLessThan(85, $near['skins'][0]['image_confidence']);
        $this->assertSame('REVIEW_REQUIRED', app(WikiGgMediaImportService::class)->plan($item, $near)['skins'][0]['image_action']);

        $duplicates = $this->drillingImagePreview($item, [
            'Weapon 3D Drilling Corrosion.jpg', 'Weapon_3D_Drilling_Corrosion.png',
        ]);
        $this->assertLessThan(85, $duplicates['skins'][0]['image_confidence']);
        $this->assertSame('REVIEW_REQUIRED', app(WikiGgMediaImportService::class)->plan($item, $duplicates)['skins'][0]['image_action']);

        foreach (['Weapon 3D Romero Corrosion.jpg', 'Weapon 3D Drilling Corrosion Icon.jpg'] as $unsafe) {
            $preview = $this->drillingImagePreview($item, [$unsafe]);
            $this->assertLessThan(85, $preview['skins'][0]['image_confidence']);
        }
    }

    private function drillingImagePreview(EquipmentItem $item, array $skinFiles): array
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        $skins = implode("\n", array_map(
            fn (string $name) => "{{Infobox Weapon Skin\n|Title={$name}\n}}",
            ['Corrosion', "Soldier's Brother", 'Tomb Reign', 'Celestial Scream'],
        ));
        Http::fake(function ($request) use ($skinFiles, $skins) {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);
            if (($query['action'] ?? null) === 'parse') {
                return Http::response(['parse' => [
                    'wikitext' => "{{Infobox Weapon\n|Title=Drilling\n|image=Weapon Drilling.png\n}}\n".$skins,
                    'text' => '',
                    'images' => array_merge(['Weapon Drilling.png'], $skinFiles),
                ]]);
            }
            $titles = explode('|', (string) ($query['titles'] ?? ''));
            return Http::response(['query' => ['pages' => array_map(fn (string $title) => [
                'title' => $title,
                'imageinfo' => [[
                    'url' => 'https://huntshowdown.wiki.gg/images/'.rawurlencode($title),
                    'mime' => 'image/jpeg', 'width' => 1200, 'height' => 400,
                    'sha1' => sha1($title),
                ]],
            ], $titles)]]);
        });

        return app(WikiGgEquipmentSource::class)->preview($item, true, false);
    }

    public function test_huntify_resync_preserves_manual_and_other_source_values(): void
    {
        $feed = function (int $price, int $damage): EquipmentSourceInterface {
            return new class($price, $damage) implements EquipmentSourceInterface {
                public function __construct(private int $price, private int $damage) {}
                public function key(): string { return 'huntify'; }
                public function name(): string { return 'Huntify'; }
                public function baseUrl(): string { return 'https://example.test'; }
                public function items(): iterable {
                    yield ['id' => 'carbine', 'name' => 'Carbine', '_item_type' => 'weapon',
                        'weaponType' => 'Rifle', 'category' => 'Rifle', 'cost' => $this->price,
                        'stats' => ['combat' => ['damage' => $this->damage]],
                        'ammo' => [['variant' => 'Basic', 'caliber' => 'Medium', 'damage' => $this->damage,
                            'speed' => 340, 'loaded' => 8, 'reserve' => 20,
                            'envelope' => [[10, 1], [100, .5]]]]];
                }
            };
        };
        $sync = app(EquipmentSyncService::class);
        $this->assertSame(1, $sync->sync($feed(100, 145))['new']);
        $item = EquipmentItem::where('external_id', 'carbine')->firstOrFail();
        $damageId = EquipmentStatDefinition::where('key', 'damage')->value('id');
        EquipmentFieldProvenance::query()->where('equipment_item_id', $item->id)->where('field_key', 'price')
            ->update(['source_key' => 'manual', 'is_manual_override' => true]);
        EquipmentFieldProvenance::query()->where('equipment_item_id', $item->id)->where('field_key', 'stat.damage')
            ->update(['source_key' => 'wiki_gg', 'is_manual_override' => false]);
        EquipmentFieldProvenance::create([
            'equipment_item_id' => $item->id, 'field_key' => 'ammo.basic-medium-0.damage',
            'source_key' => 'manual', 'is_manual_override' => true,
        ]);
        $item->update(['facts' => array_merge($item->facts, ['wiki_gg' => ['revision_id' => 123]])]);
        $sync->sync($feed(120, 150));
        $this->assertSame(100, $item->fresh()->price);
        $priceId = EquipmentStatDefinition::where('key', 'price')->value('id');
        $this->assertEquals(100, $item->stats()->where('stat_definition_id', $priceId)->value('value'));
        $this->assertEquals(145, $item->stats()->where('stat_definition_id', $damageId)->value('value'));
        $this->assertEquals(145, $item->ammo()->where('key', 'basic-medium-0')->value('damage'));
        $this->assertEquals(72.5, $item->ammo()->where('key', 'basic-medium-0')->firstOrFail()
            ->falloffPoints()->where('distance', 100)->value('damage'));
        $this->assertSame(123, data_get($item->fresh()->facts, 'wiki_gg.revision_id'));
    }

    public function test_invalid_source_payload_rolls_back_without_canonical_change(): void
    {
        $item = $this->item();
        $wiki = $this->wiki(['skins' => [['name' => 'Broken', 'price' => INF]]]);
        try {
            app(WikiGgImportService::class)->apply($item, $wiki);
            $this->fail('Expected invalid source payload to fail.');
        } catch (\JsonException) {
            $this->assertSame(100, $item->fresh()->price);
            $this->assertSame(0, EquipmentSourceSnapshot::count());
            $this->assertSame(0, $item->stats()->count());
        }
    }

    public function test_legacy_huntify_item_without_reviewed_baseline_is_not_overwritten(): void
    {
        $item = $this->item();
        $feed = new class implements EquipmentSourceInterface {
            public function key(): string { return 'huntify'; }
            public function name(): string { return 'Huntify'; }
            public function baseUrl(): string { return 'https://example.test'; }
            public function items(): iterable {
                yield ['id' => 'mosin', 'name' => 'Mosin-Nagant', '_item_type' => 'weapon',
                    'category' => 'Rifle', 'weaponType' => 'Rifle', 'cost' => 999,
                    'stats' => ['combat' => ['damage' => 999]]];
            }
        };
        $result = app(EquipmentSyncService::class)->sync($feed);
        $this->assertSame(1, $result['errors']);
        $this->assertSame(100, $item->fresh()->price);
        $this->assertSame(0, $item->stats()->count());
        $this->assertSame('active', $item->fresh()->source_status);
    }

    public function test_confident_media_import_uses_local_storage_and_preserves_source_metadata(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        Http::fake(['https://huntshowdown.wiki.gg/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);
        $item = $this->item();
        $skin = EquipmentSkin::create(['equipment_item_id' => $item->id, 'external_id' => 'skin-1', 'name' => 'Spirit Caller']);
        $image = ['file' => 'Spirit Caller.png', 'url' => 'https://huntshowdown.wiki.gg/skin.png',
            'description_url' => 'https://huntshowdown.wiki.gg/wiki/File:Spirit_Caller.png',
            'mime' => 'image/png', 'sha1' => 'source-sha1', 'license' => 'reviewed'];
        $wiki = $this->wiki([
            'base_image_file' => 'Mosin.png', 'base_image_confidence' => 100,
            'base_image' => array_replace($image, ['file' => 'Mosin.png', 'url' => 'https://huntshowdown.wiki.gg/base.png']),
            'skins' => [['name' => 'Spirit Caller', 'image_file' => 'Spirit Caller.png',
                'image_confidence' => 100, 'image' => $image]],
        ]);
        $result = app(WikiGgMediaImportService::class)->apply($item, $wiki);
        $this->assertSame(1, $result['base_image']);
        $this->assertSame(1, $result['skin_images_cached']);
        $this->assertSame(1, EquipmentSourceSnapshot::count());
        Storage::disk('public')->assertExists($item->fresh()->local_asset_path);
        Storage::disk('public')->assertExists($skin->fresh()->local_asset_path);
        $this->assertSame('wiki_gg', data_get($skin->fresh()->facts, 'wiki_gg.image.source_key'));
        $this->assertSame('source-sha1', data_get($skin->fresh()->facts, 'wiki_gg.image.source_sha1'));
        $this->assertSame('SKIP', app(WikiGgMediaImportService::class)->plan($item->fresh(), $wiki)['base']['action']);
    }
}
