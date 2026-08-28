<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Guide extends Model
{
    use HasFactory;
    use HidesBlockedUsers;

    public const STATUSES = [
        'draft',
        'pending_review',
        'changes_requested',
        'published',
        'rejected',
        'archived',
    ];

    protected $fillable = [
        'author_id',
        'slug',
        'status',
        'current_published_revision_id',
        'working_revision_id',
        'is_featured',
        'show_in_profile',
        'helpful_count',
        'bookmarks_count',
        'comments_count',
        'published_at',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'show_in_profile' => 'boolean',
            'helpful_count' => 'integer',
            'bookmarks_count' => 'integer',
            'comments_count' => 'integer',
            'published_at' => 'datetime',
            'archived_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(GuideRevision::class)->orderByDesc('version');
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(GuideRevision::class, 'current_published_revision_id');
    }

    public function workingRevision(): BelongsTo
    {
        return $this->belongsTo(GuideRevision::class, 'working_revision_id');
    }

    public function media(): HasMany
    {
        return $this->hasMany(GuideMedia::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(GuideComment::class);
    }

    public function helpfulVotes(): HasMany
    {
        return $this->hasMany(GuideHelpfulVote::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(GuideBookmark::class);
    }

    public function moderationEvents(): HasMany
    {
        return $this->hasMany(GuideModerationEvent::class)->latest();
    }

    public function reputationEntries(): HasMany
    {
        return $this->hasMany(GuideReputationEntry::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->whereNull('archived_at')
            ->whereNotNull('current_published_revision_id');
    }

    public function scopeForAuthor(Builder $query, User $user): Builder
    {
        return $query->where('author_id', $user->id);
    }

    public function isPublished(): bool
    {
        return $this->archived_at === null && $this->current_published_revision_id !== null;
    }

    public function wasEverPublished(): bool
    {
        if ($this->current_published_revision_id !== null || $this->published_at !== null) {
            return true;
        }

        if (array_key_exists('has_published_revision', $this->attributes)) {
            return (bool) $this->attributes['has_published_revision'];
        }

        if ($this->relationLoaded('revisions')) {
            return $this->revisions->contains(
                fn (GuideRevision $revision): bool => $revision->status === 'published'
            );
        }

        return $this->revisions()->where('status', 'published')->exists();
    }

    public function canBeDeletedByAuthor(): bool
    {
        return $this->status === 'draft'
            && $this->archived_at === null
            && ! $this->wasEverPublished();
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->author_id === (int) $user->id;
    }

    public function isHelpfulFor(?User $user): bool
    {
        return $user !== null && $this->helpfulVotes()->where('user_id', $user->id)->exists();
    }

    public function isBookmarkedBy(?User $user): bool
    {
        return $user !== null && $this->bookmarks()->where('user_id', $user->id)->exists();
    }

    protected function blockedAuthorColumn(): string
    {
        return 'author_id';
    }
}
