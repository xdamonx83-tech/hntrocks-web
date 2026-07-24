<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Guide extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_REVIEW = 'review';
    public const STATUS_CHANGES_REQUESTED = 'changes_requested';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_HIDDEN = 'hidden';

    public const DIFFICULTIES = ['beginner', 'advanced', 'expert'];
    public const PLATFORMS = ['all', 'pc', 'playstation', 'xbox'];
    public const LANGUAGES = ['de', 'en'];

    protected $fillable = [
        'user_id',
        'title',
        'slug',
        'summary',
        'cover_path',
        'category',
        'tags',
        'language',
        'difficulty',
        'platform',
        'status',
        'is_featured',
        'reading_time_minutes',
        'views_count',
        'helpful_count',
        'comments_count',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'is_featured' => 'boolean',
            'reading_time_minutes' => 'integer',
            'views_count' => 'integer',
            'helpful_count' => 'integer',
            'comments_count' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function coverUrl(): ?string
    {
        $path = trim((string) $this->cover_path);
        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return Storage::disk('public')->url($path);
    }
}
