<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticleInteraction extends Model
{
    protected $fillable = ['user_id', 'liked', 'saved'];

    protected function casts(): array
    {
        return ['liked' => 'boolean', 'saved' => 'boolean'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'news_article_id');
    }
}
