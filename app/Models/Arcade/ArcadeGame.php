<?php

namespace App\Models\Arcade;

use App\Enums\Arcade\ArcadeGameStatus;
use App\Enums\Arcade\ArcadeGameType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArcadeGame extends Model
{
    protected $fillable = ['key', 'name_de', 'name_en', 'description_de', 'description_en', 'type', 'status', 'cover_path', 'sort_order', 'min_players', 'max_players', 'casual_enabled', 'ranked_enabled', 'client_engine_key', 'min_client_version', 'game_version', 'launch_url', 'badge_de', 'badge_en', 'settings', 'reward_settings', 'created_by', 'updated_by', 'published_at'];

    protected function casts(): array
    {
        return ['type' => ArcadeGameType::class, 'status' => ArcadeGameStatus::class, 'sort_order' => 'integer', 'min_players' => 'integer', 'max_players' => 'integer', 'casual_enabled' => 'boolean', 'ranked_enabled' => 'boolean', 'game_version' => 'integer', 'settings' => 'array', 'reward_settings' => 'array', 'published_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $game): void {
            if ($game->min_players < 1 || $game->max_players < $game->min_players) {
                throw ValidationException::withMessages(['max_players' => 'Player limits must satisfy min_players >= 1 and max_players >= min_players.']);
            }
        });
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query->whereIn('status', [ArcadeGameStatus::Active->value, ArcadeGameStatus::ComingSoon->value, ArcadeGameStatus::Maintenance->value, ArcadeGameStatus::Event->value]);
    }

    public function releases(): HasMany
    {
        return $this->hasMany(ArcadeGameRelease::class, 'game_id');
    }

    public function publishedRelease(): HasOne
    {
        return $this->hasOne(ArcadeGameRelease::class, 'game_id')
            ->where('status', ArcadeGameRelease::STATUS_PUBLISHED)
            ->latestOfMany('published_at');
    }

    public function launchTickets(): HasMany
    {
        return $this->hasMany(ArcadeLaunchTicket::class, 'game_id');
    }

    public function getRouteKeyName(): string { return 'key'; }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }
}
