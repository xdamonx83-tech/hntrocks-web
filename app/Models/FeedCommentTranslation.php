<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedCommentTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'feed_comment_id',
        'locale',
        'source_locale',
        'provider',
        'translated_body',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(FeedComment::class, 'feed_comment_id');
    }
}
