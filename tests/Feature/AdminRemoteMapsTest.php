<?php

namespace Tests\Feature;

use App\Models\HntMap;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminRemoteMapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_manage_remote_maps(): void
    {
        $user = $this->user();

        $this->actingAs($user)->get('/admin/maps/create')->assertForbidden();
        $this->actingAs($user)->post('/admin/maps', [])->assertForbidden();
    }

    public function test_admin_can_create_inactive_remote_map_with_uploaded_assets(): void
    {
        Storage::fake('public');
        $admin = $this->user(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/maps', [
            'name' => 'Remote Test Map',
            'slug' => 'remote-test-map',
            'width' => 2048,
            'height' => 2048,
            'sort_order' => 7,
            'map_image' => UploadedFile::fake()->image('map.jpg', 1200, 1200),
            'lines_image' => UploadedFile::fake()->image('lines.png', 1200, 1200),
        ]);

        $map = HntMap::query()->where('slug', 'remote-test-map')->firstOrFail();

        $response->assertRedirect(route('admin.maps.edit', $map));

        $this->assertFalse($map->is_active);
        $this->assertSame('Remote Test Map', $map->name);
        $this->assertStringStartsWith('storage/maps/remote-test-map/map-', (string) $map->image_path);
        $this->assertStringStartsWith('storage/maps/remote-test-map/lines-', (string) $map->lines_path);

        Storage::disk('public')->assertExists(substr((string) $map->image_path, strlen('storage/')));
        Storage::disk('public')->assertExists(substr((string) $map->lines_path, strlen('storage/')));
    }

    public function test_admin_can_activate_existing_remote_map_without_reuploading_image(): void
    {
        Storage::fake('public');
        $admin = $this->user(['is_admin' => true]);

        Storage::disk('public')->put('maps/remote-test-map/map-test.png', 'fake-image');

        $map = HntMap::query()->create([
            'slug' => 'remote-test-map',
            'name' => 'Remote Test Map',
            'width' => 2048,
            'height' => 2048,
            'image_path' => 'storage/maps/remote-test-map/map-test.png',
            'lines_path' => null,
            'sort_order' => 0,
            'is_active' => false,
        ]);

        $this->actingAs($admin)->put('/admin/maps/'.$map->slug, [
            'name' => 'Remote Test Map',
            'width' => 2048,
            'height' => 2048,
            'sort_order' => 0,
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertTrue($map->fresh()->is_active);

        $this->getJson('/api/v1/maps')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'remote-test-map');
    }

    private function user(array $attributes = []): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create(array_merge([
            'name' => 'Hunter '.$suffix,
            'username' => 'hunter_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ], $attributes));
    }
}
