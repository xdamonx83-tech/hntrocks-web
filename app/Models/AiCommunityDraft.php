<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCommunityDraft extends Model
{
    protected $fillable = ['character_id', 'body', 'body_hash', 'status', 'post_kind', 'editorial_angle', 'style_key', 'model', 'input_tokens', 'output_tokens', 'estimated_cost_microusd', 'reviewed_by', 'reviewed_at', 'published_feed_post_id'];

    protected function casts(): array { return ['reviewed_at' => 'datetime']; }
    public function character(): BelongsTo { return $this->belongsTo(AiCommunityCharacter::class, 'character_id'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function feedPost(): BelongsTo { return $this->belongsTo(FeedPost::class, 'published_feed_post_id'); }

    public static function hashBody(string $body): string
    {
        return hash('sha256', mb_strtolower(preg_replace('/\s+/u', ' ', trim($body))));
    }
}
