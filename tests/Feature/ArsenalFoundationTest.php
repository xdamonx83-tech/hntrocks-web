<?php
namespace Tests\Feature;

use App\Models\{EquipmentItem,EquipmentSkin,EquipmentSourceSnapshot,EquipmentSyncChange,EquipmentSyncRun};
use App\Services\Equipment\{EquipmentSourceInterface,EquipmentSyncService};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ArsenalFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        // An unrelated legacy migration drops a foreign key by name, which SQLite cannot do.
        return ['--path'=>[
            'database/migrations/2026_09_30_120000_create_equipment_tables.php',
            'database/migrations/2026_10_01_200000_create_equipment_canonical_foundation.php',
        ]];
    }

    private function fixture(array $rows): EquipmentSourceInterface
    {
        return new class($rows) implements EquipmentSourceInterface {
            public function __construct(private array $rows) {}
            public function key(): string { return 'fixture'; }
            public function name(): string { return 'Fixture'; }
            public function baseUrl(): string { return 'https://example.test'; }
            public function items(): iterable { yield from $this->rows; }
        };
    }

    private function rows(): array
    {
        return [
            ['id'=>'carbine-1','name'=>'1865 Carbine','_item_type'=>'weapon','weaponType'=>'Rifle','category'=>'Rifle','family'=>'carbine',
                'caliber'=>'Medium','slots'=>3,'cost'=>120,'icon'=>'Hunt/assets/icons/weapons/carbine.webp',
                'stats'=>['combat'=>['damage'=>145,'magazine'=>8],'ballistics'=>['muzzleVelocity'=>340]],
                'ammo'=>[['variant'=>'Basic','caliber'=>'Medium','damage'=>145,'speed'=>340,'loaded'=>8,'reserve'=>20,'envelope'=>[[10,1],[100,.5]]]],
                'recommendedTraits'=>[['id'=>'iron_eye','name'=>'Iron Eye']]],
            ['id'=>'berthier-1','name'=>'Berthier','_item_type'=>'weapon','weaponType'=>'Rifle','category'=>'Rifle','family'=>'berthier',
                'caliber'=>'Long','slots'=>3,'cost'=>356,'stats'=>['combat'=>['damage'=>130],'ballistics'=>['muzzleVelocity'=>590]]],
            ['id'=>'pistol-1','name'=>'Pistol','_item_type'=>'weapon','weaponType'=>'Pistol','category'=>'Pistol','cost'=>55,
                'stats'=>['combat'=>['damage'=>90]]],
            ['id'=>'dusters','name'=>'Dusters','_item_type'=>'tool','category'=>'Melee','cost'=>15,'stats'=>['melee'=>30]],
            ['id'=>'knife','name'=>'Knife','_item_type'=>'tool','category'=>'Melee / Throwable','cost'=>20,'stats'=>['melee'=>50]],
            ['id'=>'first-aid-kit','name'=>'First Aid Kit','_item_type'=>'tool','category'=>'Healing','cost'=>30,'stats'=>[]],
            ['id'=>'frag-1','name'=>'Frag Bomb','_item_type'=>'consumable','category'=>'Explosive','cost'=>103,'stats'=>['throwDamage'=>150]],
            ['id'=>'dynamite-stick','name'=>'Dynamite Stick','_item_type'=>'consumable','category'=>'Explosive','cost'=>20,'stats'=>['throwDamage'=>200]],
            ['id'=>'vitality-shot','name'=>'Vitality Shot','_item_type'=>'consumable','category'=>'Restoration',
                'labels'=>['Boon'],'gameId'=>'2econsumableboostwc0019weakvitalityshot','cost'=>65,'stats'=>[]],
        ];
    }

    private function sync(array $rows, bool $dry = false, ?string $item = null): array
    {
        return app(EquipmentSyncService::class)->sync($this->fixture($rows),$dry,$item);
    }

    public function test_import_is_idempotent_and_slugs_stay_stable(): void
    {
        $this->assertSame(9,$this->sync($this->rows())['new']);
        $slug = EquipmentItem::where('external_id','carbine-1')->value('slug');
        $this->assertSame(9,$this->sync($this->rows())['unchanged']);
        $this->assertSame(9,EquipmentItem::count());
        $this->assertSame(9,EquipmentSourceSnapshot::count());
        $this->assertSame($slug,EquipmentItem::where('external_id','carbine-1')->value('slug'));
    }

    public function test_changed_item_updates_generated_text_but_preserves_manual_override(): void
    {
        $rows = $this->rows(); $this->sync($rows);
        $item = EquipmentItem::where('external_id','carbine-1')->firstOrFail();
        $item->translations()->where('locale','de')->update(['description'=>'Admin text','description_is_manual'=>true]);
        $rows[0]['stats']['combat']['damage'] = 150;
        $this->assertSame(1,$this->sync($rows)['changed']);
        $item->refresh();
        $this->assertSame('Admin text',$item->translations()->where('locale','de')->value('description'));
        $this->assertStringContainsString('150',$item->translations()->where('locale','en')->value('description'));
        $this->assertSame('1865-carbine',$item->slug);
        $this->assertTrue(EquipmentSyncChange::where('external_id','carbine-1')->where('field','stats')->exists());
    }

    public function test_missing_is_archived_and_partial_sync_does_not_archive(): void
    {
        $rows = $this->rows(); $this->sync($rows);
        $this->assertSame(0,$this->sync([$rows[0]],false,'carbine-1')['missing']);
        $this->assertSame(8,$this->sync([$rows[0]])['missing']);
        $this->assertSame(9,EquipmentItem::count());
        $this->assertSame('missing',EquipmentItem::where('external_id','berthier-1')->value('source_status'));
    }

    public function test_dry_run_has_no_database_writes(): void
    {
        $this->assertSame(9,$this->sync($this->rows(),true)['new']);
        $this->assertSame(0,EquipmentItem::count());
        $this->assertSame(0,EquipmentSyncRun::count());
        $this->sync($this->rows());
        $rows = $this->rows(); $rows[0]['cost'] = 999;
        $this->assertSame(1,$this->sync($rows,true)['changed']);
        $this->assertSame(120,EquipmentItem::where('external_id','carbine-1')->value('price'));
        $this->assertSame(1,EquipmentSyncRun::count());
    }

    public function test_api_list_detail_filter_related_and_compare(): void
    {
        $this->sync($this->rows());
        $this->getJson('/api/v1/arsenal?class=Rifle&per_page=1')->assertOk()->assertJsonPath('total',2)->assertJsonCount(1,'data');
        $this->getJson('/api/v1/arsenal?search=Carbine')->assertOk()->assertJsonPath('total',1);
        $this->getJson('/api/v1/arsenal/1865-carbine')->assertOk()->assertJsonStructure(['ammo'=>[['falloff_points'=>[['distance','damage']]]]]);
        $this->getJson('/api/v1/arsenal/1865-carbine/related')->assertOk()->assertJsonCount(1,'items');
        $this->getJson('/api/v1/arsenal/compare?items=1865-carbine,berthier')->assertOk()->assertJsonPath('compatible',true)
            ->assertJsonPath('comparison_group','weapon:rifle');
        $this->getJson('/api/v1/arsenal/compare?items=1865-carbine,pistol')->assertUnprocessable();
        $this->getJson('/api/v1/arsenal/compare?items=1865-carbine,berthier,pistol,frag-bomb')->assertUnprocessable();
        $this->getJson('/api/v1/arsenal/compare?items=1865-carbine,1865-carbine')->assertUnprocessable();
    }

    public function test_weapon_detail_uses_local_images_and_structured_relations(): void
    {
        Storage::fake('public');
        $this->sync($this->rows());
        $item = EquipmentItem::where('slug', '1865-carbine')->firstOrFail();
        $variant = EquipmentItem::where('slug', 'berthier')->firstOrFail();
        $variant->update(['family_id' => $item->family_id]);
        Storage::disk('public')->put('arsenal/items/carbine.webp', 'base');
        Storage::disk('public')->put('arsenal/wiki/skins/1865-carbine/skin.webp', 'skin');
        $item->update(['local_asset_path' => 'arsenal/items/carbine.webp']);
        EquipmentSkin::create([
            'equipment_item_id' => $item->id, 'external_id' => 'local-skin',
            'name' => 'Local Skin', 'rarity' => 'Legendary',
            'local_asset_path' => 'arsenal/wiki/skins/1865-carbine/skin.webp',
            'original_asset_url' => 'https://huntshowdown.wiki.gg/wiki/File:Skin.webp',
        ]);
        EquipmentSkin::create([
            'equipment_item_id' => $item->id, 'external_id' => 'missing-skin',
            'name' => 'Missing Skin', 'local_asset_path' => null,
        ]);

        $detail = $this->getJson('/api/v1/arsenal/1865-carbine')->assertOk()->json();
        $this->assertSame('/storage/arsenal/items/carbine.webp', $detail['image_url']);
        $this->assertSame('/storage/arsenal/wiki/skins/1865-carbine/skin.webp', $detail['skins'][0]['image_url']);
        $this->assertNull($detail['skins'][1]['image_url']);
        $this->assertSame('berthier', $detail['variants'][0]['slug']);
        $this->assertSame('Iron Eye', $detail['traits'][0]['name']);
        $this->assertSame('damage', $detail['stats'][0]['key']);
        $this->assertIsArray($detail['ammo'][0]['falloff_points']);
        $this->assertIsArray($detail['patch_history']);
        $this->assertStringNotContainsString('huntshowdown.wiki.gg', json_encode($detail));
    }

    public function test_weapon_stat_ranges_use_only_active_items_in_same_comparison_group(): void
    {
        $this->sync($this->rows());
        EquipmentItem::where('slug', 'pistol')->update(['comparison_group' => 'weapon:rifle']);
        $query = '/api/v1/arsenal/stat-ranges?comparison_group=weapon:rifle&category=Rifle&class=Rifle';
        $response = $this->getJson($query)->assertOk();
        $response->assertJsonPath('comparison_group', 'weapon:rifle')
            ->assertJsonPath('ranges.damage.min', 130)
            ->assertJsonPath('ranges.damage.max', 145)
            ->assertJsonPath('ranges.damage.count', 2);
        EquipmentItem::where('slug', 'berthier')->update(['source_status' => 'missing']);
        $this->getJson($query)->assertOk()
            ->assertJsonPath('ranges.damage.min', 145)
            ->assertJsonPath('ranges.damage.count', 1);
        $this->getJson('/api/v1/arsenal/stat-ranges')->assertUnprocessable();
    }

    public function test_weapon_detail_url_resolves_to_react_shell_on_direct_load(): void
    {
        $route = Route::getRoutes()->match(Request::create('/arsenal/1865-carbine'));
        $this->assertSame('arsenal.react.detail', $route->getName());
        $this->assertSame('1865-carbine', $route->parameter('slug'));
    }

    public function test_multiple_ammo_modes_are_explicit_in_detail_and_comparison(): void
    {
        $this->sync($this->rows());
        $item = EquipmentItem::where('slug', '1865-carbine')->firstOrFail();
        $item->ammo()->create([
            'key' => 'shell-1', 'name' => 'Basic', 'ammo_type' => 'Shell',
            'damage' => 220, 'velocity' => 450,
            'facts' => ['stats' => ['damage' => 220, 'muzzleVelocity' => 450, 'effectiveRange' => 20]],
        ]);

        $detail = $this->getJson('/api/v1/arsenal/1865-carbine')->assertOk()->json();
        $this->assertTrue($detail['has_multiple_ammo_modes']);
        $this->assertContains('damage', $detail['mode_ambiguous_stat_keys']);
        $this->assertContains('muzzleVelocity', $detail['mode_ambiguous_stat_keys']);
        $this->assertSame('damage', $detail['ammo'][1]['mode_stats'][0]['key']);
        $this->assertEquals(220, $detail['ammo'][1]['mode_stats'][0]['value']);
        $this->assertSame('Damage', $detail['ammo'][1]['mode_stats'][0]['label']);
        $this->assertIsArray($detail['ammo'][0]['mode_stats']);

        $compare = $this->getJson('/api/v1/arsenal/compare?items=1865-carbine,berthier')->assertOk()->json();
        $this->assertTrue($compare['has_multiple_ammo_modes']);
        $this->assertContains('damage', $compare['mode_ambiguous_stat_keys']);
    }

    public function test_comparison_groups_use_structured_source_fields(): void
    {
        $this->sync($this->rows());
        $this->assertSame('tool:melee',EquipmentItem::where('slug','dusters')->value('comparison_group'));
        $this->assertSame('tool:healing',EquipmentItem::where('slug','first-aid-kit')->value('comparison_group'));
        $this->assertSame('consumable:shot',EquipmentItem::where('slug','vitality-shot')->value('comparison_group'));
        $this->getJson('/api/v1/arsenal/compare?items=dusters,knife')->assertOk()->assertJsonPath('comparison_group','tool:melee');
        $this->getJson('/api/v1/arsenal/compare?items=dusters,first-aid-kit')->assertUnprocessable();
        $this->getJson('/api/v1/arsenal/compare?items=frag-bomb,dynamite-stick')->assertOk()->assertJsonPath('comparison_group','consumable:explosive');
        $this->getJson('/api/v1/arsenal/compare?items=frag-bomb,vitality-shot')->assertUnprocessable();
    }

    public function test_item_type_filters_list_categories_and_classes(): void
    {
        $this->sync($this->rows());
        $weapons = $this->getJson('/api/v1/arsenal?type=weapon')->assertOk()->json('data');
        $this->assertCount(3,$weapons);
        $this->assertSame(['weapon'],array_values(array_unique(array_column($weapons,'item_type'))));
        $tools = $this->getJson('/api/v1/arsenal?type=tool')->assertOk()->json('data');
        $this->assertCount(3,$tools);
        $this->assertSame(['tool'],array_values(array_unique(array_column($tools,'item_type'))));
        $this->assertSame(['Pistol','Rifle'],$this->getJson('/api/v1/arsenal/categories?type=weapon')->assertOk()->json());
        $this->assertSame(['Healing','Melee','Melee / Throwable'],$this->getJson('/api/v1/arsenal/classes?type=tool')->assertOk()->json());
        $this->getJson('/api/v1/arsenal?type=unknown')->assertUnprocessable();
        $this->getJson('/api/v1/arsenal/categories?type=unknown')->assertUnprocessable();
    }

    public function test_numeric_api_values_and_private_asset_metadata(): void
    {
        $this->sync($this->rows());
        $item = EquipmentItem::where('slug','1865-carbine')->firstOrFail();
        $this->assertSame('https://wiki.huntify.win/Hunt/assets/icons/weapons/carbine.webp',$item->original_asset_url);
        $this->assertNull($item->local_asset_path);
        $item->update(['local_asset_path'=>'equipment/licensed/carbine.webp','license_note'=>'Reviewed license']);
        $this->sync($this->rows());
        $item->refresh();
        $this->assertSame('equipment/licensed/carbine.webp',$item->local_asset_path);
        $this->assertSame('Reviewed license',$item->license_note);
        $detail = $this->getJson('/api/v1/arsenal/1865-carbine')->assertOk()->assertJsonPath('image_url',null)->json();
        $damage = collect($detail['stats'])->firstWhere('key','damage')['value'];
        $velocity = $detail['ammo'][0]['velocity'];
        $distance = $detail['ammo'][0]['falloff_points'][1]['distance'];
        $falloffDamage = $detail['ammo'][0]['falloff_points'][1]['damage'];
        foreach ([$damage,$velocity,$distance,$falloffDamage] as $number) {
            $this->assertTrue(is_int($number) || is_float($number), 'Expected JSON number, not string.');
        }
        $this->assertArrayNotHasKey('equipment_item_id',$detail['stats'][0]);
        $this->assertArrayNotHasKey('equipment_item_id',$detail['ammo'][0]);
        $this->assertArrayNotHasKey('pivot',$detail['traits'][0]);
        $this->assertArrayNotHasKey('original_asset_url',$detail);
        $compare = $this->getJson('/api/v1/arsenal/compare?items=1865-carbine,berthier')->assertOk()->json();
        $this->assertArrayNotHasKey('id',$compare['stat_definitions'][0]);
        $this->assertTrue(is_int($compare['stat_definitions'][0]['sort_order']));
        $this->assertTrue(is_int($compare['stats'][0]['values']['1865-carbine']) || is_float($compare['stats'][0]['values']['1865-carbine']));
    }
}
