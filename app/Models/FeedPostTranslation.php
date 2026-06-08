<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedPostTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_post_id',
        'locale',
        'source_locale',
        'provider',
        'translated_body',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }
}
