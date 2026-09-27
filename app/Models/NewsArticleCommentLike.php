<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticleCommentLike extends Model
{
    protected $fillable = ['user_id'];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(NewsArticleComment::class, 'news_article_comment_id');
    }
}
