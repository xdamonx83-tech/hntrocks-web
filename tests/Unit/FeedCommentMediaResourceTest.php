<?php

namespace Tests\Unit;

use App\Http\Resources\Api\FeedCommentMediaResource;
use App\Models\FeedCommentMedia;
use App\Models\MediaAsset;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class FeedCommentMediaResourceTest extends TestCase
{
    public function test_comment_media_resource_matches_feed_media_payload_shape(): void
    {
        $asset = new class extends MediaAsset {
            public function url(): string
            {
                return 'https://cdn.example.test/feed/comment.jpg';
            }

            public function thumbnailUrl(): string
            {
                return 'https://cdn.example.test/feed/comment-thumb.jpg';
            }
        };
        $asset->forceFill([
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'status' => 'ready',
        ]);

        $media = new FeedCommentMedia([
            'mime_type' => 'image/jpeg',
            'sort_order' => 2,
        ]);
        $media->id = 42;
        $media->setRelation('mediaAsset', $asset);

        $payload = (new FeedCommentMediaResource($media))->toArray(Request::create('/'));

        $this->assertSame([
            'id' => 42,
            'type' => 'image',
            'mime_type' => 'image/jpeg',
            'url' => 'https://cdn.example.test/feed/comment.jpg',
            'thumbnail_url' => 'https://cdn.example.test/feed/comment-thumb.jpg',
            'status' => 'ready',
            'sort_order' => 2,
        ], $payload);
    }
}
