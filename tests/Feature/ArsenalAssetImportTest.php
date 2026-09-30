<?php

namespace Tests\Feature;

use App\Models\EquipmentItem;
use App\Models\EquipmentSource;
use App\Services\Equipment\EquipmentAssetImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArsenalAssetImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_fan_kit_import_matches_original_asset_filename_and_publishes_only_local_asset(): void
    {
        Storage::fake('public');

        $source = EquipmentSource::create([
            'key' => 'huntify',
            'name' => 'Huntify HuntWiki',
            'base_url' => 'https://wiki.huntify.win',
        ]);

        $item = EquipmentItem::create([
            'source_id' => $source->id,
            'external_id' => 'rifle-1865',
            'slug' => '1865-carbine',
            'name' => '1865 Carbine',
            'item_type' => 'weapon',
            'equipment_class' => 'Rifle',
            'comparison_group' => 'weapon:rifle',
            'source_status' => 'active',
            'original_asset_url' => 'https://wiki.huntify.win/Hunt/assets/icons/weapons/1865-carbine.png',
        ]);

        $directory = sys_get_temp_dir().'/hnt-arsenal-assets-'.bin2hex(random_bytes(4));
        mkdir($directory, 0700, true);
        file_put_contents(
            $directory.'/1865-carbine.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9ZQmcAAAAASUVORK5CYII=')
        );

        try {
            $service = app(EquipmentAssetImportService::class);

            $dryRun = $service->importFanKit($directory, true);
            $this->assertSame(1, $dryRun['counts']['matched']);
            $this->assertSame(1, $dryRun['counts']['imported']);
            $this->assertNull($item->fresh()->local_asset_path);
            Storage::disk('public')->assertMissing('arsenal/items/1865-carbine.png');

            $result = $service->importFanKit($directory);
            $this->assertSame(1, $result['counts']['matched']);
            $this->assertSame(1, $result['counts']['imported']);

            $item = $item->fresh();
            $this->assertSame('arsenal/items/1865-carbine.png', $item->local_asset_path);
            $this->assertStringContainsString('Crytek Hunt Fan Kit', (string) $item->license_note);
            Storage::disk('public')->assertExists('arsenal/items/1865-carbine.png');
            $this->assertStringContainsString('/storage/arsenal/items/1865-carbine.png', (string) $item->imageUrl());
        } finally {
            @unlink($directory.'/1865-carbine.png');
            @rmdir($directory);
        }
    }
}
