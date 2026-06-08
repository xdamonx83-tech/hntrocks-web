<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HuntNewsItem extends Model
{
    use HasFactory;

    public const STATUS_DISCOVERED = 'discovered';
    public const STATUS_POSTED = 'posted';
    public const STATUS_SKIPPED = 'skipped';
    public const STATUS_ERROR = 'error';

    protected $fillable = [
        'source_hash',
        'source_url',
        'source_slug',
        'source_domain',
        'title',
        'excerpt',
        'category',
        'source_published_at',
        'discovered_at',
        'auto_publish_eligible',
        'status',
        'feed_post_id',
        'outbound_link_id',
        'posted_at',
        'posted_by_user_id',
        'error_message',
        'raw_meta',
    ];

    protected function casts(): array
    {
        return [
            'source_published_at' => 'datetime',
            'discovered_at' => 'datetime',
            'auto_publish_eligible' => 'boolean',
            'posted_at' => 'datetime',
            'raw_meta' => 'array',
        ];
    }

    public function feedPost(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function outboundLink(): BelongsTo
    {
        return $this->belongsTo(ApprovedOutboundLink::class, 'outbound_link_id');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by_user_id');
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED && $this->feed_post_id !== null;
    }

    public function canBePublished(): bool
    {
        return ! $this->isPosted() && in_array($this->status, [self::STATUS_DISCOVERED, self::STATUS_ERROR, self::STATUS_SKIPPED], true);
    }

    public function displayTitle(): string
    {
        return trim((string) $this->title) !== '' ? (string) $this->title : Str::limit($this->source_url, 80);
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DISCOVERED => 'Entdeckt',
            self::STATUS_POSTED => 'Gepostet',
            self::STATUS_SKIPPED => 'Übersprungen',
            self::STATUS_ERROR => 'Fehler',
        ];
    }
}
