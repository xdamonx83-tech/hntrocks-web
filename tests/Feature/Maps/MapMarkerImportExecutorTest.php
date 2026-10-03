<?php

namespace Tests\Feature\Maps;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\User;
use App\Services\Maps\Imports\MapMarkerImportExecutor;
use App\Services\Maps\Imports\MapMarkerImportProviderInterface;
use App\Services\Maps\Imports\MapMarkerImportSourcePlan;
use App\Services\Maps\Imports\StaleMapMarkerImportPreviewException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MapMarkerImportExecutorTest extends TestCase
{
    private HntMap $stillwater;
    private HntMap $lawson;
    private TestMapMarkerProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('hnt_maps', function (Blueprint $table) {
            $table->id(); $table->string('slug')->unique(); $table->string('name');
            $table->unsignedInteger('width'); $table->unsignedInteger('height');
            $table->unsignedInteger('sort_order')->default(0); $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('hnt_map_markers', function (Blueprint $table) {
            $table->id(); $table->foreignId('hnt_map_id'); $table->string('legacy_key');
            $table->unsignedBigInteger('source_id')->nullable(); $table->string('type');
            $table->decimal('x', 12, 6); $table->decimal('y', 12, 6);
            $table->string('label_de')->nullable(); $table->string('label_en')->nullable();
            $table->string('source_image')->nullable(); $table->string('status')->default('approved');
            $table->unsignedInteger('sort_order')->default(0); $table->json('meta')->nullable();
            $table->timestamps(); $table->unique(['hnt_map_id', 'legacy_key']);
        });
        (require base_path('database/migrations/2026_10_03_000001_add_external_identity_to_hnt_map_markers.php'))->up();
        Schema::create('hnt_map_cash_spot_submissions', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('hnt_map_marker_id')->nullable(); $table->timestamps();
        });
        $this->stillwater = HntMap::query()->create(['slug' => 'stillwater-bayou', 'name' => 'Stillwater Bayou', 'width' => 2048, 'height' => 2048]);
        $this->lawson = HntMap::query()->create(['slug' => 'lawson-delta', 'name' => 'Lawson Delta', 'width' => 2048, 'height' => 2048]);
        $this->provider = new TestMapMarkerProvider();
    }

    public function test_new_sync_repeat_and_changed_sync_are_idempotent(): void
    {
        $first = $this->execute();
        $this->assertSame(2, $first['total']['created']);
        $this->assertSame(2, HntMapMarker::query()->count());
        $second = $this->execute();
        $this->assertSame(2, $second['total']['unchanged']);
        $this->assertSame(2, HntMapMarker::query()->count());
        $this->provider->rows['stillwater-bayou'][0]['x'] = 1200.0;
        $third = $this->execute();
        $this->assertSame(1, $third['total']['updated']);
        $this->assertSame(600.0, HntMapMarker::query()->where('source_key', 'tower-1')->first()->x);
    }

    public function test_add_only_never_changes_existing_marker(): void
    {
        $this->execute();
        $this->provider->rows['stillwater-bayou'][0]['x'] = 1400.0;
        $result = $this->execute(['tower:hunting', 'beetle'], 'add_only');
        $this->assertSame(1, $result['total']['skipped_add_only']);
        $this->assertSame(500.0, HntMapMarker::query()->where('source_key', 'tower-1')->first()->x);
    }

    public function test_cash_and_submission_markers_are_hard_protected(): void
    {
        $cash = $this->marker($this->stillwater, 'cash', 'external:cash', 'tower-1', 'kamille');
        $result = $this->execute(['tower:hunting']);
        $this->assertSame(1, $result['total']['protected']);
        $this->assertSame('cash', $cash->fresh()->type);
        $cash->forceFill(['type' => 'tower', 'legacy_key' => 'submission:123'])->save();
        $this->assertSame(1, $this->execute(['tower:hunting'])['total']['protected']);
        $cash->forceFill(['legacy_key' => 'ordinary'])->save();
        \Illuminate\Support\Facades\DB::table('hnt_map_cash_spot_submissions')->insert(['hnt_map_marker_id' => $cash->id]);
        $this->assertSame(1, $this->execute(['tower:hunting'])['total']['protected']);
        $this->assertSame(100.0, $cash->fresh()->x);
    }

    public function test_unselected_category_and_map_remain_untouched(): void
    {
        $other = $this->marker($this->lawson, 'tower', 'legacy:lawson');
        $result = $this->execute(['tower:hunting']);
        $this->assertSame(1, $result['total']['created']);
        $this->assertNull(HntMapMarker::query()->where('source_key', 'beetle-1')->first());
        $this->assertSame(100.0, $other->fresh()->x);
        $this->assertSame(0, HntMapMarker::query()->where('hnt_map_id', $this->lawson->id)->whereNotNull('source_provider')->count());
    }

    public function test_unknown_wild_and_out_of_bounds_are_counted_but_not_written(): void
    {
        $this->provider->rows['stillwater-bayou'][] = $this->provider->row('wild-unknown', 'wild_target', null, 100, 100);
        $this->provider->outOfBounds = ['easter_eggs' => 1];
        $this->provider->outOfBoundsKeys = ['egg-outside'];
        $result = $this->execute(['wild_target', 'easter_egg']);
        $this->assertSame(1, $result['total']['unclassified']);
        $this->assertSame(1, $result['total']['out_of_bounds']);
        $this->assertSame(0, HntMapMarker::query()->count());
    }

    public function test_external_missing_is_reported_without_deletion(): void
    {
        $this->execute(['tower:hunting']);
        $this->provider->rows['stillwater-bayou'] = [];
        $result = $this->execute(['tower:hunting']);
        $this->assertSame(1, $result['total']['external_missing']);
        $this->assertSame(1, HntMapMarker::query()->count());
    }

    public function test_stale_source_fingerprint_prevents_any_write(): void
    {
        $fingerprint = $this->fingerprint(['tower:hunting']);
        $this->provider->rows['stillwater-bayou'][0]['x'] = 1800.0;
        $this->expectException(StaleMapMarkerImportPreviewException::class);
        try {
            app(MapMarkerImportExecutor::class)->execute($this->provider, ['stillwater-bayou'], ['tower:hunting'], 'sync', $fingerprint);
        } finally {
            $this->assertSame(0, HntMapMarker::query()->count());
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('legacyTypes')]
    public function test_legacy_replace_deletes_only_selected_unowned_type(string $legacyType, string $selection): void
    {
        foreach (['tower', 'bugs', 'wild'] as $type) {
            $this->marker($this->stillwater, $type, 'legacy:'.$type);
        }
        $otherProvider = $this->marker($this->stillwater, $legacyType, 'other:'.$legacyType, 'other-'.$legacyType, 'other');
        $cash = $this->marker($this->stillwater, 'cash', 'submission:44');
        $this->provider->rows['stillwater-bayou'][] = $this->provider->row('wild-1', 'wild_target', 'rotjaw', 900, 900);
        $selections = match ($legacyType) {
            'tower' => ['tower'], 'wild' => ['wild_target'], default => [$selection],
        };
        $result = $this->execute($selections, 'sync', [$legacyType], [$legacyType => 1]);
        $this->assertSame(1, $result['maps']['stillwater-bayou']['legacy_deleted'][$legacyType]);
        $this->assertNull(HntMapMarker::query()->where('legacy_key', 'legacy:'.$legacyType)->first());
        $this->assertNotNull($otherProvider->fresh());
        $this->assertNotNull($cash->fresh());
        foreach (array_diff(['tower', 'bugs', 'wild'], [$legacyType]) as $type) {
            $this->assertNotNull(HntMapMarker::query()->where('legacy_key', 'legacy:'.$type)->first());
        }
    }

    public static function legacyTypes(): array
    {
        return [['tower', 'tower:hunting'], ['bugs', 'beetle'], ['wild', 'wild_target:rotjaw']];
    }

    public function test_legacy_count_drift_rolls_back_import(): void
    {
        $this->marker($this->stillwater, 'tower', 'legacy:tower');
        $this->expectException(StaleMapMarkerImportPreviewException::class);
        try {
            $this->execute(['tower'], 'sync', ['tower'], ['tower' => 0]);
        } finally {
            $this->assertSame(1, HntMapMarker::query()->count());
            $this->assertNotNull(HntMapMarker::query()->where('legacy_key', 'legacy:tower')->first());
        }
    }

    public function test_write_failure_rolls_back_all_created_markers_and_legacy_delete(): void
    {
        $this->marker($this->stillwater, 'tower', 'legacy:tower');
        // The second insert collides with an unrelated local legacy key after the first insert.
        $this->marker($this->stillwater, 'beetle', 'external:kamille:beetle-1');
        $this->expectException(\Illuminate\Database\QueryException::class);
        try {
            $this->execute(['tower', 'beetle'], 'sync', ['tower'], ['tower' => 1]);
        } finally {
            $this->assertSame(2, HntMapMarker::query()->count());
            $this->assertNotNull(HntMapMarker::query()->where('legacy_key', 'legacy:tower')->first());
        }
    }

    public function test_execute_route_requires_admin_post_csrf_and_exact_confirmation(): void
    {
        $this->get('/admin/maps/marker-import/execute')->assertStatus(405);
        $this->post('/admin/maps/marker-import/execute', ['reviewed' => '1', 'confirmation' => 'IMPORT'])->assertRedirect();
        $admin = new User(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test', 'is_admin' => true]);
        $admin->id = 1;
        $this->actingAs($admin);
        $this->post('/admin/maps/marker-import/execute', ['reviewed' => '1', 'confirmation' => 'wrong'])->assertSessionHasErrors('confirmation');
        $this->post('/admin/maps/marker-import/execute', ['reviewed' => '1', 'confirmation' => 'IMPORT'])->assertOk()->assertSee('Import-Vorschau ist abgelaufen');
        $this->assertSame(0, HntMapMarker::query()->count());
    }

    public function test_admin_preview_then_confirmation_executes_exact_selected_plan(): void
    {
        $this->fakeKamille(false);
        $this->actingAs($this->admin());
        $this->post('/admin/maps/marker-import/preview', [
            'provider' => 'kamille', 'maps' => ['stillwater-bayou'],
            'categories' => ['tower:hunting'], 'mode' => 'sync',
        ])->assertOk()->assertSee('Import vorbereiten');
        $this->assertSame(0, HntMapMarker::query()->count());
        $this->post('/admin/maps/marker-import/execute', [
            'reviewed' => '1', 'confirmation' => 'IMPORT',
        ])->assertOk()->assertSee('Markerimport abgeschlossen');
        $this->assertSame(1, HntMapMarker::query()->count());
        $this->assertSame('HuntingTower', HntMapMarker::query()->first()->source_key);
        $this->assertSame(0, HntMapMarker::query()->where('type', 'cash')->count());
    }

    public function test_admin_execute_rejects_source_change_after_preview(): void
    {
        $this->fakeKamille(true);
        $this->actingAs($this->admin());
        $this->post('/admin/maps/marker-import/preview', [
            'provider' => 'kamille', 'maps' => ['stillwater-bayou'],
            'categories' => ['tower:hunting'], 'mode' => 'sync',
        ])->assertOk();
        $this->post('/admin/maps/marker-import/execute', [
            'reviewed' => '1', 'confirmation' => 'IMPORT',
        ])->assertOk()->assertSee('Die Quelldaten haben sich seit der Vorschau geändert.');
        $this->assertSame(0, HntMapMarker::query()->count());
    }

    public function test_non_admin_cannot_execute_even_with_a_valid_confirmation(): void
    {
        $user = new User(['name' => 'User', 'username' => 'user', 'email' => 'user@example.test', 'is_admin' => false]);
        $user->id = 2;
        $this->actingAs($user)->post('/admin/maps/marker-import/execute', [
            'reviewed' => '1', 'confirmation' => 'IMPORT',
        ])->assertForbidden();
        $this->assertSame(0, HntMapMarker::query()->count());
    }

    public function test_legacy_replace_needs_its_own_confirmation_and_complete_category_selection(): void
    {
        $this->marker($this->stillwater, 'tower', 'legacy:tower');
        $this->fakeKamille(false);
        $this->actingAs($this->admin());
        $this->post('/admin/maps/marker-import/preview', [
            'provider' => 'kamille', 'maps' => ['stillwater-bayou'],
            'categories' => ['tower:hunting'], 'mode' => 'sync',
        ])->assertOk();
        $this->post('/admin/maps/marker-import/execute', [
            'reviewed' => '1', 'confirmation' => 'IMPORT', 'replace_legacy' => ['tower'],
        ])->assertSessionHasErrors(['replace_reviewed', 'replace_confirmation']);
        $this->assertNotNull(HntMapMarker::query()->where('legacy_key', 'legacy:tower')->first());
        $this->post('/admin/maps/marker-import/execute', [
            'reviewed' => '1', 'confirmation' => 'IMPORT',
            'replace_legacy' => ['tower'], 'replace_reviewed' => '1', 'replace_confirmation' => 'ERSETZEN',
        ])->assertOk()->assertSee('Der Markerimport wurde abgebrochen');
        $this->assertSame(1, HntMapMarker::query()->count());
    }

    private function admin(): User
    {
        $admin = new User(['name' => 'Admin', 'username' => 'admin', 'email' => 'admin@example.test', 'is_admin' => true]);
        $admin->id = 1;
        return $admin;
    }

    private function fakeKamille(bool $changeOnSecondFetch): void
    {
        $manifest = [];
        foreach (['easter_egg' => 'easter_eggs', 'wild_target' => 'wild_targets', 'brute' => 'brutes', 'beetle' => 'beetles',
            'tower' => 'towers', 'big_tower' => 'big_towers', 'scout_tower' => 'scout_towers', 'workbench' => 'workbenches'] as $type => $category) {
            $manifest[$type] = ['categories' => $category];
        }
        $fetches = 0;
        Http::fake(function (HttpRequest $request) use ($manifest, &$fetches, $changeOnSecondFetch) {
            if (str_ends_with($request->url(), 'poi-types.json')) {
                return Http::response($manifest);
            }
            $data = json_decode(file_get_contents(base_path('tests/Fixtures/maps/kamille-stillwater-sample.json')), true);
            if ($changeOnSecondFetch && ++$fetches > 1) {
                $data['towers'][0]['c'][1] += 12;
            }
            return Http::response($data);
        });
    }

    private function execute(array $selection = ['tower:hunting', 'beetle'], string $mode = 'sync', array $replace = [], array $legacy = []): array
    {
        return app(MapMarkerImportExecutor::class)->execute(
            $this->provider, ['stillwater-bayou'], $selection, $mode,
            $this->fingerprint($selection, $mode), $replace,
            ['stillwater-bayou' => $legacy],
        );
    }

    private function fingerprint(array $selection, string $mode = 'sync'): string
    {
        return app(MapMarkerImportSourcePlan::class)->build($this->provider, ['stillwater-bayou'], $selection, $mode)['fingerprint'];
    }

    private function marker(HntMap $map, string $type, string $legacyKey, ?string $sourceKey = null, ?string $provider = null): HntMapMarker
    {
        return $map->markers()->create([
            'legacy_key' => $legacyKey, 'type' => $type, 'x' => 100, 'y' => 100,
            'source_provider' => $provider, 'source_key' => $sourceKey,
        ]);
    }
}

final class TestMapMarkerProvider implements MapMarkerImportProviderInterface
{
    public array $rows;
    public array $outOfBounds = [];
    public array $outOfBoundsKeys = [];

    public function __construct()
    {
        $this->rows = [
            'stillwater-bayou' => [
                $this->row('tower-1', 'tower', 'hunting', 1000, 2000),
                $this->row('beetle-1', 'beetle', null, 400, 600),
            ],
            'lawson-delta' => [$this->row('lawson-tower', 'tower', 'hunting', 1000, 2000)],
        ];
    }
    public function row(string $key, string $type, ?string $subtype, float $x, float $y): array
    {
        return [
            'source_key' => $key,
            'source_category' => match ($type) { 'tower' => 'towers', 'beetle' => 'beetles', 'wild_target' => 'wild_targets', default => 'easter_eggs' },
            'type' => $type, 'subtype' => $subtype, 'x' => $x, 'y' => $y,
            'label_de' => null, 'label_en' => null,
        ];
    }
    public function id(): string { return 'kamille'; }
    public function name(): string { return 'Kamille'; }
    public function maps(): array { return ['stillwater-bayou' => ['id' => 1, 'name' => 'Stillwater Bayou'], 'lawson-delta' => ['id' => 2, 'name' => 'Lawson Delta']]; }
    public function categories(): array
    {
        return [
            'tower:hunting' => ['source_category' => 'towers', 'type' => 'tower', 'subtype' => 'hunting'],
            'beetle' => ['source_category' => 'beetles', 'type' => 'beetle', 'subtype' => null],
            'wild_target' => ['source_category' => 'wild_targets', 'type' => 'wild_target', 'subtype' => null],
            'easter_egg' => ['source_category' => 'easter_eggs', 'type' => 'easter_egg', 'subtype' => null],
        ];
    }
    public function markers(string $mapSlug): array { return $this->rows[$mapSlug]; }
    public function outOfBounds(string $mapSlug): array { return $this->outOfBounds; }
    public function outOfBoundsKeys(string $mapSlug): array { return $this->outOfBoundsKeys; }
}
