<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedPostPoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_post_id',
        'question',
        'allows_multiple',
        'closed_at',
    ];

    protected $casts = [
        'allows_multiple' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(FeedPostPollOption::class)->orderBy('sort_order');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(FeedPostPollVote::class, 'feed_post_poll_id');
    }

    public function isClosed(): bool
    {
        return $this->closed_at !== null && $this->closed_at->isPast();
    }
}
