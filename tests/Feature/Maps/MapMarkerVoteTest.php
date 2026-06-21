<?php

namespace Tests\Feature\Maps;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerVote;
use App\Models\User;
use App\Support\MapVoteVisitorIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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

        $this->assertDatabaseHas('hnt_map_marker_votes', [
            'hnt_map_marker_id' => $marker->id,
            'user_id' => $user->id,
            'visitor_hash' => null,
            'value' => 1,
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

    public function test_guest_can_switch_and_remove_a_vote_using_an_anonymous_browser_cookie(): void
    {
        $marker = $this->marker();
        $token = (string) Str::uuid();
        $visitorHash = hash_hmac('sha256', $token, (string) config('app.key'));

        $this->withHeader('User-Agent', 'HNT Maps vote test')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->withCookie(MapVoteVisitorIdentity::COOKIE_NAME, $token)
            ->postJson(route('maps.markers.vote', $marker), ['value' => 1])
            ->assertOk()
            ->assertJson([
                'up_count' => 1,
                'down_count' => 0,
                'viewer_vote' => 1,
            ]);

        $vote = HntMapMarkerVote::query()->where('visitor_hash', $visitorHash)->firstOrFail();

        $this->assertNull($vote->user_id);
        $this->assertSame(hash_hmac('sha256', '203.0.113.10', (string) config('app.key')), $vote->ip_hash);
        $this->assertSame(hash_hmac('sha256', 'HNT Maps vote test', (string) config('app.key')), $vote->user_agent_hash);

        $this->withCookie(MapVoteVisitorIdentity::COOKIE_NAME, $token)
            ->postJson(route('maps.markers.vote', $marker), ['value' => -1])
            ->assertOk()
            ->assertJson([
                'up_count' => 0,
                'down_count' => 1,
                'viewer_vote' => -1,
            ]);

        $this->withCookie(MapVoteVisitorIdentity::COOKIE_NAME, $token)
            ->postJson(route('maps.markers.vote', $marker), ['value' => -1])
            ->assertOk()
            ->assertJson([
                'up_count' => 0,
                'down_count' => 0,
                'viewer_vote' => null,
            ]);

        $this->assertDatabaseMissing('hnt_map_marker_votes', [
            'hnt_map_marker_id' => $marker->id,
            'visitor_hash' => $visitorHash,
        ]);
    }

    public function test_first_guest_vote_issues_an_anonymous_visitor_cookie(): void
    {
        $this->postJson(route('maps.markers.vote', $this->marker()), ['value' => 1])
            ->assertOk()
            ->assertCookie(MapVoteVisitorIdentity::COOKIE_NAME);
    }

    public function test_non_approved_or_non_cash_markers_cannot_be_voted_on(): void
    {
        $nonCashMarker = $this->marker(['type' => 'supply']);
        $pendingMarker = $this->marker(['status' => 'pending']);

        $this->postJson(route('maps.markers.vote', $nonCashMarker), ['value' => 1])
            ->assertNotFound();
        $this->postJson(route('maps.markers.vote', $pendingMarker), ['value' => 1])
            ->assertNotFound();
    }

    public function test_vote_value_must_be_up_or_down(): void
    {
        $this->postJson(route('maps.markers.vote', $this->marker()), ['value' => 0])
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
