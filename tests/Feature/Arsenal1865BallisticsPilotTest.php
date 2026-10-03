<?php

namespace Tests\Feature;

use App\Models\EquipmentFieldProvenance;
use App\Models\EquipmentItem;
use App\Models\EquipmentSource;
use App\Models\EquipmentSourceSnapshot;
use App\Models\EquipmentStatDefinition;
use App\Services\Equipment\CarbineBallisticsPilot;
use App\Services\Equipment\WeaponBulletDropCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class Arsenal1865BallisticsPilotTest extends TestCase
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

    private function seedCarbine(): EquipmentItem
    {
        $source = EquipmentSource::firstOrCreate(['key' => 'test'], ['name' => 'Test']);
        $item = EquipmentItem::create([
            'source_id' => $source->id, 'external_id' => '1865-carbine', 'slug' => '1865-carbine',
            'name' => '1865 Carbine', 'item_type' => 'weapon', 'source_status' => 'active',
            'ammo_type' => 'Medium', 'category' => 'Rifle', 'equipment_class' => 'Rifle',
            'comparison_group' => 'weapon:rifle',
        ]);
        foreach ([
            'baseDamage' => 112, 'upperTorsoMultiplier' => 1.3, 'torsoMultiplier' => 1.2,
            'armMultiplier' => 0.9, 'legMultiplier' => 0.8, 'dropRange' => 115,
        ] as $key => $value) {
            $definition = EquipmentStatDefinition::firstOrCreate(['key' => $key], [
                'label' => $key, 'comparison_direction' => 'neutral', 'group' => 'ballistics', 'sort_order' => 500,
            ]);
            $item->stats()->create(['stat_definition_id' => $definition->id, 'value' => $value]);
        }
        foreach ([
            ['basic-medium-0', 'Basic', 340, 21, [[30, 145], [80, 92.8], [130, 73.95]]],
            ['fullmetaljacket-medium-1', 'Fullmetaljacket', 272, 21, [[40, 145], [90, 92.8], [130, 76.85]]],
            ['subsonic-medium-2', 'Subsonic', 242, 25, [[30, 145], [80, 92.8], [130, 73.95]]],
        ] as [$key, $name, $velocity, $reserve, $points]) {
            $ammo = $item->ammo()->create([
                'key' => $key, 'name' => $name, 'ammo_type' => 'Medium',
                'damage' => 145, 'velocity' => $velocity, 'loaded' => 7, 'reserve' => $reserve,
                'facts' => $name === 'Subsonic' ? ['stats' => ['reserve' => 21]] : [],
            ]);
            foreach ($points as [$distance, $damage]) {
                $ammo->falloffPoints()->create(['distance' => $distance, 'damage' => $damage]);
            }
        }
        return $item;
    }

    public function test_public_model_matches_stock_thresholds_and_keeps_card_drop_distinct_from_zero_range(): void
    {
        $item = $this->seedCarbine();
        $plan = app(CarbineBallisticsPilot::class)->plan($item);
        $profile = collect($plan['rows'])->firstWhere('field', 'ammo.basic-medium-0.bullet_drop')['source_value'];
        $calculator = app(WeaponBulletDropCalculator::class);
        $this->assertTrue($calculator->valid($profile));
        $summary = $calculator->summary($profile);
        $this->assertSame(45.0, $summary['flat_until_m']);
        $this->assertSame(117, $summary['head_size_range_m']);
        $this->assertSame([0, 121, 169, 189], array_column($summary['zones'], 'start_m'));
        $this->assertSame([0.0, 0.25, 0.66, 0.89], array_column($summary['zones'], 'reference_drop_m'));
        $this->assertEqualsWithDelta(0.441, $calculator->atDistance($profile, 146)['drop_m'], 0.001);
        $this->assertSame('upper_torso', $calculator->atDistance($profile, 146)['reference_zone']);
        $this->assertNull($calculator->atDistance($profile, 222));
    }

    public function test_read_only_plan_is_ammo_isolated_and_marks_existing_conflicts(): void
    {
        $item = $this->seedCarbine();
        $rows = collect(app(CarbineBallisticsPilot::class)->plan($item)['rows'])->keyBy('field');
        $this->assertSame('UNCHANGED', $rows['ammo.basic-medium-0.stat.baseDamage']['action']);
        $this->assertSame('CREATE', $rows['ammo.fullmetaljacket-medium-1.stat.baseDamage']['action']);
        $this->assertSame('CREATE', $rows['ammo.subsonic-medium-2.stat.baseDamage']['action']);
        $this->assertSame('CREATE', $rows['ammo.basic-medium-0.bullet_drop']['action']);
        $this->assertSame('REVIEW_REQUIRED', $rows['stat.dropRange']['action']);
        $this->assertSame('REVIEW_REQUIRED', $rows['ammo.subsonic-medium-2.stat.reserve']['action']);
        foreach (['basic-medium-0', 'fullmetaljacket-medium-1', 'subsonic-medium-2'] as $key) {
            $this->assertSame('UNCHANGED', $rows["ammo.$key.falloff_ratios"]['action']);
        }
        $this->assertSame(0, EquipmentSourceSnapshot::count());
        $this->assertSame(0, EquipmentFieldProvenance::count());
        $this->assertSame(0, Artisan::call('arsenal:1865-ballistics', ['--dry-run' => true]));
        $this->assertStringContainsString('READ-ONLY', Artisan::output());
        $this->assertSame(0, EquipmentSourceSnapshot::count());
    }

    public function test_safe_apply_creates_missing_values_once_and_exposes_three_independent_modes(): void
    {
        $item = $this->seedCarbine();
        $service = app(CarbineBallisticsPilot::class);
        $first = $service->apply($item);
        $this->assertSame(13, $first['written']);
        $this->assertSame(0, $service->apply($item)['written']);
        $this->assertSame(1, EquipmentSourceSnapshot::count());
        $this->assertSame(13, EquipmentFieldProvenance::count());
        $rows = collect($service->plan($item->fresh())['rows'])->keyBy('field');
        $this->assertSame('UNCHANGED', $rows['ammo.fullmetaljacket-medium-1.stat.baseDamage']['action']);
        $this->assertSame('UNCHANGED', $rows['ammo.subsonic-medium-2.bullet_drop']['action']);
        $this->assertSame('REVIEW_REQUIRED', $rows['stat.dropRange']['action']);
        $this->assertSame(115.0, (float) $item->stats()->whereHas('definition', fn ($query) =>
            $query->where('key', 'dropRange'))->first()->value);
        $this->assertSame(21, $item->ammo()->where('key', 'basic-medium-0')->first()->reserve);
        $this->assertSame(25, $item->ammo()->where('key', 'subsonic-medium-2')->first()->reserve);
        foreach (['basic-medium-0', 'fullmetaljacket-medium-1', 'subsonic-medium-2'] as $key) {
            $this->assertArrayNotHasKey('headMultiplier', $item->ammo()->where('key', $key)->first()->facts['stats'] ?? []);
        }
        $detail = $this->getJson('/api/v1/arsenal/1865-carbine')->assertOk()->json('ballistics.ammo_modes');
        $this->assertSame(['partial', 'partial', 'partial'], array_column($detail, 'status'));
        $this->assertSame([117, 103, 96], array_map(fn ($mode) =>
            $mode['bullet_drop']['head_size_range_m'], $detail));
        $this->assertNull($detail[0]['hit_zones']['head']);
        $this->assertSame(2, $detail[1]['damage_profiles']['150']['upper_torso']['at_zero']['shots_to_kill']);
        $distance = $this->getJson('/api/v1/arsenal/1865-carbine/ballistics?ammo_key=basic-medium-0&distance=146&hp=150')
            ->assertOk()->json();
        $this->assertSame('upper_torso', $distance['bullet_drop']['reference_zone']);
        $this->assertSame(3, $distance['hit_zones']['upper_torso']['shots_to_kill']);
    }

    public function test_conflicts_manual_override_and_other_weapon_are_protected(): void
    {
        $item = $this->seedCarbine();
        $fmj = $item->ammo()->where('key', 'fullmetaljacket-medium-1')->firstOrFail();
        $fmj->facts = ['stats' => ['baseDamage' => 99]];
        $fmj->save();
        EquipmentFieldProvenance::create([
            'equipment_item_id' => $item->id, 'field_key' => 'ammo.subsonic-medium-2.bullet_drop',
            'source_key' => 'manual', 'is_manual_override' => true,
        ]);
        $result = app(CarbineBallisticsPilot::class)->apply($item);
        $rows = collect($result['rows'])->keyBy('field');
        $this->assertSame('REVIEW_REQUIRED', $rows['ammo.fullmetaljacket-medium-1.stat.baseDamage']['result']);
        $this->assertSame('REVIEW_REQUIRED', $rows['ammo.subsonic-medium-2.bullet_drop']['result']);
        $this->assertSame(99, $fmj->fresh()->facts['stats']['baseDamage']);
        $other = EquipmentItem::create([
            'source_id' => $item->source_id, 'external_id' => 'other', 'slug' => 'other',
            'name' => 'Other', 'item_type' => 'weapon', 'source_status' => 'active',
            'comparison_group' => 'weapon:rifle',
        ]);
        $this->expectException(\InvalidArgumentException::class);
        app(CarbineBallisticsPilot::class)->plan($other);
    }

    public function test_velocity_conflict_blocks_bullet_drop_and_command_apply_is_idempotent(): void
    {
        $item = $this->seedCarbine();
        $fmj = $item->ammo()->where('key', 'fullmetaljacket-medium-1')->firstOrFail();
        $fmj->velocity = 270;
        $fmj->save();
        $rows = collect(app(CarbineBallisticsPilot::class)->plan($item->fresh())['rows'])->keyBy('field');
        $this->assertSame('REVIEW_REQUIRED', $rows['ammo.fullmetaljacket-medium-1.bullet_drop']['action']);
        $this->assertSame(0, Artisan::call('arsenal:1865-ballistics', ['--apply' => true]));
        $this->assertArrayNotHasKey('bullet_drop', $fmj->fresh()->facts ?? []);
        $this->assertSame(0, Artisan::call('arsenal:1865-ballistics', ['--apply' => true]));
        $this->assertSame(0, app(CarbineBallisticsPilot::class)->apply($item)['written']);
    }
}
