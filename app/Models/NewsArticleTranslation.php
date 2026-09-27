<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsArticleTranslation extends Model
{
    protected $fillable = [
        'locale',
        'slug',
        'title',
        'excerpt',
        'content_json',
        'seo_title',
        'seo_description',
        'canonical_url',
    ];

    protected function casts(): array
    {
        return ['content_json' => 'array'];
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(NewsArticle::class, 'news_article_id');
    }

    public function scopePublishable(Builder $query): Builder
    {
        return $query->whereNotNull('title')->where('title', '!=', '')
            ->whereNotNull('slug')->where('slug', '!=', '')
            ->whereJsonLength('content_json', '>', 0);
    }

    public function isPublishable(): bool
    {
        return trim((string) $this->title) !== ''
            && trim((string) $this->slug) !== ''
            && is_array($this->content_json)
            && $this->content_json !== [];
    }
}
