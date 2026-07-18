<?php

namespace Tests\Feature;

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
use App\Services\UserDataExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class UserDataExportGuideTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_contains_complete_guide_metadata_without_binary_file_contents(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $moderator = User::factory()->create(['is_admin' => true]);
        $foreignAuthor = User::factory()->create();
        [$ownedGuide, $ownedRevision] = $this->guide($user, 'export-owned');
        [$foreignGuide] = $this->guide($foreignAuthor, 'export-foreign');
        $path = 'guides/tests/export.webp';
        $binaryContents = 'binary-guide-contents-must-not-be-exported';
        Storage::disk('local')->put($path, $binaryContents);

        $asset = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'attachable_type' => $ownedRevision->getMorphClass(),
            'attachable_id' => $ownedRevision->id,
            'context' => 'guides',
            'disk' => 'local',
            'path' => $path,
            'type' => 'image',
            'mime_type' => 'image/webp',
            'original_name' => 'export.webp',
            'extension' => 'webp',
            'size_bytes' => strlen($binaryContents),
            'visibility' => 'private',
            'status' => 'ready',
            'metadata' => ['image_optimization' => ['status' => 'optimized']],
        ]);
        GuideMedia::query()->create([
            'guide_id' => $ownedGuide->id,
            'revision_id' => $ownedRevision->id,
            'uploaded_by' => $user->id,
            'media_asset_id' => $asset->id,
            'kind' => 'content',
            'disk' => 'local',
            'path' => $path,
            'original_name' => 'export.webp',
            'mime_type' => 'image/webp',
            'size_bytes' => strlen($binaryContents),
        ]);
        GuideComment::query()->create([
            'guide_id' => $foreignGuide->id,
            'user_id' => $user->id,
            'body' => 'My exported comment',
        ]);
        GuideHelpfulVote::query()->create(['guide_id' => $foreignGuide->id, 'user_id' => $user->id]);
        GuideBookmark::query()->create(['guide_id' => $foreignGuide->id, 'user_id' => $user->id]);
        GuideReputationEntry::query()->create([
            'user_id' => $user->id,
            'guide_id' => $ownedGuide->id,
            'event_type' => 'guide_published',
            'event_key' => "guide:{$ownedGuide->id}:published",
            'points' => 10,
        ]);
        GuideReputationEntry::query()->create([
            'user_id' => $foreignAuthor->id,
            'guide_id' => $foreignGuide->id,
            'event_type' => 'helpful_added',
            'event_key' => "guide:{$foreignGuide->id}:helpful:{$user->id}",
            'points' => 1,
        ]);
        GuideModerationEvent::query()->create([
            'guide_id' => $ownedGuide->id,
            'revision_id' => $ownedRevision->id,
            'actor_id' => $moderator->id,
            'action' => 'changes_requested',
            'from_status' => 'pending_review',
            'to_status' => 'changes_requested',
            'reason' => 'Please add verifiable source details.',
        ]);

        $export = app(UserDataExportService::class)->build($user);
        $json = json_encode($export, JSON_THROW_ON_ERROR);

        $this->assertSame(3, $export['meta']['export_version']);
        $this->assertFalse($export['meta']['contains_binary_files']);
        $this->assertSame($ownedGuide->id, $export['guides']['owned_guides'][0]['id']);
        $this->assertSame($ownedRevision->id, $export['guides']['revisions'][0]['id']);
        $this->assertSame($path, $export['guides']['media_metadata'][0]['path']);
        $this->assertSame('My exported comment', $export['guides']['comments_written'][0]['body']);
        $this->assertSame($foreignGuide->id, $export['guides']['helpful_votes'][0]['guide_id']);
        $this->assertSame($foreignGuide->id, $export['guides']['bookmarks'][0]['guide_id']);
        $this->assertSame(10, $export['guides']['reputation_earned'][0]['points']);
        $this->assertSame(
            "guide:{$foreignGuide->id}:helpful:{$user->id}",
            $export['guides']['reputation_triggered_by_helpful_votes'][0]['event_key']
        );
        $this->assertSame(
            'Please add verifiable source details.',
            $export['guides']['moderation_events'][0]['reason']
        );
        $this->assertStringNotContainsString($binaryContents, $json);
    }

    /** @return array{Guide,GuideRevision} */
    private function guide(User $author, string $slug): array
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
            'summary' => 'A complete export summary for '.$slug,
            'content_blocks' => [['id' => 'body', 'type' => 'paragraph', 'text' => 'Export content']],
            'status' => 'published',
            'moderation_reason' => 'Revision reason retained for export.',
        ]);
        $guide->update(['current_published_revision_id' => $revision->id]);

        return [$guide->fresh(), $revision];
    }
}
