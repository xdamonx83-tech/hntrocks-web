<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'feed_post_id',
        'user_id',
        'parent_id',
        'body',
        'source_language',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(FeedComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(FeedComment::class, 'parent_id')->oldest();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(FeedCommentReaction::class);
    }


    public function translations(): HasMany
    {
        return $this->hasMany(FeedCommentTranslation::class);
    }

    public function viewerReaction(): HasOne
    {
        return $this->hasOne(FeedCommentReaction::class)->where('user_id', auth()->id());
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }
}

