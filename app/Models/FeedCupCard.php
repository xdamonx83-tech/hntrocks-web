<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedCupCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_post_id',
        'cup_id',
        'published_by_user_id',
    ];

    public function feedPost(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class)->withTrashed();
    }

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class)->withTrashed();
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }
}
