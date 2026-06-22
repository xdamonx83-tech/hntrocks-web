<?php

namespace Tests\Feature\Maps;

use App\Models\ApiAccessToken;
use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApiMapMarkerInteractionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_read_approved_cash_spot_comments(): void
    {
        $marker = $this->marker();
        $comment = $this->comment($marker, User::factory()->create());

        $this->getJson(route('api.v1.maps.markers.comments.index', $marker))
            ->assertOk()
            ->assertJsonPath('comments.0.id', $comment->id)
            ->assertJsonPath('comments.0.body', 'Original comment')
            ->assertJsonPath('comments.0.can_edit', false)
            ->assertJsonPath('comments.0.can_delete', false)
            ->assertJsonPath('comment_count', 1)
            ->assertJsonPath('viewer_can_comment', false)
            ->assertJsonStructure([
                'comments' => [[
                    'id',
                    'body',
                    'created_at',
                    'updated_at',
                    'created_at_label',
                    'user' => ['id', 'name', 'avatar_url'],
                    'can_edit',
                    'can_delete',
                ]],
                'comment_count',
                'viewer_can_comment',
            ]);
    }

    public function test_valid_optional_bearer_token_exposes_comment_permissions(): void
    {
        $owner = User::factory()->create();
        $marker = $this->marker();
        $this->comment($marker, $owner);

        $this->withToken($this->token($owner))
            ->getJson(route('api.v1.maps.markers.comments.index', $marker))
            ->assertOk()
            ->assertJsonPath('viewer_can_comment', true)
            ->assertJsonPath('comments.0.can_edit', true)
            ->assertJsonPath('comments.0.can_delete', true);
    }

    public function test_guest_cannot_create_a_comment(): void
    {
        $this->postJson(route('api.v1.maps.markers.comments.store', $this->marker()), ['body' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_comments_reject_non_cash_and_unapproved_markers(): void
    {
        foreach ([$this->marker(['type' => 'supply']), $this->marker(['status' => 'pending'])] as $marker) {
            $this->getJson(route('api.v1.maps.markers.comments.index', $marker))->assertNotFound();
        }
    }

    public function test_authenticated_user_can_create_a_comment(): void
    {
        $user = User::factory()->create();
        $marker = $this->marker();

        $this->withToken($this->token($user))
            ->postJson(route('api.v1.maps.markers.comments.store', $marker), ['body' => '  Mobile comment  '])
            ->assertCreated()
            ->assertJsonPath('comment.body', 'Mobile comment')
            ->assertJsonPath('comment.can_edit', true)
            ->assertJsonPath('comment_count', 1);

        $this->assertDatabaseHas('hnt_map_marker_comments', [
            'hnt_map_marker_id' => $marker->id,
            'user_id' => $user->id,
            'body' => 'Mobile comment',
        ]);
    }

    public function test_owner_can_update_a_comment_but_another_user_cannot(): void
    {
        $owner = User::factory()->create();
        $comment = $this->comment($this->marker(), $owner);

        $this->withToken($this->token(User::factory()->create()))
            ->patchJson(route('api.v1.maps.marker-comments.update', $comment), ['body' => 'Nope'])
            ->assertForbidden();

        $this->withToken($this->token($owner))
            ->patchJson(route('api.v1.maps.marker-comments.update', $comment), ['body' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('comment.body', 'Updated');
    }

    public function test_owner_and_admin_can_delete_comments_but_another_user_cannot(): void
    {
        $owner = User::factory()->create();
        $ownerComment = $this->comment($this->marker(), $owner);

        $this->withToken($this->token(User::factory()->create()))
            ->deleteJson(route('api.v1.maps.marker-comments.destroy', $ownerComment))
            ->assertForbidden();

        $this->withToken($this->token($owner))
            ->deleteJson(route('api.v1.maps.marker-comments.destroy', $ownerComment))
            ->assertOk()
            ->assertJson(['ok' => true, 'comment_count' => 0]);
        $this->assertSoftDeleted('hnt_map_marker_comments', ['id' => $ownerComment->id]);

        $adminComment = $this->comment($this->marker(), User::factory()->create());
        $admin = User::factory()->create(['is_admin' => true]);

        $this->withToken($this->token($admin))
            ->deleteJson(route('api.v1.maps.marker-comments.destroy', $adminComment))
            ->assertOk();
        $this->assertSoftDeleted('hnt_map_marker_comments', ['id' => $adminComment->id]);
    }

    public function test_guest_vote_uses_hashed_mobile_visitor_id_and_toggles(): void
    {
        $marker = $this->marker();
        $visitorId = 'flutter-installation-123';
        $visitorHash = hash_hmac('sha256', $visitorId, (string) config('app.key'));

        $this->withHeader('X-HNT-Visitor-ID', $visitorId)
            ->postJson(route('api.v1.maps.markers.vote', $marker), ['value' => 1])
            ->assertOk()
            ->assertJson(['ok' => true, 'up_count' => 1, 'down_count' => 0, 'viewer_vote' => 1]);

        $this->assertDatabaseHas('hnt_map_marker_votes', [
            'hnt_map_marker_id' => $marker->id,
            'user_id' => null,
            'visitor_hash' => $visitorHash,
            'value' => 1,
        ]);
        $this->assertDatabaseMissing('hnt_map_marker_votes', ['visitor_hash' => $visitorId]);

        $this->withHeader('X-HNT-Visitor-ID', $visitorId)
            ->postJson(route('api.v1.maps.markers.vote', $marker), ['value' => 1])
            ->assertOk()
            ->assertJson(['up_count' => 0, 'down_count' => 0, 'viewer_vote' => null]);

        $this->assertDatabaseMissing('hnt_map_marker_votes', ['visitor_hash' => $visitorHash]);
    }

    public function test_guest_vote_requires_a_mobile_visitor_id(): void
    {
        $this->postJson(route('api.v1.maps.markers.vote', $this->marker()), ['value' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('visitor_id');
    }

    public function test_guest_vote_also_accepts_visitor_id_in_the_request_body(): void
    {
        $this->postJson(route('api.v1.maps.markers.vote', $this->marker()), [
            'value' => 1,
            'visitor_id' => 'flutter-body-visitor',
        ])->assertOk()->assertJsonPath('viewer_vote', 1);
    }

    public function test_authenticated_user_vote_is_bound_to_user(): void
    {
        $user = User::factory()->create();
        $marker = $this->marker();

        $this->withToken($this->token($user))
            ->postJson(route('api.v1.maps.markers.vote', $marker), ['value' => -1])
            ->assertOk()
            ->assertJson(['up_count' => 0, 'down_count' => 1, 'viewer_vote' => -1]);

        $this->assertDatabaseHas('hnt_map_marker_votes', [
            'hnt_map_marker_id' => $marker->id,
            'user_id' => $user->id,
            'visitor_hash' => null,
            'value' => -1,
        ]);
    }

    public function test_vote_rejects_non_cash_and_unapproved_markers(): void
    {
        foreach ([$this->marker(['type' => 'supply']), $this->marker(['status' => 'pending'])] as $marker) {
            $this->withHeader('X-HNT-Visitor-ID', 'mobile-visitor')
                ->postJson(route('api.v1.maps.markers.vote', $marker), ['value' => 1])
                ->assertNotFound();
        }
    }

    public function test_public_interaction_endpoints_reject_an_invalid_bearer_token(): void
    {
        $marker = $this->marker();

        $this->withToken('999|invalid')
            ->getJson(route('api.v1.maps.markers.comments.index', $marker))
            ->assertUnauthorized();

        $this->withToken('999|invalid')
            ->withHeader('X-HNT-Visitor-ID', 'mobile-visitor')
            ->postJson(route('api.v1.maps.markers.vote', $marker), ['value' => 1])
            ->assertUnauthorized();
    }

    public function test_web_interaction_routes_remain_registered(): void
    {
        $this->assertTrue(Route::has('maps.markers.vote'));
        $this->assertTrue(Route::has('maps.markers.comments.index'));
        $this->assertTrue(Route::has('maps.markers.comments.store'));
        $this->assertTrue(Route::has('maps.marker-comments.update'));
        $this->assertTrue(Route::has('maps.marker-comments.destroy'));
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Maps API test')['access_token'];
    }

    private function comment(HntMapMarker $marker, User $author): HntMapMarkerComment
    {
        return $marker->comments()->create([
            'user_id' => $author->id,
            'body' => 'Original comment',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function marker(array $overrides = []): HntMapMarker
    {
        $map = HntMap::create([
            'slug' => 'api-interactions-'.fake()->unique()->word(),
            'name' => 'API Interaction Test Map',
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
