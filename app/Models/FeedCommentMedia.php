<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FeedCommentMedia extends Model
{
    use HasFactory;

    protected $table = 'feed_comment_media';

    protected $fillable = [
        'feed_comment_id',
        'user_id',
        'media_asset_id',
        'disk',
        'path',
        'mime_type',
        'original_name',
        'size_bytes',
        'sort_order',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(FeedComment::class, 'feed_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    public function url(): string
    {
        if ($this->mediaAsset) {
            return $this->mediaAsset->url();
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }
}
