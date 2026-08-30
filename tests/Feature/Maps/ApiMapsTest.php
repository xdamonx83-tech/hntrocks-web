<?php

namespace Tests\Feature\Maps;

use App\Models\HntMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiMapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_index_is_public_and_uses_active_database_maps(): void
    {
        HntMap::query()->create([
            'slug' => 'remote-test-map',
            'name' => 'Remote Test Map',
            'width' => 2048,
            'height' => 2048,
            'image_path' => 'assets/hnt/maps/stillwater-bayou/map.webp',
            'lines_path' => null,
            'sort_order' => 5,
            'is_active' => true,
        ]);

        HntMap::query()->create([
            'slug' => 'hidden-test-map',
            'name' => 'Hidden Test Map',
            'width' => 2048,
            'height' => 2048,
            'image_path' => 'assets/hnt/maps/stillwater-bayou/map.webp',
            'sort_order' => 6,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/v1/maps')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.slug', 'remote-test-map')
            ->assertJsonPath('data.0.name', 'Remote Test Map')
            ->assertJsonStructure([
                'data' => [[
                    'slug',
                    'name',
                    'width',
                    'height',
                    'image_url',
                    'lines_url',
                    'marker_types',
                    'marker_count',
                    'marker_counts',
                ]],
            ]);

        $this->assertStringStartsWith('http', $response->json('data.0.image_url'));
        $this->assertNull($response->json('data.0.lines_url'));
    }

    public function test_dynamic_database_map_detail_is_public(): void
    {
        HntMap::query()->create([
            'slug' => 'new-hunt-map',
            'name' => 'New Hunt Map',
            'width' => 3072,
            'height' => 3072,
            'image_path' => 'assets/hnt/maps/stillwater-bayou/map.webp',
            'lines_path' => null,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/v1/maps/new-hunt-map')
            ->assertOk()
            ->assertJsonPath('data.slug', 'new-hunt-map')
            ->assertJsonPath('data.name', 'New Hunt Map')
            ->assertJsonPath('data.width', 3072)
            ->assertJsonPath('data.height', 3072)
            ->assertJsonStructure([
                'data' => [
                    'slug',
                    'name',
                    'width',
                    'height',
                    'image_url',
                    'lines_url',
                    'marker_types',
                    'markers',
                ],
            ]);

        $this->assertIsArray($response->json('data.markers'));
    }

    public function test_unknown_or_inactive_map_slug_returns_not_found(): void
    {
        HntMap::query()->create([
            'slug' => 'inactive-map',
            'name' => 'Inactive Map',
            'width' => 2048,
            'height' => 2048,
            'image_path' => 'assets/hnt/maps/stillwater-bayou/map.webp',
            'is_active' => false,
        ]);

        $this->getJson('/api/v1/maps/unknown-map')->assertNotFound();
        $this->getJson('/api/v1/maps/inactive-map')->assertNotFound();
    }

    public function test_api_returns_only_approved_database_markers_and_never_resolves_viewer_vote(): void
    {
        $map = HntMap::query()->create([
            'slug' => 'marker-test-map',
            'name' => 'Marker Test Map',
            'width' => 2048,
            'height' => 2048,
            'image_path' => 'assets/hnt/maps/stillwater-bayou/map.webp',
            'is_active' => true,
        ]);

        $approved = $map->markers()->create([
            'legacy_key' => 'approved-api-marker',
            'type' => 'cash',
            'x' => 120.5,
            'y' => 240.25,
            'label_de' => 'Genehmigt',
            'label_en' => 'Approved',
            'status' => 'approved',
        ]);

        $map->markers()->create([
            'legacy_key' => 'pending-api-marker',
            'type' => 'cash',
            'x' => 300,
            'y' => 400,
            'status' => 'pending',
        ]);

        $this->getJson('/api/v1/maps/marker-test-map')
            ->assertOk()
            ->assertJsonCount(1, 'data.markers')
            ->assertJsonPath('data.markers.0.id', $approved->id)
            ->assertJsonPath('data.markers.0.x', 120.5)
            ->assertJsonPath('data.markers.0.y', 240.25)
            ->assertJsonPath('data.markers.0.viewer_vote', null);
    }
}
