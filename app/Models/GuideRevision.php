<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuideRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_id',
        'version',
        'author_id',
        'category_id',
        'cover_media_id',
        'title',
        'summary',
        'tags',
        'language',
        'difficulty',
        'platform',
        'content_blocks',
        'reading_time_minutes',
        'status',
        'submitted_at',
        'reviewed_at',
        'moderator_id',
        'moderation_reason',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'tags' => 'array',
            'content_blocks' => 'array',
            'reading_time_minutes' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(GuideCategory::class);
    }

    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(GuideMedia::class, 'cover_media_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(GuideMedia::class, 'revision_id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'changes_requested'], true);
    }
}
