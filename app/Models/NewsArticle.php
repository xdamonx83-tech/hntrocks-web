<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class NewsArticle extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public const LOCALES = ['de', 'en', 'es', 'ru'];

    protected $fillable = [
        'status',
        'category_key',
        'tags',
        'hero_media_asset_id',
        'featured',
        'comments_enabled',
        'scheduled_at',
        'published_at',
        'archived_at',
        'lock_version',
        'created_by_user_id',
        'updated_by_user_id',
        'published_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'featured' => 'boolean',
            'comments_enabled' => 'boolean',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
            'lock_version' => 'integer',
        ];
    }

    public function translations(): HasMany
    {
        return $this->hasMany(NewsArticleTranslation::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(NewsArticleRevision::class)->orderByDesc('revision_number');
    }

    public function heroMedia(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'hero_media_asset_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by_user_id');
    }

    public function mediaAssets(): MorphMany
    {
        return $this->morphMany(MediaAsset::class, 'attachable');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(NewsArticleComment::class);
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(NewsArticleInteraction::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function translationForLocale(string $locale): ?NewsArticleTranslation
    {
        if (! in_array($locale, self::LOCALES, true)) {
            return null;
        }

        if ($this->relationLoaded('translations')) {
            return $this->translations->firstWhere('locale', $locale);
        }

        return $this->translations()->where('locale', $locale)->first();
    }

    public static function statuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SCHEDULED,
            self::STATUS_PUBLISHED,
            self::STATUS_ARCHIVED,
        ];
    }
}
