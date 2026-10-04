<?php

namespace Tests\Feature\Maps;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\User;
use App\Services\Maps\Imports\MapMarkerImportPreview;
use App\Services\Maps\Imports\MapMarkerImportProviderInterface;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MapMarkerImportPreviewTest extends TestCase
{
    private HntMap $map;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('hnt_maps', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->unsignedInteger('width');
            $table->unsignedInteger('height');
            $table->string('image_path')->nullable();
            $table->string('lines_path')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('hnt_map_markers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hnt_map_id');
            $table->string('legacy_key');
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('type');
            $table->decimal('x', 12, 6);
            $table->decimal('y', 12, 6);
            $table->string('label_de')->nullable();
            $table->string('label_en')->nullable();
            $table->string('source_image')->nullable();
            $table->string('status')->default('approved');
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->unique(['hnt_map_id', 'legacy_key']);
        });

        $migration = require base_path('database/migrations/2026_10_03_000001_add_external_identity_to_hnt_map_markers.php');
        $migration->up();
        Schema::create('hnt_map_cash_spot_submissions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('hnt_map_marker_id')->nullable();
            $table->timestamps();
        });

        $this->map = HntMap::query()->create([
            'slug' => 'stillwater-bayou', 'name' => 'Stillwater Bayou', 'width' => 2048, 'height' => 2048, 'is_active' => true,
        ]);
    }

    public function test_preview_is_read_only_and_preserves_cash_and_unselected_categories(): void
    {
        $cash = $this->marker('submission:9', 'cash', 111, 222);
        $legacyTower = $this->marker('legacy:tower', 'tower', 100, 200);
        $legacyBugs = $this->marker('legacy:bugs', 'bugs', 300, 400);
        $legacyWild = $this->marker('legacy:wild', 'wild', 500, 600);
        $otherImported = $this->marker('external:kamille:BeetleSource', 'beetle', 50, 60, 'kamille', 'BeetleSource', 'beetles');
        $before = HntMapMarker::query()->orderBy('id')->get()->toArray();

        $result = app(MapMarkerImportPreview::class)->create($this->provider(), ['stillwater-bayou'], ['tower:hunting'], 'sync');

        $this->assertSame($before, HntMapMarker::query()->orderBy('id')->get()->toArray());
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['database_fingerprint']);
        $this->assertSame(1, $result['total']['new']);
        $this->assertSame(0, $result['total']['removed_external']);
        $this->assertSame(['tower' => 1, 'bugs' => 1, 'wild' => 1], $result['maps']['stillwater-bayou']['legacy']);
        $this->assertSame(1, $result['maps']['stillwater-bayou']['protected_existing']['cash']);
        $this->assertSame(1, $result['maps']['stillwater-bayou']['protected_existing']['submission_key']);
        $this->assertSame(111.0, $cash->fresh()->x);
        $this->assertSame('submission:9', $cash->fresh()->legacy_key);
        $this->assertSame(50.0, $otherImported->fresh()->x);
        $this->assertSame(5, HntMapMarker::query()->count());
    }

    public function test_unknown_wild_subtype_is_reported_without_being_classified_or_written(): void
    {
        $before = HntMapMarker::query()->count();

        $result = app(MapMarkerImportPreview::class)->create($this->provider(), ['stillwater-bayou'], ['wild_target'], 'add_only');

        $this->assertSame(1, $result['total']['unclassified']);
        $this->assertSame(0, $result['total']['new']);
        $this->assertSame($before, HntMapMarker::query()->count());
    }

    public function test_preview_still_runs_before_the_additive_migration_is_applied(): void
    {
        $migration = require base_path('database/migrations/2026_10_03_000001_add_external_identity_to_hnt_map_markers.php');
        $migration->down();

        $result = app(MapMarkerImportPreview::class)->create($this->provider(), ['stillwater-bayou'], ['tower:hunting'], 'sync');

        $this->assertFalse($result['identity_columns_ready']);
        $this->assertSame(1, $result['total']['new']);
        $this->assertSame(0, HntMapMarker::query()->count());
    }

    public function test_external_identity_unique_constraint_prevents_duplicate_sync_rows(): void
    {
        $this->marker('external:first', 'tower', 10, 20, 'kamille', 'HuntingTower', 'towers');

        $this->expectException(QueryException::class);
        $this->marker('external:duplicate', 'tower', 10, 20, 'kamille', 'HuntingTower', 'towers');
    }

    public function test_selected_imported_marker_is_recognized_as_existing_on_repeat_preview(): void
    {
        $payload = [
            'type' => 'tower', 'subtype' => 'hunting', 'x' => 778.0, 'y' => 2001.5,
            'label_de' => null, 'label_en' => null,
        ];
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $marker = $this->marker('external:kamille:HuntingTower', 'tower', 778, 2001.5, 'kamille', 'HuntingTower', 'towers');
        $marker->forceFill(['subtype' => 'hunting', 'source_payload_hash' => $hash])->save();

        $result = app(MapMarkerImportPreview::class)->create($this->provider(), ['stillwater-bayou'], ['tower:hunting'], 'sync');

        $this->assertSame(0, $result['total']['new']);
        $this->assertSame(1, $result['total']['unchanged']);
        $this->assertSame(1, HntMapMarker::query()->count());

        $marker->forceFill(['x' => 779])->save();
        $drift = app(MapMarkerImportPreview::class)->create($this->provider(), ['stillwater-bayou'], ['tower:hunting'], 'sync');
        $this->assertSame(1, $drift['total']['changed']);
        $this->assertSame(779.0, $marker->fresh()->x);
    }

    public function test_map_api_keeps_legacy_type_and_exposes_new_subtype(): void
    {
        $this->marker('legacy:cash', 'cash', 100, 200);
        $tower = $this->marker('external:tower', 'tower', 778, 2001.5, 'kamille', 'HuntingTower', 'towers');
        $tower->forceFill(['subtype' => 'hunting'])->save();
        $beast = $this->marker('external:beast', 'beast', 500, 600, 'kamille', 'BruteSource1', 'brutes');

        $response = $this->getJson('/api/v1/maps/stillwater-bayou')->assertOk();

        $this->assertContains('beast', $response->json('data.marker_types'));
        $response->assertJsonPath('data.markers.0.type', 'cash');
        $response->assertJsonPath('data.markers.1.type', 'tower');
        $response->assertJsonPath('data.markers.1.subtype', 'hunting');
        $response->assertJsonPath('data.markers.2.type', 'beast');
        $response->assertJsonPath('data.markers.2.subtype', null);
    }

    public function test_admin_preview_route_shows_source_failure_without_database_changes(): void
    {
        $admin = new User(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test', 'is_admin' => true]);
        $admin->id = 1;
        $this->actingAs($admin);
        Http::fake(['hunt.kamille.ovh/maps/cache/*' => Http::response('', 503)]);

        $this->get('/admin/maps/marker-import')->assertOk()->assertSee('Marker importieren');
        $this->post('/admin/maps/marker-import/preview', [
            'provider' => 'kamille', 'maps' => ['stillwater-bayou'], 'categories' => ['tower:hunting'], 'mode' => 'sync',
        ])->assertOk()->assertSee('Die externe Markerquelle konnte nicht geladen werden.');

        $this->assertSame(0, HntMapMarker::query()->count());
    }

    public function test_admin_preview_renders_current_provider_categories_and_transformed_examples(): void
    {
        $admin = new User(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test', 'is_admin' => true]);
        $admin->id = 1;
        $this->actingAs($admin);
        $manifest = [];

        foreach ([
            'easter_egg' => 'easter_eggs', 'wild_target' => 'wild_targets', 'brute' => 'brutes',
            'beetle' => 'beetles', 'tower' => 'towers', 'big_tower' => 'big_towers',
            'spawn' => 'spawns', 'extraction' => 'extractions',
            'bounty_clash_extraction' => 'bounty_clash_extractions',
            'supply_point' => 'supply_points', 'postal_supply' => 'postal_supplies',
            'clockmaker_supply' => 'clockmaker_supplies', 'firefighter_supply' => 'firefighter_supplies',
            'medical_supply' => 'medical_supplies', 'military_supply' => 'military_supplies',
            'scout_tower' => 'scout_towers', 'workbench' => 'workbenches',
        ] as $type => $category) {
            $manifest[$type] = ['categories' => $category];
        }

        Http::fake([
            'hunt.kamille.ovh/maps/cache/poi-types.json' => Http::response($manifest),
            'hunt.kamille.ovh/maps/cache/data-1.json' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/maps/kamille-stillwater-sample.json')), true)),
        ]);

        $this->post('/admin/maps/marker-import/preview', [
            'provider' => 'kamille', 'maps' => ['stillwater-bayou'],
            'categories' => ['easter_egg', 'wild_target', 'beast', 'beetle', 'tower', 'workbench'],
            'mode' => 'sync',
        ])->assertOk()->assertSee('Import-Vorschau')->assertSee('HuntingTower')->assertSee('Neu: 9');

        $this->assertSame(0, HntMapMarker::query()->count());
    }

    public function test_artisan_preview_command_is_read_only_and_emits_json(): void
    {
        $manifest = [];
        foreach ([
            'easter_egg' => 'easter_eggs', 'wild_target' => 'wild_targets', 'brute' => 'brutes',
            'beetle' => 'beetles', 'tower' => 'towers', 'big_tower' => 'big_towers',
            'spawn' => 'spawns', 'extraction' => 'extractions',
            'bounty_clash_extraction' => 'bounty_clash_extractions',
            'supply_point' => 'supply_points', 'postal_supply' => 'postal_supplies',
            'clockmaker_supply' => 'clockmaker_supplies', 'firefighter_supply' => 'firefighter_supplies',
            'medical_supply' => 'medical_supplies', 'military_supply' => 'military_supplies',
            'scout_tower' => 'scout_towers', 'workbench' => 'workbenches',
        ] as $type => $category) {
            $manifest[$type] = ['categories' => $category];
        }
        Http::fake([
            'hunt.kamille.ovh/maps/cache/poi-types.json' => Http::response($manifest),
            'hunt.kamille.ovh/maps/cache/data-1.json' => Http::response(json_decode(file_get_contents(base_path('tests/Fixtures/maps/kamille-stillwater-sample.json')), true)),
        ]);
        $before = HntMapMarker::query()->count();
        $this->artisan('hnt:maps:marker-import-preview', [
            '--provider' => 'kamille', '--map' => ['stillwater-bayou'],
            '--category' => ['tower:hunting'], '--mode' => 'sync', '--json' => true,
        ])->assertExitCode(0);
        $this->assertSame($before, HntMapMarker::query()->count());
    }

    private function marker(string $legacyKey, string $type, float $x, float $y, ?string $provider = null, ?string $sourceKey = null, ?string $sourceCategory = null): HntMapMarker
    {
        return $this->map->markers()->create([
            'legacy_key' => $legacyKey,
            'type' => $type,
            'x' => $x,
            'y' => $y,
            'status' => 'approved',
            'source_provider' => $provider,
            'source_key' => $sourceKey,
            'source_category' => $sourceCategory,
        ]);
    }

    private function provider(): MapMarkerImportProviderInterface
    {
        return new class implements MapMarkerImportProviderInterface
        {
            public function id(): string { return 'kamille'; }
            public function name(): string { return 'Kamille'; }
            public function maps(): array { return ['stillwater-bayou' => ['id' => 1, 'name' => 'Stillwater Bayou']]; }
            public function categories(): array
            {
                return [
                    'tower:hunting' => ['source_category' => 'towers', 'type' => 'tower', 'subtype' => 'hunting'],
                    'beetle' => ['source_category' => 'beetles', 'type' => 'beetle', 'subtype' => null],
                    'wild_target' => ['source_category' => 'wild_targets', 'type' => 'wild_target', 'subtype' => null],
                ];
            }
            public function markers(string $mapSlug): array
            {
                return [
                    ['source_key' => 'HuntingTower', 'source_category' => 'towers', 'type' => 'tower', 'subtype' => 'hunting', 'x' => 1556.0, 'y' => 4003.0, 'label_de' => null, 'label_en' => null],
                    ['source_key' => 'BeetleSource', 'source_category' => 'beetles', 'type' => 'beetle', 'subtype' => null, 'x' => 2683.0, 'y' => 987.0, 'label_de' => null, 'label_en' => null],
                    ['source_key' => 'UnknownWild1', 'source_category' => 'wild_targets', 'type' => 'wild_target', 'subtype' => null, 'x' => 1250.0, 'y' => 2140.0, 'label_de' => null, 'label_en' => null],
                ];
            }
            public function outOfBounds(string $mapSlug): array { return []; }
            public function outOfBoundsKeys(string $mapSlug): array { return []; }
        };
    }
}
