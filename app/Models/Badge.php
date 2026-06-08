<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Badge extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'rarity',
        'icon',
        'icon_path',
        'description',
        'xp_reward',
        'sort_order',
        'is_active',
        'is_manual_only',
        'notify_on_award',
    ];

    protected function casts(): array
    {
        return [
            'xp_reward' => 'integer',
            'is_active' => 'boolean',
            'is_manual_only' => 'boolean',
            'notify_on_award' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['awarded_at', 'awarded_by', 'award_reason'])
            ->withTimestamps();
    }

    public function iconUrl(): ?string
    {
        if ($this->icon_path) {
            return Storage::disk('public')->url($this->icon_path);
        }

        return null;
    }

    public function rarityLabel(): string
    {
        return match ($this->rarity) {
            'uncommon' => 'Ungewöhnlich',
            'rare' => 'Selten',
            'epic' => 'Episch',
            'legendary' => 'Legendär',
            default => 'Normal',
        };
    }
}
