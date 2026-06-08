<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class LoadoutChallenge extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'created_by',
        'title',
        'slug',
        'summary',
        'description',
        'rules',
        'loadout_notes',
        'status',
        'starts_at',
        'ends_at',
        'xp_reward',
        'badge_slug',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'xp_reward' => 'integer',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => __('ui.loadout_status_draft'),
            self::STATUS_ACTIVE => __('ui.loadout_status_active'),
            self::STATUS_ARCHIVED => __('ui.loadout_status_archived'),
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LoadoutChallengeSubmission::class);
    }

    public function acceptedSubmissions(): HasMany
    {
        return $this->submissions()->where('status', LoadoutChallengeSubmission::STATUS_ACCEPTED);
    }

    public function scopePublicVisible(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? self::statusOptions()[self::STATUS_DRAFT];
    }

    public function runtimeStatus(): string
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return $this->status;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return 'planned';
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return 'ended';
        }

        return 'running';
    }

    public function runtimeStatusLabel(): string
    {
        return match ($this->runtimeStatus()) {
            'running' => __('ui.loadout_runtime_running'),
            'planned' => __('ui.loadout_runtime_planned'),
            'ended' => __('ui.loadout_runtime_ended'),
            self::STATUS_ARCHIVED => __('ui.loadout_status_archived'),
            default => $this->statusLabel(),
        };
    }

    public function canAcceptSubmissions(): bool
    {
        if ($this->status !== self::STATUS_ACTIVE) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->ends_at && $this->ends_at->isPast()) {
            return false;
        }

        return true;
    }
}
