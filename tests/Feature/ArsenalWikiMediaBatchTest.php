<?php

namespace Tests\Feature;

use App\Models\EquipmentFamily;
use App\Models\EquipmentItem;
use App\Models\EquipmentSkin;
use App\Models\EquipmentSource;
use App\Models\EquipmentSourceSnapshot;
use App\Services\Equipment\WikiGgEquipmentSource;
use App\Services\Equipment\WikiGgMediaBatchService;
use App\Services\Equipment\WikiGgMediaImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArsenalWikiMediaBatchTest extends TestCase
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

    private function item(string $slug = 'drilling'): EquipmentItem
    {
        $source = EquipmentSource::firstOrCreate(['key' => 'huntify'], ['name' => 'Huntify']);
        $family = EquipmentFamily::firstOrCreate(['key' => 'drilling'], ['name' => 'Drilling']);

        return EquipmentItem::create([
            'source_id' => $source->id, 'external_id' => $slug, 'slug' => $slug,
            'name' => $slug === 'drilling' ? 'Drilling' : 'Second Weapon',
            'item_type' => 'weapon', 'comparison_group' => 'weapon:rifle',
            'family_id' => $family->id, 'source_status' => 'active',
            'local_asset_path' => 'arsenal/items/existing.jpg',
        ]);
    }

    private function wiki(array $skins, int|string|null $revision = 123): array
    {
        return [
            'page_title' => 'Weapons/Drilling',
            'page_url' => 'https://huntshowdown.wiki.gg/wiki/Weapons/Drilling',
            'revision_id' => $revision,
            'resolution_method' => 'direct', 'resolution_score' => 100,
            'base_image_confidence' => 100,
            'base_image' => ['url' => 'https://huntshowdown.wiki.gg/images/base.jpg'],
            'skins' => $skins,
        ];
    }

    private function skin(string $name, int $score = 100, bool $image = true): array
    {
        return [
            'name' => $name, 'image_file' => 'Weapon 3D Drilling '.$name.'.jpg',
            'image_confidence' => $score, 'image_ambiguous' => false,
            'image' => $image ? [
                'file' => 'Weapon 3D Drilling '.$name.'.jpg',
                'url' => 'https://huntshowdown.wiki.gg/images/'.rawurlencode($name).'.jpg',
                'sha1' => 'source-sha1',
            ] : null,
        ];
    }

    public function test_plan_imports_only_exact_existing_skin_without_local_image(): void
    {
        $item = $this->item();
        foreach (['Safe', 'Already Local', 'Low Score', 'Missing'] as $name) {
            EquipmentSkin::create([
                'equipment_item_id' => $item->id, 'external_id' => $name, 'name' => $name,
                'local_asset_path' => $name === 'Already Local' ? 'arsenal/skins/already.jpg' : null,
            ]);
        }
        $wiki = $this->wiki([
            $this->skin('Safe'), $this->skin('Already Local'),
            $this->skin('Low Score', 65), $this->skin('Missing', 0, false),
            $this->skin('New Skin'),
        ]);
        $plan = app(WikiGgMediaBatchService::class)->plan($item, $wiki);

        $this->assertSame(['IMPORT', 'SKIP', 'REVIEW_REQUIRED', 'REVIEW_REQUIRED', 'REVIEW_REQUIRED'],
            array_column($plan['rows'], 'action'));
        $this->assertSame('REVIEW_REQUIRED', $plan['base_action']);
        $this->assertSame('No exact existing skin mapping', $plan['rows'][4]['reason']);
        $this->assertSame('REVIEW_REQUIRED', app(WikiGgMediaBatchService::class)
            ->plan($item, $this->wiki([$this->skin('Safe')], null))['rows'][0]['action']);
    }

    public function test_safe_apply_is_idempotent_and_preserves_existing_huntify_metadata(): void
    {
        Storage::fake('public');
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=');
        Http::fake(['https://huntshowdown.wiki.gg/images/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);
        $item = $this->item();
        $skin = EquipmentSkin::create([
            'equipment_item_id' => $item->id, 'external_id' => 'safe', 'name' => 'Safe',
            'source_url' => 'https://wiki.huntify.win/original',
            'original_asset_url' => 'https://wiki.huntify.win/original.jpg',
            'license_note' => 'Existing Huntify note',
        ]);
        $wiki = $this->wiki([$this->skin('Safe')]);
        $service = app(WikiGgMediaBatchService::class);
        $media = app(WikiGgMediaImportService::class);

        $first = $media->applySafeSkinBatch($item, $wiki, $service->plan($item, $wiki));
        $this->assertSame(1, $first['images_downloaded']);
        $this->assertSame(1, $first['skins_updated']);
        $this->assertSame(0, $first['skins_created']);
        Storage::disk('public')->assertExists($skin->fresh()->local_asset_path);
        $this->assertSame('arsenal/items/existing.jpg', $item->fresh()->local_asset_path);
        $this->assertSame('https://wiki.huntify.win/original.jpg', $skin->fresh()->original_asset_url);
        $this->assertSame('Existing Huntify note', $skin->fresh()->license_note);
        $this->assertSame('source-sha1', data_get($skin->fresh()->facts, 'wiki_gg.image.source_sha1'));

        $secondPlan = $service->plan($item->fresh(), $wiki);
        $this->assertSame('SKIP', $secondPlan['rows'][0]['action']);
        $second = $media->applySafeSkinBatch($item->fresh(), $wiki, $secondPlan);
        $this->assertSame(0, $second['images_downloaded']);
        $this->assertSame(0, $second['images_skipped']);
        $this->assertSame(1, EquipmentSkin::count());
        $this->assertSame(1, EquipmentSourceSnapshot::count());
        Http::assertSentCount(1);
    }

    public function test_bad_item_does_not_stop_dry_run_by_default(): void
    {
        $this->item('first');
        $this->item('second');
        $source = new class extends WikiGgEquipmentSource {
            public function preview(EquipmentItem $item, bool $withMedia = true, bool $withRevision = true): array
            {
                if ($item->slug === 'first') throw new \RuntimeException('Broken page');
                return ['revision_id' => 123, 'page_title' => 'Weapons/Second_Weapon',
                    'resolution_method' => 'direct', 'resolution_score' => 100, 'skins' => []];
            }
        };
        app()->instance(WikiGgEquipmentSource::class, $source);

        $exit = Artisan::call('arsenal:wiki-media-batch', [
            '--type' => 'weapon', '--dry-run' => true, '--limit' => 0,
            '--sleep-ms' => 0, '--show' => 1,
        ]);
        $this->assertSame(1, $exit);
        $output = Artisan::output();
        $this->assertStringContainsString('Items Resolved', $output);
        $this->assertStringContainsString('Broken page', $output);
        $this->assertSame(0, EquipmentSourceSnapshot::count());
    }

    public function test_dry_run_plans_import_without_database_or_media_writes(): void
    {
        Storage::fake('public');
        Http::fake();
        $item = $this->item();
        $skin = EquipmentSkin::create([
            'equipment_item_id' => $item->id, 'external_id' => 'safe', 'name' => 'Safe',
        ]);
        $wiki = $this->wiki([$this->skin('Safe')]);
        $source = new class($wiki) extends WikiGgEquipmentSource {
            public function __construct(private array $wiki) {}
            public function preview(EquipmentItem $item, bool $withMedia = true, bool $withRevision = true): array
            {
                return $this->wiki;
            }
        };
        app()->instance(WikiGgEquipmentSource::class, $source);

        $exit = Artisan::call('arsenal:wiki-media-batch', [
            '--type' => 'weapon', '--dry-run' => true, '--limit' => 1, '--sleep-ms' => 0,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Auto Importable', Artisan::output());
        $this->assertNull($skin->fresh()->local_asset_path);
        $this->assertSame(0, EquipmentSourceSnapshot::count());
        $this->assertSame(0, count(Storage::disk('public')->allFiles()));
        Http::assertNothingSent();
    }

    public function test_existing_storage_file_is_never_downloaded_or_overwritten(): void
    {
        Storage::fake('public');
        Http::fake();
        $item = $this->item();
        $skin = EquipmentSkin::create([
            'equipment_item_id' => $item->id, 'external_id' => 'safe', 'name' => 'Safe',
        ]);
        $wiki = $this->wiki([$this->skin('Safe')]);
        $url = $wiki['skins'][0]['image']['url'];
        $path = 'arsenal/wiki/skins/drilling/safe-'.substr(hash('sha256', $url), 0, 12).'.jpg';
        Storage::disk('public')->put($path, 'existing image bytes');

        $result = app(WikiGgMediaImportService::class)->applySafeSkinBatch(
            $item, $wiki, app(WikiGgMediaBatchService::class)->plan($item, $wiki),
        );

        $this->assertSame(0, $result['images_downloaded']);
        $this->assertSame(1, $result['images_skipped']);
        $this->assertNull($skin->fresh()->local_asset_path);
        $this->assertSame('existing image bytes', Storage::disk('public')->get($path));
        Http::assertNothingSent();
    }

    public function test_revision_change_skips_apply_without_download_or_base_replacement(): void
    {
        Storage::fake('public');
        Http::fake();
        $item = $this->item();
        $skin = EquipmentSkin::create(['equipment_item_id' => $item->id, 'external_id' => 'safe', 'name' => 'Safe']);
        $first = $this->wiki([$this->skin('Safe')], 123);
        $second = $this->wiki([$this->skin('Safe')], 124);
        $source = new class($first, $second) extends WikiGgEquipmentSource {
            private int $calls = 0;
            public function __construct(private array $first, private array $second) {}
            public function preview(EquipmentItem $item, bool $withMedia = true, bool $withRevision = true): array
            {
                return ++$this->calls === 1 ? $this->first : $this->second;
            }
        };
        app()->instance(WikiGgEquipmentSource::class, $source);

        $exit = Artisan::call('arsenal:wiki-media-batch', [
            '--type' => 'weapon', '--apply' => true, '--limit' => 1,
            '--sleep-ms' => 0, '--show' => 1,
        ]);
        $this->assertSame(0, $exit);
        $this->assertNull($skin->fresh()->local_asset_path);
        $this->assertSame('arsenal/items/existing.jpg', $item->fresh()->local_asset_path);
        $this->assertSame(0, EquipmentSourceSnapshot::count());
        $this->assertStringContainsString('Wiki revision or image metadata changed after plan', Artisan::output());
        Http::assertNothingSent();
    }
}
