<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedPostPollOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_post_poll_id',
        'body',
        'sort_order',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(FeedPostPoll::class, 'feed_post_poll_id');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeedPostPollVote::class, 'feed_post_poll_option_id');
    }
}
