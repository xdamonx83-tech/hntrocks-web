<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MomentSpotlight extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'moment_id',
        'selected_by',
        'title',
        'note',
        'week_starts_at',
        'week_ends_at',
        'status',
        'is_active',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'week_starts_at' => 'date',
            'week_ends_at' => 'date',
            'is_active' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function moment(): BelongsTo
    {
        return $this->belongsTo(Moment::class);
    }

    public function selectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'selected_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('is_active', true)
            ->where(function (Builder $dateQuery): void {
                $today = now()->toDateString();

                $dateQuery->where(function (Builder $range) use ($today): void {
                    $range->where(function (Builder $start) use ($today): void {
                        $start->whereNull('week_starts_at')->orWhereDate('week_starts_at', '<=', $today);
                    })->where(function (Builder $end) use ($today): void {
                        $end->whereNull('week_ends_at')->orWhereDate('week_ends_at', '>=', $today);
                    });
                })->orWhereNull('week_starts_at');
            });
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACTIVE => __('ui.moment_week_status_active'),
            self::STATUS_ARCHIVED => __('ui.moment_week_status_archived'),
            self::STATUS_DRAFT => __('ui.moment_week_status_draft'),
        ];
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function dateLabel(): string
    {
        if (! $this->week_starts_at && ! $this->week_ends_at) {
            return __('ui.moment_week_no_date');
        }

        $start = $this->week_starts_at?->format('d.m.Y') ?? '—';
        $end = $this->week_ends_at?->format('d.m.Y') ?? '—';

        return $start.' – '.$end;
    }
}
