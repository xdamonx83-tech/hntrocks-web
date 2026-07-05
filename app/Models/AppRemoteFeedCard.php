<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRemoteFeedCard extends Model
{
    protected $fillable = [
        'remote_id',
        'title_de',
        'title_en',
        'body_de',
        'body_en',
        'cta_label_de',
        'cta_label_en',
        'action_url',
        'style_variant',
        'priority',
        'is_active',
        'dismissible',
        'starts_at',
        'ends_at',
        'audience_type',
        'audience_payload',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'is_active' => 'boolean',
            'dismissible' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'audience_payload' => 'array',
        ];
    }

    public function scopeVisibleNow(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $starts) use ($now): void {
                $starts->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $ends) use ($now): void {
                $ends->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function dismissals(): HasMany
    {
        return $this->hasMany(AppRemoteFeedCardDismissal::class, 'remote_card_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
