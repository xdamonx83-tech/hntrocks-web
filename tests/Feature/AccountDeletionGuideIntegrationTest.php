<?php

namespace Tests\Feature;

use App\Models\AccountDeletionRequest;
use App\Models\Guide;
use App\Models\GuideBookmark;
use App\Models\GuideComment;
use App\Models\GuideHelpfulVote;
use App\Models\GuideMedia;
use App\Models\GuideModerationEvent;
use App\Models\GuideReputationEntry;
use App\Models\GuideRevision;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AccountDeletionGuideIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_owned_guide_revisions_media_and_related_rows_can_be_deleted(): void
    {
        Storage::fake('local');
        $author = User::factory()->create();
        $other = User::factory()->create();
        [$guide, $revision] = $this->publishedGuide($author, 'owned-guide');
        $path = 'guides/tests/owned-guide.webp';
        Storage::disk('local')->put($path, 'guide-image-binary');

        $asset = $this->mediaAsset($author, $revision, $path);
        $media = GuideMedia::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $revision->id,
            'uploaded_by' => $author->id,
            'media_asset_id' => $asset->id,
            'kind' => 'cover',
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'owned-guide.webp',
            'mime_type' => 'image/webp',
            'size_bytes' => 18,
            'width' => 1280,
            'height' => 720,
        ]);
        $revision->update(['cover_media_id' => $media->id]);
        GuideComment::query()->create(['guide_id' => $guide->id, 'user_id' => $other->id, 'body' => 'Comment']);
        GuideHelpfulVote::query()->create(['guide_id' => $guide->id, 'user_id' => $other->id]);
        GuideBookmark::query()->create(['guide_id' => $guide->id, 'user_id' => $other->id]);
        GuideModerationEvent::query()->create([
            'guide_id' => $guide->id,
            'revision_id' => $revision->id,
            'actor_id' => $author->id,
            'action' => 'submitted',
            'from_status' => 'draft',
            'to_status' => 'pending_review',
        ]);
        GuideReputationEntry::query()->create([
            'user_id' => $author->id,
            'guide_id' => $guide->id,
            'event_type' => 'guide_published',
            'event_key' => "guide:{$guide->id}:published",
            'points' => 10,
        ]);

        $request = $this->deletionRequest($author);
        $counts = app(AccountDeletionService::class)->countsFor($author);
        $this->assertSame(1, $counts['guides']);
        $this->assertSame(1, $counts['guide_revisions']);
        $this->assertSame(1, $counts['guide_media']);

        $result = app(AccountDeletionService::class)->process($request, dryRun: false);

        $this->assertDatabaseMissing('users', ['id' => $author->id]);
        $this->assertDatabaseMissing('guides', ['id' => $guide->id]);
        $this->assertDatabaseMissing('guide_revisions', ['id' => $revision->id]);
        $this->assertDatabaseMissing('guide_media', ['id' => $media->id]);
        $this->assertDatabaseMissing('media_assets', ['id' => $asset->id]);
        $this->assertDatabaseMissing('guide_moderation_events', ['guide_id' => $guide->id]);
        $this->assertDatabaseMissing('guide_reputation_entries', ['guide_id' => $guide->id]);
        Storage::disk('local')->assertMissing($path);
        $this->assertSame(1, $result['deleted_files']);
        $this->assertSame(0, $result['failed_files']);
    }

    public function test_deleting_guide_interactions_recalculates_foreign_counts_and_removes_helpful_reputation(): void
    {
        $author = User::factory()->create();
        $deletingUser = User::factory()->create();
        $remainingUser = User::factory()->create();
        [$guide] = $this->publishedGuide($author, 'foreign-guide');

        foreach ([$deletingUser, $remainingUser] as $participant) {
            GuideComment::query()->create([
                'guide_id' => $guide->id,
                'user_id' => $participant->id,
                'body' => 'Comment by '.$participant->id,
            ]);
            GuideHelpfulVote::query()->create(['guide_id' => $guide->id, 'user_id' => $participant->id]);
            GuideBookmark::query()->create(['guide_id' => $guide->id, 'user_id' => $participant->id]);
            GuideReputationEntry::query()->create([
                'user_id' => $author->id,
                'guide_id' => $guide->id,
                'event_type' => 'helpful_added',
                'event_key' => "guide:{$guide->id}:helpful:{$participant->id}",
                'points' => 1,
            ]);
        }
        $guide->update(['comments_count' => 2, 'helpful_count' => 2, 'bookmarks_count' => 2]);

        app(AccountDeletionService::class)->process(
            $this->deletionRequest($deletingUser),
            dryRun: false
        );

        $guide->refresh();
        $this->assertSame(1, $guide->comments_count);
        $this->assertSame(1, $guide->helpful_count);
        $this->assertSame(1, $guide->bookmarks_count);
        $this->assertDatabaseMissing('guide_comments', ['user_id' => $deletingUser->id]);
        $this->assertDatabaseMissing('guide_helpful_votes', ['user_id' => $deletingUser->id]);
        $this->assertDatabaseMissing('guide_bookmarks', ['user_id' => $deletingUser->id]);
        $this->assertDatabaseMissing('guide_reputation_entries', [
            'event_key' => "guide:{$guide->id}:helpful:{$deletingUser->id}",
        ]);
        $this->assertDatabaseHas('guide_reputation_entries', [
            'event_key' => "guide:{$guide->id}:helpful:{$remainingUser->id}",
        ]);
    }

    /** @return array{Guide,GuideRevision} */
    private function publishedGuide(User $author, string $slug): array
    {
        $guide = Guide::query()->create([
            'author_id' => $author->id,
            'slug' => $slug,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $revision = GuideRevision::query()->create([
            'guide_id' => $guide->id,
            'version' => 1,
            'author_id' => $author->id,
            'title' => 'Guide '.$slug,
            'summary' => 'A complete summary for '.$slug,
            'content_blocks' => [['id' => 'intro', 'type' => 'paragraph', 'text' => 'Content']],
            'status' => 'published',
        ]);
        $guide->update(['current_published_revision_id' => $revision->id]);

        return [$guide->fresh(), $revision];
    }

    private function mediaAsset(User $user, GuideRevision $revision, string $path): MediaAsset
    {
        return MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'attachable_type' => $revision->getMorphClass(),
            'attachable_id' => $revision->id,
            'context' => 'guides',
            'disk' => 'local',
            'path' => $path,
            'type' => 'image',
            'mime_type' => 'image/webp',
            'original_name' => 'guide.webp',
            'extension' => 'webp',
            'size_bytes' => 18,
            'visibility' => 'private',
            'status' => 'ready',
        ]);
    }

    private function deletionRequest(User $user): AccountDeletionRequest
    {
        return AccountDeletionRequest::query()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'requested_at' => now()->subDays(8),
            'scheduled_for' => now()->subMinute(),
        ]);
    }
}
