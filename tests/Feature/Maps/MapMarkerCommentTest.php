<?php

namespace Tests\Feature\Maps;

use App\Models\HntMap;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapMarkerCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_read_comments_but_cannot_create_them(): void
    {
        $marker = $this->marker();
        $comment = $marker->comments()->create([
            'user_id' => User::factory()->create()->id,
            'body' => 'Visible comment',
        ]);

        $this->getJson(route('maps.markers.comments.index', $marker))
            ->assertOk()
            ->assertJsonPath('comments.0.id', $comment->id)
            ->assertJsonPath('comments.0.body', 'Visible comment')
            ->assertJsonPath('comment_count', 1)
            ->assertJsonPath('viewer_can_comment', false);

        $this->postJson(route('maps.markers.comments.store', $marker), ['body' => 'Nope'])
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_create_a_trimmed_comment(): void
    {
        $user = User::factory()->create();
        $marker = $this->marker();

        $this->actingAs($user)
            ->postJson(route('maps.markers.comments.store', $marker), ['body' => '  Useful spot  '])
            ->assertCreated()
            ->assertJsonPath('comment.body', 'Useful spot')
            ->assertJsonPath('comment.can_edit', true)
            ->assertJsonPath('comment_count', 1);

        $this->assertDatabaseHas('hnt_map_marker_comments', [
            'hnt_map_marker_id' => $marker->id,
            'user_id' => $user->id,
            'body' => 'Useful spot',
        ]);
    }

    public function test_empty_comment_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('maps.markers.comments.store', $this->marker()), ['body' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_only_approved_cash_markers_support_comments(): void
    {
        $user = User::factory()->create();

        foreach ([$this->marker(['type' => 'supply']), $this->marker(['status' => 'pending'])] as $marker) {
            $this->getJson(route('maps.markers.comments.index', $marker))->assertNotFound();
            $this->actingAs($user)
                ->postJson(route('maps.markers.comments.store', $marker), ['body' => 'Nope'])
                ->assertNotFound();
        }
    }

    public function test_author_can_update_and_delete_own_comment(): void
    {
        $author = User::factory()->create();
        $comment = $this->comment($author);

        $this->actingAs($author)
            ->patchJson(route('maps.marker-comments.update', $comment), ['body' => 'Updated'])
            ->assertOk()
            ->assertJsonPath('comment.body', 'Updated');

        $this->actingAs($author)
            ->deleteJson(route('maps.marker-comments.destroy', $comment))
            ->assertOk()
            ->assertJsonPath('comment_count', 0);

        $this->assertSoftDeleted('hnt_map_marker_comments', ['id' => $comment->id]);
    }

    public function test_other_user_cannot_update_or_delete_a_comment(): void
    {
        $comment = $this->comment(User::factory()->create());
        $other = User::factory()->create();

        $this->actingAs($other)
            ->patchJson(route('maps.marker-comments.update', $comment), ['body' => 'Nope'])
            ->assertForbidden();
        $this->actingAs($other)
            ->deleteJson(route('maps.marker-comments.destroy', $comment))
            ->assertForbidden();
    }

    public function test_admin_can_delete_a_comment(): void
    {
        $comment = $this->comment(User::factory()->create());
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->deleteJson(route('maps.marker-comments.destroy', $comment))
            ->assertOk();

        $this->assertSoftDeleted('hnt_map_marker_comments', ['id' => $comment->id]);
    }

    private function comment(User $author): HntMapMarkerComment
    {
        return $this->marker()->comments()->create([
            'user_id' => $author->id,
            'body' => 'Original',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function marker(array $overrides = []): HntMapMarker
    {
        $map = HntMap::create([
            'slug' => 'comment-test-'.fake()->unique()->word(),
            'name' => 'Comment Test Map',
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
