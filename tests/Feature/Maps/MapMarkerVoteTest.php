<?php

namespace Tests\Feature\Maps;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapMarkerVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_toggle_and_switch_a_cash_marker_vote(): void
    {
        $user = User::factory()->create();
        $marker = $this->marker();

        $this->actingAs($user)
            ->postJson(route('maps.markers.vote', $marker), ['value' => 1])
            ->assertOk()
            ->assertJson([
                'ok' => true,
                'up_count' => 1,
                'down_count' => 0,
                'viewer_vote' => 1,
            ]);

        $this->actingAs($user)
            ->postJson(route('maps.markers.vote', $marker), ['value' => -1])
            ->assertOk()
            ->assertJson([
                'up_count' => 0,
                'down_count' => 1,
                'viewer_vote' => -1,
            ]);

        $this->actingAs($user)
            ->postJson(route('maps.markers.vote', $marker), ['value' => -1])
            ->assertOk()
            ->assertJson([
                'up_count' => 0,
                'down_count' => 0,
                'viewer_vote' => null,
            ]);

        $this->assertDatabaseMissing('hnt_map_marker_votes', [
            'hnt_map_marker_id' => $marker->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_guests_and_non_approved_or_non_cash_markers_cannot_vote(): void
    {
        $cashMarker = $this->marker();

        $this->postJson(route('maps.markers.vote', $cashMarker), ['value' => 1])
            ->assertUnauthorized();

        $user = User::factory()->create();
        $nonCashMarker = $this->marker(['type' => 'supply']);
        $pendingMarker = $this->marker(['status' => 'pending']);

        $this->actingAs($user)
            ->postJson(route('maps.markers.vote', $nonCashMarker), ['value' => 1])
            ->assertNotFound();
        $this->actingAs($user)
            ->postJson(route('maps.markers.vote', $pendingMarker), ['value' => 1])
            ->assertNotFound();
    }

    public function test_vote_value_must_be_up_or_down(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('maps.markers.vote', $this->marker()), ['value' => 0])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('value');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function marker(array $overrides = []): HntMapMarker
    {
        $map = HntMap::create([
            'slug' => 'test-map-'.fake()->unique()->word(),
            'name' => 'Test Map',
            'width' => 2048,
            'height' => 2048,
            'is_active' => true,
        ]);

        return $map->markers()->create(array_merge([
            'legacy_key' => fake()->unique()->uuid(),
            'type' => 'cash',
            'x' => 100,
            'y' => 200,
            'status' => 'approved',
        ], $overrides));
    }
}
