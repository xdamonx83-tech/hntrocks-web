<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MomentComment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'moment_id',
        'user_id',
        'parent_id',
        'body',
        'likes_count',
        'replies_count',
    ];

    protected function casts(): array
    {
        return [
            'likes_count' => 'integer',
            'replies_count' => 'integer',
        ];
    }

    public function moment(): BelongsTo
    {
        return $this->belongsTo(Moment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MomentComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(MomentComment::class, 'parent_id')->oldest();
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(MomentCommentReaction::class);
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->reactions()->where('user_id', $user->id)->where('type', 'like')->exists();
    }

    public function canBeEditedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    public function canBeDeletedBy(?User $user): bool
    {
        return $user !== null && (
            (int) $this->user_id === (int) $user->id
            || (int) ($this->moment?->user_id ?? 0) === (int) $user->id
            || $user->isAdmin()
        );
    }
}
