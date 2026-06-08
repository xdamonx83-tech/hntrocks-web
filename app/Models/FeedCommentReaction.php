<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedCommentReaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_comment_id',
        'user_id',
        'type',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(FeedComment::class, 'feed_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
