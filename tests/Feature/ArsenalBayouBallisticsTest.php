<?php

namespace Tests\Feature;

use App\Models\EquipmentFamily;
use App\Models\EquipmentFieldProvenance;
use App\Models\EquipmentItem;
use App\Models\EquipmentSource;
use App\Models\EquipmentSourceSnapshot;
use App\Models\EquipmentStatDefinition;
use App\Services\Equipment\BayouBallisticsPlanner;
use App\Services\Equipment\BayouIndexBallisticsSource;
use App\Services\Equipment\BayouWeaponMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ArsenalBayouBallisticsTest extends TestCase
{
    use RefreshDatabase;

    protected function migrateFreshUsing(): array
    {
        return ['--path' => [
            'database/migrations/2026_09_30_120000_create_equipment_tables.php',
            'database/migrations/2026_10_01_200000_create_equipment_canonical_foundation.php',
            'database/migrations/2026_10_02_100000_add_equipment_ballistics_stat_definitions.php',
        ]];
    }

    private function weapon(string $slug = '1865-carbine', string $name = '1865 Carbine', string $ammoType = 'Medium'): EquipmentItem
    {
        $source = EquipmentSource::firstOrCreate(['key' => 'test'], ['name' => 'Test']);
        $item = EquipmentItem::create([
            'source_id' => $source->id, 'external_id' => $slug, 'slug' => $slug,
            'name' => $name, 'item_type' => 'weapon', 'category' => 'Rifle',
            'equipment_class' => 'Rifle', 'comparison_group' => 'weapon:rifle',
            'ammo_type' => $ammoType, 'source_status' => 'active',
        ]);
        $ammo = $item->ammo()->create([
            'key' => 'basic-'.$ammoType, 'name' => 'Basic', 'ammo_type' => $ammoType,
            'damage' => 120, 'loaded' => 2, 'reserve' => 20,
        ]);
        foreach ([[30, 120], [80, 76.8], [130, 61.2]] as [$distance, $damage]) {
            $ammo->falloffPoints()->create(['distance' => $distance, 'damage' => $damage]);
        }
        return $item;
    }

    private function stat(EquipmentItem $item, string $key, float $value): void
    {
        $definition = EquipmentStatDefinition::firstOrCreate(['key' => $key], [
            'label' => $key, 'group' => 'ballistics', 'comparison_direction' => 'neutral', 'sort_order' => 900,
        ]);
        $item->stats()->updateOrCreate(['stat_definition_id' => $definition->id], ['value' => $value]);
        $item->unsetRelation('stats');
    }

    private function html(string $name = '1865 Carbine'): string
    {
        return '<html><body><h1>'.$name.'</h1><img alt="medium ammunition" src="/never-fetch.png">'
            .'<div class="spec-cell"><dt>Damage</dt><dd>121</dd></div>'
            .'<div class="spec-cell"><dt>Cycle</dt><dd>0.6 s</dd></div>'
            .'<div class="spec-cell"><dt>Drop</dt><dd>146 m</dd></div>'
            .'<div class="spec-cell"><dt>Ammo</dt><dd>2/20=22</dd></div>'
            .'<div class="more-cell"><dt>Zoom</dt><dd>×1.67</dd></div>'
            .'<section class="profile"><p class="model">Base damage <span>93</span></p>'
            .'<div class="rowhead">Head</div>'
            .'<div class="rowhead">Upper torso <b>×1.3</b> mult</div>'
            .'<div class="rowhead">Torso <b>×1.2</b> mult</div>'
            .'<div class="rowhead">Arm <b>×0.9</b> mult</div>'
            .'<div class="rowhead">Leg <b>×0.8</b> mult</div></section></body></html>';
    }

    public function test_source_reads_only_public_numeric_html_and_does_not_fetch_assets(): void
    {
        Http::fake(['https://bayouindex.com/*' => Http::response($this->html(), 200, ['Content-Type' => 'text/html'])]);
        $page = app(BayouIndexBallisticsSource::class)->preview('1865-carbine');
        $this->assertSame('1865 Carbine', $page['name']);
        $this->assertSame('Medium', $page['ammo_type']);
        $this->assertSame(93.0, $page['fields']['baseDamage']);
        $this->assertSame(1.67, $page['fields']['zoom']);
        $this->assertSame(1.3, $page['fields']['upperTorsoMultiplier']);
        $this->assertNull($page['fields']['headMultiplier']);
        $this->assertSame(146.0, $page['checks']['dropRange']);
        $this->assertArrayNotHasKey('html', $page);
        Http::assertSentCount(1);
    }

    public function test_matching_requires_unique_exact_identity(): void
    {
        $one = $this->weapon();
        $matcher = app(BayouWeaponMatcher::class);
        $items = EquipmentItem::with('family')->get();
        $this->assertSame($one->id, $matcher->match(['source_page_slug' => '1865-carbine', 'name' => '1865 Carbine'], $items)['item']->id);
        $this->assertSame('REVIEW_REQUIRED', $matcher->match(['source_page_slug' => '1865-carbine', 'name' => 'Different Weapon'], $items)['action']);
        $this->assertSame('exact_name', $matcher->match(['source_page_slug' => 'unknown', 'name' => '1865_Carbine'], $items)['method']);

        $this->weapon('other-carbine', '1865 Carbine');
        $items = EquipmentItem::with('family')->get();
        $this->assertSame('REVIEW_REQUIRED', $matcher->match(['source_page_slug' => 'unknown', 'name' => '1865 Carbine'], $items)['action']);

        $family = EquipmentFamily::create(['key' => 'drilling', 'name' => 'Drilling']);
        $variant = $this->weapon('drilling-hatchet', 'Drilling Hatchet');
        $variant->update(['family_id' => $family->id]);
        $this->assertSame('family_variant', $matcher->match([
            'source_page_slug' => 'unknown', 'name' => 'Unmatched', 'family' => 'Drilling', 'variant' => 'Hatchet',
        ], EquipmentItem::with('family')->get())['method']);
    }

    public function test_additive_plan_creates_missing_preserves_same_and_reviews_conflicts_and_manual_override(): void
    {
        $item = $this->weapon();
        $page = app(BayouIndexBallisticsSource::class)->parse($this->html(), '1865-carbine', now()->toIso8601String());
        $planner = app(BayouBallisticsPlanner::class);
        $plan = $planner->plan($item, $page, 100);
        $rows = collect($plan['rows'])->keyBy('field');
        $this->assertSame('CREATE', $rows['ammo.basic-Medium.stat.baseDamage']['action']);
        $this->assertSame('CREATE', $rows['stat.zoom']['action']);
        $this->assertSame('SKIP', $rows['ammo.basic-Medium.stat.headMultiplier']['action']);
        $this->assertSame('SKIP', $rows['stat.damage']['action']);

        $this->stat($item, 'zoom', 1.67);
        $this->assertSame('UNCHANGED', collect($planner->plan($item, $page, 100)['rows'])->keyBy('field')['stat.zoom']['action']);
        $this->stat($item, 'zoom', 2);
        $this->assertSame('REVIEW_REQUIRED', collect($planner->plan($item, $page, 100)['rows'])->keyBy('field')['stat.zoom']['action']);
        EquipmentFieldProvenance::create([
            'equipment_item_id' => $item->id, 'field_key' => 'stat.zoom', 'source_key' => 'manual',
            'is_manual_override' => true,
        ]);
        $this->assertSame('BLOCKED_MANUAL', collect($planner->plan($item, $page, 100)['rows'])->keyBy('field')['stat.zoom']['action']);
        $this->assertSame(0, EquipmentSourceSnapshot::count());
        $this->assertSame(0, $plan['writes']);
    }

    public function test_detail_and_distance_api_keep_drilling_shell_mode_unsupported(): void
    {
        $item = $this->weapon('drilling', 'Drilling');
        $this->stat($item, 'baseDamage', 93);
        $this->stat($item, 'upperTorsoMultiplier', 1.3);
        $shell = $item->ammo()->create(['key' => 'basic-shell', 'name' => 'Basic', 'ammo_type' => 'Shell', 'damage' => 220]);
        $shell->falloffPoints()->create(['distance' => 5, 'damage' => 220]);
        $shell->falloffPoints()->create(['distance' => 80, 'damage' => 66]);

        $detail = $this->getJson('/api/v1/arsenal/drilling')->assertOk()->json('ballistics');
        $this->assertSame('available', $detail['status']);
        $this->assertSame('partial', $detail['ammo_modes'][0]['status']);
        $this->assertSame('unsupported', $detail['ammo_modes'][1]['status']);
        $this->assertNull($detail['ammo_modes'][1]['base_damage']);
        $this->assertNull($detail['ammo_modes'][0]['damage_profiles']['150']['head']);
        $this->assertSame(2, $detail['ammo_modes'][0]['damage_profiles']['150']['upper_torso']['at_zero']['shots_to_kill']);
        $atDistance = $this->getJson('/api/v1/arsenal/drilling/ballistics?ammo_key=basic-Medium&distance=55&hp=150')
            ->assertOk()->json();
        $this->assertEqualsWithDelta(.82, $atDistance['hit_zones']['upper_torso']['falloff_multiplier'], 0.000001);
        $this->assertArrayNotHasKey('shell', $atDistance['hit_zones']);
        $this->getJson('/api/v1/arsenal/drilling/ballistics?ammo_key=basic-shell&distance=0&hp=150')
            ->assertOk()->assertJsonPath('status', 'unsupported');
        $this->getJson('/api/v1/arsenal/drilling/ballistics?ammo_key=basic-Medium&distance=0&hp=151')->assertUnprocessable();
    }

    public function test_missing_ballistics_data_returns_explicit_unsupported_state(): void
    {
        $this->weapon();
        $this->getJson('/api/v1/arsenal/1865-carbine')->assertOk()
            ->assertJsonPath('ballistics.status', 'unsupported')
            ->assertJsonPath('ballistics.ammo_modes.0.base_damage', null);
    }

    public function test_one_item_command_is_read_only_and_has_no_apply_mode(): void
    {
        $item = $this->weapon();
        Http::fake(['https://bayouindex.com/*' => Http::response($this->html(), 200, ['Content-Type' => 'text/html'])]);
        $before = $item->stats()->count();
        $this->assertSame(0, Artisan::call('arsenal:bayou-ballistics', ['--dry-run' => true, '--item' => '1865-carbine']));
        $output = Artisan::output();
        $this->assertStringContainsString('READ-ONLY', $output);
        $this->assertStringContainsString('CREATE', $output);
        $this->assertSame($before, $item->stats()->count());
        $this->assertSame(0, EquipmentSourceSnapshot::count());
        $this->assertNotSame(0, Artisan::call('arsenal:bayou-ballistics', ['--item' => '1865-carbine']));
        Http::assertSentCount(1);
    }
}
