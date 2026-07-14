<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Moment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'media_asset_id',
        'cover_media_asset_id',
        'caption',
        'description',
        'visibility',
        'status',
        'processing_status',
        'trim_start_seconds',
        'trim_end_seconds',
        'duration_seconds',
        'views_count',
        'likes_count',
        'comments_count',
        'bookmarks_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'trim_start_seconds' => 'integer',
            'trim_end_seconds' => 'integer',
            'duration_seconds' => 'integer',
            'views_count' => 'integer',
            'likes_count' => 'integer',
            'comments_count' => 'integer',
            'bookmarks_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Moment $moment): void {
            if (app()->runningInConsole() || ! app()->bound('request')) {
                return;
            }

            $request = request();
            $aspectRatio = trim((string) $request->input('aspect_ratio', ''));
            $publishToFeed = $request->boolean('publish_to_feed');

            if (! in_array($aspectRatio, ['9:16', '16:9'], true) && ! $publishToFeed) {
                return;
            }

            $moment->loadMissing('media');
            $asset = $moment->media;

            if (! $asset) {
                return;
            }

            if (in_array($aspectRatio, ['9:16', '16:9'], true)) {
                $metadata = is_array($asset->metadata) ? $asset->metadata : [];
                $metadata['aspect_ratio'] = $aspectRatio;
                $asset->forceFill(['metadata' => $metadata])->saveQuietly();
            }

            if (! $publishToFeed) {
                return;
            }

            DB::transaction(function () use ($moment, $asset): void {
                $bodyParts = array_values(array_filter([
                    trim((string) $moment->caption),
                    trim((string) $moment->description),
                ], static fn (string $value): bool => $value !== ''));

                $body = trim(implode("\n\n", $bodyParts));
                if ($body === '') {
                    $body = 'HNT Moment';
                }

                $body .= "\n\n".url('/moments/r/'.$moment->getKey());

                $post = FeedPost::create([
                    'user_id' => $moment->user_id,
                    'body' => $body,
                    'visibility' => $moment->visibility === 'private' ? 'private' : 'public',
                    'status' => 'published',
                ]);

                FeedPostMedia::create([
                    'feed_post_id' => $post->id,
                    'user_id' => $moment->user_id,
                    'media_asset_id' => $asset->id,
                    'disk' => $asset->disk,
                    'path' => $asset->path,
                    'mime_type' => $asset->mime_type,
                    'original_name' => $asset->original_name,
                    'size_bytes' => $asset->size_bytes,
                    'sort_order' => 0,
                ]);
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'cover_media_asset_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(MomentComment::class)->latest();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MomentReaction::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(MomentBookmark::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereIn('visibility', ['public', 'registered'])
            ->where(function (Builder $subQuery): void {
                $subQuery->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->reactions()->where('user_id', $user->id)->where('type', 'like')->exists();
    }

    public function isBookmarkedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    public function canBeManagedBy(?User $user): bool
    {
        return $user !== null && ((int) $this->user_id === (int) $user->id || $user->isAdmin());
    }

    public function mediaUrl(): string
    {
        return $this->media?->url() ?? '';
    }

    public function coverUrl(): string
    {
        return $this->cover?->thumbnailUrl() ?: $this->media?->thumbnailUrl() ?: asset('assets/vikinger/img/default-cover.svg');
    }
}
