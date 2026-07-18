<?php

namespace App\Models;

use App\Models\Concerns\HidesBlockedUsers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GuideComment extends Model
{
    use HasFactory;
    use HidesBlockedUsers;
    use SoftDeletes;

    protected $fillable = ['guide_id', 'user_id', 'parent_id', 'body'];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GuideComment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(GuideComment::class, 'parent_id')->oldest();
    }

    public function canEdit(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->id;
    }

    public function canDelete(?User $user): bool
    {
        return $user !== null && (
            $this->canEdit($user)
            || (int) ($this->guide?->author_id ?? 0) === (int) $user->id
            || $user->isAdmin()
        );
    }
}
