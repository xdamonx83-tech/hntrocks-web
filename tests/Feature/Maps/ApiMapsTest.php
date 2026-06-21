<?php

namespace Tests\Feature\Maps;

use App\Models\HntMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiMapsTest extends TestCase
{
    use RefreshDatabase;

    public function test_maps_index_is_public_and_contains_stillwater_bayou(): void
    {
        $response = $this->getJson('/api/v1/maps')
            ->assertOk()
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
                ]],
            ]);

        $this->assertIsArray($response->json('data'));
        $this->assertTrue(
            collect($response->json('data'))->contains('slug', 'stillwater-bayou')
        );
    }

    public function test_stillwater_bayou_is_public_and_returns_native_map_data(): void
    {
        $response = $this->getJson('/api/v1/maps/stillwater-bayou')
            ->assertOk()
            ->assertJsonPath('data.slug', 'stillwater-bayou')
            ->assertJsonPath('data.width', 2048)
            ->assertJsonPath('data.height', 2048)
            ->assertJsonStructure([
                'data' => [
                    'slug',
                    'name',
                    'width',
                    'height',
                    'image_url',
                    'lines_url',
                    'marker_types',
                    'markers' => [[
                        'id',
                        'type',
                        'x',
                        'y',
                        'label',
                        'label_de',
                        'label_en',
                        'image_url',
                        'up_count',
                        'down_count',
                        'viewer_vote',
                        'comment_count',
                    ]],
                ],
            ]);

        $this->assertStringStartsWith('http', $response->json('data.image_url'));
        $linesUrl = $response->json('data.lines_url');
        $this->assertTrue($linesUrl === null || is_string($linesUrl));

        if ($linesUrl !== null) {
            $this->assertStringStartsWith('http', $linesUrl);
        }
        $this->assertIsArray($response->json('data.markers'));
    }

    public function test_unknown_map_slug_returns_not_found(): void
    {
        $this->getJson('/api/v1/maps/unknown-map')->assertNotFound();
    }

    public function test_api_returns_only_approved_database_markers_and_never_resolves_viewer_vote(): void
    {
        $map = HntMap::create([
            'slug' => 'stillwater-bayou',
            'name' => 'Stillwater Bayou',
            'width' => 2048,
            'height' => 2048,
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

        $this->getJson('/api/v1/maps/stillwater-bayou')
            ->assertOk()
            ->assertJsonCount(1, 'data.markers')
            ->assertJsonPath('data.markers.0.id', $approved->id)
            ->assertJsonPath('data.markers.0.x', 120.5)
            ->assertJsonPath('data.markers.0.y', 240.25)
            ->assertJsonPath('data.markers.0.viewer_vote', null);
    }
}
