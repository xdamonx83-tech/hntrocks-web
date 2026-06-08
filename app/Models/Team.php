<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Team extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'tagline',
        'description',
        'platform',
        'playstyle',
        'region',
        'language',
        'visibility',
        'recruitment_status',
        'avatar_path',
        'cover_path',
        'status',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function pendingMembers(): HasMany
    {
        return $this->members()->where('status', 'pending');
    }

    public function teamLfgPosts(): HasMany
    {
        return $this->hasMany(TeamLfgPost::class);
    }

    public function feedPosts(): HasMany
    {
        return $this->hasMany(FeedPost::class);
    }

    public function avatarUrl(): string
    {
        if ($this->avatar_path) {
            return Storage::disk('public')->url($this->avatar_path);
        }

        return asset('assets/vikinger/img/default-avatar.svg');
    }

    public function coverUrl(): string
    {
        if ($this->cover_path) {
            return Storage::disk('public')->url($this->cover_path);
        }

        return asset('assets/vikinger/img/default-cover.svg');
    }

    public function membershipFor(?User $user): ?TeamMember
    {
        if (! $user) {
            return null;
        }

        return $this->members->firstWhere('user_id', $user->id);
    }

    public function isActiveMember(?User $user): bool
    {
        $membership = $this->membershipFor($user);

        return $membership !== null && $membership->status === 'active';
    }

    public function canManage(?User $user): bool
    {
        $membership = $this->membershipFor($user);

        return $membership !== null
            && $membership->status === 'active'
            && in_array($membership->role, ['owner', 'officer'], true);
    }

    public function isOwner(?User $user): bool
    {
        return $user !== null && (int) $this->owner_id === (int) $user->id;
    }

    public function visibilityLabel(): string
    {
        return match ($this->visibility) {
            'private' => __('ui.visibility_private'),
            default => __('ui.visibility_public'),
        };
    }

    public function recruitmentLabel(): string
    {
        return match ($this->recruitment_status) {
            'closed' => __('ui.recruiting_closed'),
            default => __('ui.recruiting_open'),
        };
    }
}
