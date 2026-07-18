<?php

namespace Tests\Feature\Guides;

use App\Models\Guide;
use App\Models\GuideMedia;
use App\Models\GuideReputationEntry;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\Guides\GuideWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class GuideWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_a_guide(): void
    {
        $this->post(route('guides.store'))->assertRedirect(route('login'));
    }

    public function test_user_can_create_and_autosave_a_private_draft(): void
    {
        $author = User::factory()->create();

        $response = $this->actingAs($author)->post(route('guides.store'));
        $guide = Guide::query()->firstOrFail();

        $response->assertRedirect(route('guides.edit', $guide));
        $this->assertSame('draft', $guide->status);

        $this->actingAs($author)
            ->putJson(route('guides.update', $guide), [
                'title' => 'Safe Hunt Basics',
                'summary' => 'A useful summary that is long enough for a draft.',
                'content_blocks' => [
                    ['id' => 'intro', 'type' => 'paragraph', 'text' => 'Real saved content'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('guide_revisions', [
            'guide_id' => $guide->id,
            'title' => 'Safe Hunt Basics',
        ]);

        $this->get(route('guides.show', $guide))->assertNotFound();
    }

    public function test_other_user_cannot_view_or_edit_a_draft(): void
    {
        $guide = $this->draft(User::factory()->create());
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('guides.preview', $guide))->assertForbidden();
        $this->actingAs($other)->get(route('guides.edit', $guide))->assertForbidden();
        $this->actingAs($other)->putJson(route('guides.update', $guide), ['title' => 'Nope'])->assertForbidden();
    }

    public function test_unknown_content_block_type_is_rejected(): void
    {
        $author = User::factory()->create();
        $guide = $this->draft($author);

        $this->actingAs($author)
            ->putJson(route('guides.update', $guide), [
                'content_blocks' => [['type' => 'unsafe_embed', 'html' => '<script>alert(1)</script>']],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content_blocks.0.type');
    }

    public function test_invalid_guide_upload_is_rejected_server_side(): void
    {
        $author = User::factory()->create();
        $guide = $this->draft($author);

        $this->actingAs($author)
            ->postJson(route('guides.media.store', $guide), [
                'kind' => 'content',
                'image' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('image');
    }

    public function test_incomplete_guide_cannot_be_submitted(): void
    {
        $author = User::factory()->create();
        $guide = $this->draft($author);

        $this->actingAs($author)
            ->postJson(route('guides.submit', $guide))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'summary', 'category_id', 'cover_media_id', 'content_blocks']);
    }

    public function test_submission_is_private_and_visible_in_admin_queue(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->completeDraft($author);

        $this->actingAs($author)
            ->postJson(route('guides.submit', $guide))
            ->assertOk();

        $guide->refresh();
        $this->assertSame('pending_review', $guide->status);
        $this->assertSame('pending_review', $guide->workingRevision->status);
        $this->get(route('guides.show', $guide))->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.guides.index'))
            ->assertOk()
            ->assertSee($guide->workingRevision->title);
    }

    public function test_pending_submission_can_be_withdrawn_by_its_author(): void
    {
        $author = User::factory()->create();
        $guide = $this->submittedGuide($author);

        $this->actingAs($author)
            ->post(route('guides.withdraw', $guide))
            ->assertSessionHasNoErrors();

        $guide->refresh();
        $this->assertSame('draft', $guide->status);
        $this->assertSame('draft', $guide->workingRevision->status);
        $this->assertDatabaseHas('guide_moderation_events', [
            'guide_id' => $guide->id,
            'action' => 'withdrawn',
        ]);
    }

    public function test_non_admin_cannot_moderate_and_admin_approval_publishes_exact_revision(): void
    {
        $author = User::factory()->create();
        $other = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->submittedGuide($author);
        $revision = $guide->workingRevision;

        $this->actingAs($other)
            ->post(route('admin.guides.moderate', $revision), ['action' => 'approve'])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.guides.moderate', $revision), ['action' => 'approve'])
            ->assertRedirect(route('admin.guides.index'));

        $guide->refresh();
        $this->assertSame('published', $guide->status);
        $this->assertSame($revision->id, $guide->current_published_revision_id);
        $this->assertNull($guide->working_revision_id);
        $this->assertDatabaseHas('guide_moderation_events', [
            'guide_id' => $guide->id,
            'revision_id' => $revision->id,
            'action' => 'approved',
        ]);
        $this->assertDatabaseHas('guide_reputation_entries', [
            'event_key' => "guide:{$guide->id}:published",
            'points' => 10,
        ]);
        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee($revision->title)
            ->assertSee(asset('assets/vikinger/fonts/phosphor/regular/style.css'), false);
    }

    public function test_changes_and_rejection_require_a_reason(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->submittedGuide(User::factory()->create());

        foreach (['changes', 'reject'] as $action) {
            $this->actingAs($admin)
                ->post(route('admin.guides.moderate', $guide->workingRevision), ['action' => $action])
                ->assertSessionHasErrors('reason');
        }
    }

    public function test_archive_requires_a_reason_and_reputation_is_reversibly_corrected(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->publishedGuide($author, $admin);

        $this->actingAs($admin)
            ->post(route('admin.guides.archive', $guide), ['reason' => 'short'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)
            ->post(route('admin.guides.archive', $guide), [
                'reason' => 'A documented moderation rule was violated.',
            ])
            ->assertSessionHasNoErrors();

        $guide->refresh();
        $this->assertSame('archived', $guide->status);
        $this->assertNotNull($guide->archived_at);
        $this->assertNotNull(GuideReputationEntry::query()
            ->where('event_key', "guide:{$guide->id}:published")
            ->value('reversed_at'));

        $this->actingAs($admin)
            ->post(route('admin.guides.restore', $guide))
            ->assertSessionHasNoErrors();

        $this->assertSame('published', $guide->fresh()->status);
        $this->assertNull(GuideReputationEntry::query()
            ->where('event_key', "guide:{$guide->id}:published")
            ->value('reversed_at'));
    }

    public function test_admin_can_feature_a_published_guide(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->publishedGuide(User::factory()->create(), $admin);

        $this->actingAs($admin)
            ->post(route('admin.guides.feature', $guide), ['is_featured' => true])
            ->assertSessionHasNoErrors();

        $this->assertTrue($guide->fresh()->is_featured);
        $this->assertDatabaseHas('guide_moderation_events', [
            'guide_id' => $guide->id,
            'action' => 'featured',
        ]);
    }

    public function test_changes_requested_can_be_edited_and_resubmitted(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->submittedGuide($author);

        $this->actingAs($admin)->post(route('admin.guides.moderate', $guide->workingRevision), [
            'action' => 'changes',
            'reason' => 'Please clarify the second tactical step.',
        ]);

        $guide->refresh();
        $this->assertSame('changes_requested', $guide->status);
        $this->assertSame('Please clarify the second tactical step.', $guide->workingRevision->moderation_reason);

        $this->actingAs($author)
            ->putJson(route('guides.update', $guide), [
                'summary' => 'An updated and sufficiently detailed summary for review.',
            ])
            ->assertOk();
        $this->actingAs($author)->postJson(route('guides.submit', $guide))->assertOk();
        $this->assertSame('pending_review', $guide->fresh()->status);
    }

    public function test_published_revision_remains_public_while_update_is_reviewed_or_rejected(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->publishedGuide($author, $admin);
        $publishedRevisionId = $guide->current_published_revision_id;
        $publishedTitle = $guide->publishedRevision->title;

        $this->actingAs($author)->get(route('guides.edit', $guide))->assertOk();
        $guide->refresh();
        $this->assertNotSame($publishedRevisionId, $guide->working_revision_id);

        $this->actingAs($author)
            ->putJson(route('guides.update', $guide), ['title' => 'Unreviewed replacement title'])
            ->assertOk();
        $this->actingAs($author)->postJson(route('guides.submit', $guide))->assertOk();

        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee($publishedTitle)
            ->assertDontSee('Unreviewed replacement title');

        $this->actingAs($admin)->post(route('admin.guides.moderate', $guide->fresh()->workingRevision), [
            'action' => 'reject',
            'reason' => 'This revision is not ready for publication.',
        ]);

        $guide->refresh();
        $this->assertSame($publishedRevisionId, $guide->current_published_revision_id);
        $this->get(route('guides.show', $guide))
            ->assertOk()
            ->assertSee($publishedTitle);
    }

    public function test_only_published_guides_can_receive_comments_and_replies_have_one_level(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $draft = $this->completeDraft($author);

        $this->actingAs($commenter)
            ->postJson(route('guides.comments.store', $draft), ['body' => 'No public comment'])
            ->assertNotFound();

        $guide = $this->publishedGuide($author, $admin);
        $rootResponse = $this->actingAs($commenter)
            ->postJson(route('guides.comments.store', $guide), ['body' => 'Useful guide'])
            ->assertCreated()
            ->assertJsonPath('comment.actions.can_reply', true)
            ->assertJsonPath('comment.actions.can_edit', true)
            ->assertJsonPath('comment.actions.can_delete', true)
            ->assertJsonPath('comment.actions.can_report', false);
        $root = $rootResponse->json('comment.id');

        $replyResponse = $this->actingAs($author)
            ->postJson(route('guides.comments.store', $guide), ['body' => 'Thanks', 'parent_id' => $root])
            ->assertCreated()
            ->assertJsonPath('comment.actions.can_reply', false)
            ->assertJsonPath('comment.actions.can_edit', true)
            ->assertJsonPath('comment.actions.can_delete', true)
            ->assertJsonPath('comment.actions.can_report', false);
        $reply = $replyResponse->json('comment.id');

        $this->actingAs($commenter)
            ->postJson(route('guides.comments.store', $guide), ['body' => 'Too deep', 'parent_id' => $reply])
            ->assertNotFound();

        $rootComment = \App\Models\GuideComment::query()->findOrFail($root);
        $this->assertTrue($rootComment->canDelete($author));
        $this->actingAs($author)
            ->deleteJson(route('guides.comments.destroy', $rootComment))
            ->assertOk();
    }

    public function test_comment_authorization_and_guide_notification_setting_are_respected(): void
    {
        $author = User::factory()->create();
        $commenter = User::factory()->create();
        $other = User::factory()->create();
        $guide = $this->publishedGuide($author, User::factory()->create(['is_admin' => true]));
        $author->notificationSettings()->updateOrCreate([], ['guides' => false]);

        $commentId = $this->actingAs($commenter)
            ->postJson(route('guides.comments.store', $guide), ['body' => 'A valid comment'])
            ->assertCreated()
            ->json('comment.id');

        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $author->id,
            'type' => 'guide_comment_new',
        ]);

        $comment = \App\Models\GuideComment::query()->findOrFail($commentId);
        $this->actingAs($other)
            ->patchJson(route('guides.comments.update', $comment), ['body' => 'Unauthorized edit'])
            ->assertForbidden();
        $this->actingAs($other)
            ->deleteJson(route('guides.comments.destroy', $comment))
            ->assertForbidden();
    }

    public function test_blocking_prevents_comments_helpful_votes_bookmarks_and_results(): void
    {
        $author = User::factory()->create();
        $viewer = User::factory()->create();
        $guide = $this->publishedGuide($author, User::factory()->create(['is_admin' => true]));
        UserBlock::query()->create(['user_id' => $viewer->id, 'blocked_user_id' => $author->id]);

        $this->actingAs($viewer)->get(route('guides.show', $guide))->assertNotFound();
        $this->actingAs($viewer)->postJson(route('guides.comments.store', $guide), ['body' => 'Nope'])->assertNotFound();
        $this->actingAs($viewer)->postJson(route('guides.helpful.toggle', $guide))->assertNotFound();
        $this->actingAs($viewer)->postJson(route('guides.bookmark.toggle', $guide))->assertNotFound();
        $this->actingAs($viewer)->get(route('guides.index', ['q' => $guide->publishedRevision->title]))->assertDontSee($guide->publishedRevision->title);
    }

    public function test_helpful_toggle_is_unique_updates_reputation_and_disallows_own_vote(): void
    {
        $author = User::factory()->create();
        $voter = User::factory()->create();
        $guide = $this->publishedGuide($author, User::factory()->create(['is_admin' => true]));

        $this->actingAs($author)->postJson(route('guides.helpful.toggle', $guide))->assertUnprocessable();

        $this->actingAs($voter)
            ->postJson(route('guides.helpful.toggle', $guide))
            ->assertOk()
            ->assertJsonPath('helpful', true)
            ->assertJsonPath('count', 1);

        $this->assertDatabaseCount('guide_helpful_votes', 1);
        $this->assertDatabaseHas('guide_reputation_entries', [
            'event_key' => "guide:{$guide->id}:helpful:{$voter->id}",
            'points' => 1,
            'reversed_at' => null,
        ]);

        $this->actingAs($voter)
            ->postJson(route('guides.helpful.toggle', $guide))
            ->assertOk()
            ->assertJsonPath('helpful', false)
            ->assertJsonPath('count', 0);

        $this->assertDatabaseCount('guide_helpful_votes', 0);
        $this->assertNotNull(GuideReputationEntry::query()
            ->where('event_key', "guide:{$guide->id}:helpful:{$voter->id}")
            ->value('reversed_at'));
    }

    public function test_bookmark_toggle_is_unique_and_only_works_for_published_guides(): void
    {
        $user = User::factory()->create();
        $draft = $this->completeDraft(User::factory()->create());

        $this->actingAs($user)->postJson(route('guides.bookmark.toggle', $draft))->assertNotFound();

        $guide = $this->publishedGuide(User::factory()->create(), User::factory()->create(['is_admin' => true]));
        $this->actingAs($user)->postJson(route('guides.bookmark.toggle', $guide))
            ->assertOk()->assertJsonPath('saved', true)->assertJsonPath('count', 1);
        $this->assertDatabaseCount('guide_bookmarks', 1);

        $this->actingAs($user)->postJson(route('guides.bookmark.toggle', $guide))
            ->assertOk()->assertJsonPath('saved', false)->assertJsonPath('count', 0);
        $this->assertDatabaseCount('guide_bookmarks', 0);
    }

    public function test_search_uses_only_active_published_revision(): void
    {
        $viewer = User::factory()->create();
        $author = User::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $guide = $this->publishedGuide($author, $admin);
        $publishedTitle = $guide->publishedRevision->title;

        $this->actingAs($author)->get(route('guides.edit', $guide));
        $this->actingAs($author)->putJson(route('guides.update', $guide), ['title' => 'Secret future title']);

        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => $publishedTitle, 'type' => 'guides']))
            ->assertOk()
            ->assertSee($publishedTitle);
        $this->actingAs($viewer)
            ->get(route('search.index', ['q' => 'Secret future title', 'type' => 'guides']))
            ->assertOk()
            ->assertDontSee('Secret future title');
    }

    public function test_guide_overview_widgets_use_real_published_data(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $firstAuthor = User::factory()->create(['name' => 'Real Guide Author']);
        $secondAuthor = User::factory()->create(['name' => 'Second Real Author']);
        $viewer = User::factory()->create();

        $featured = $this->publishedGuide($firstAuthor, $admin);
        $featured->update([
            'is_featured' => true,
            'helpful_count' => 7,
            'comments_count' => 2,
        ]);

        $secondGuide = $this->publishedGuide($secondAuthor, $admin);
        $secondGuide->update(['helpful_count' => 3]);

        $this->actingAs($viewer)
            ->postJson(route('guides.bookmark.toggle', $featured))
            ->assertOk()
            ->assertJsonPath('saved', true);

        $response = $this->actingAs($viewer)->get(route('guides.index'));

        $response
            ->assertOk()
            ->assertSee($featured->publishedRevision->title)
            ->assertSee($secondGuide->publishedRevision->title)
            ->assertSee('Real Guide Author')
            ->assertDontSee('Erica Wyatt')
            ->assertViewHas('publishedCount', 2)
            ->assertViewHas('authorCount', 2)
            ->assertViewHas('helpfulCount', 10)
            ->assertViewHas('featuredGuide', fn (?Guide $guide) => $guide?->is($featured))
            ->assertViewHas('categories', fn ($categories) => (int) $categories->sum('published_guides_count') === 2)
            ->assertViewHas('topAuthors', fn ($authors) => $authors->first()?->is($firstAuthor))
            ->assertViewHas('bookmarkedGuideIds', fn (array $ids) => in_array($featured->id, $ids, true));
    }

    public function test_guide_detail_widgets_use_real_content_and_related_guides(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $author = User::factory()->create(['name' => 'Real Detail Author']);
        $relatedAuthor = User::factory()->create(['name' => 'Related Guide Author']);
        $viewer = User::factory()->create();

        $guide = $this->publishedGuide($author, $admin);
        $guide->update(['helpful_count' => 9]);

        $relatedGuide = $this->publishedGuide($relatedAuthor, $admin);
        $relatedGuide->update(['helpful_count' => 4]);

        $response = $this->actingAs($viewer)->get(route('guides.show', $guide));

        $response
            ->assertOk()
            ->assertSee($guide->publishedRevision->title)
            ->assertSee($relatedGuide->publishedRevision->title)
            ->assertSee('Real Detail Author')
            ->assertSee('Aufrufe · geplant')
            ->assertDontSee('Erica Wyatt')
            ->assertViewHas('authorPublishedGuideCount', 1)
            ->assertViewHas('relatedGuides', fn ($guides) => $guides->contains(fn (Guide $item) => $item->is($relatedGuide)));
    }

    public function test_guide_editor_uses_saved_data_and_marks_unavailable_blocks_as_planned(): void
    {
        $author = User::factory()->create(['name' => 'Real Editor Author']);
        $guide = $this->draft($author);

        $this->actingAs($author)
            ->putJson(route('guides.update', $guide), [
                'title' => 'Real Saved Editor Guide',
                'summary' => 'This summary is stored in the working guide revision.',
                'content_blocks' => [
                    ['id' => 'intro', 'type' => 'paragraph', 'text' => 'Real saved editor paragraph'],
                ],
            ])
            ->assertOk();

        $this->actingAs($author)
            ->get(route('guides.edit', $guide))
            ->assertOk()
            ->assertSee('Real Saved Editor Guide')
            ->assertSee('Real Editor Author')
            ->assertSee('Moment')
            ->assertSee('Geplant')
            ->assertDontSee('Budget-Loadouts, die wirklich funktionieren')
            ->assertDontSee('Erica Wyatt');
    }

    private function draft(User $author): Guide
    {
        return app(GuideWorkflowService::class)->create($author);
    }

    private function completeDraft(User $author): Guide
    {
        $guide = $this->draft($author);
        $revision = $guide->workingRevision;
        $categoryId = \App\Models\GuideCategory::query()->value('id');
        $media = GuideMedia::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $revision->id,
            'uploaded_by' => $author->id,
            'kind' => 'cover',
            'disk' => 'local',
            'path' => "tests/guides/{$guide->id}/cover.jpg",
            'original_name' => 'cover.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => 1024,
            'width' => 1280,
            'height' => 720,
        ]);

        $revision->update([
            'title' => 'Complete Community Guide '.$guide->id,
            'summary' => 'A complete and useful community guide ready for a real moderation review.',
            'category_id' => $categoryId,
            'cover_media_id' => $media->id,
            'language' => 'en',
            'difficulty' => 'beginner',
            'platform' => 'all',
            'content_blocks' => [
                ['id' => 'intro', 'type' => 'heading', 'level' => 2, 'text' => 'Introduction'],
                ['id' => 'body', 'type' => 'paragraph', 'text' => 'This is complete structured guide content.'],
            ],
        ]);

        return $guide->fresh(['workingRevision']);
    }

    private function submittedGuide(User $author): Guide
    {
        $guide = $this->completeDraft($author);
        app(GuideWorkflowService::class)->submit($guide, $author);

        return $guide->fresh(['workingRevision']);
    }

    private function publishedGuide(User $author, User $admin): Guide
    {
        $guide = $this->submittedGuide($author);
        app(GuideWorkflowService::class)->moderate($guide->workingRevision, $admin, 'approve');

        return $guide->fresh(['publishedRevision']);
    }
}
