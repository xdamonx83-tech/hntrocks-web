<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedPostPollVote extends Model
{
    use HasFactory;
    use HidesBlockedUsers;

    protected $fillable = [
        'feed_post_poll_id',
        'feed_post_poll_option_id',
        'user_id',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(FeedPostPoll::class, 'feed_post_poll_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(FeedPostPollOption::class, 'feed_post_poll_option_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
