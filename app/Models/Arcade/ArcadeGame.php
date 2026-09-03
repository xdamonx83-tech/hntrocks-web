<?php

namespace App\Models\Arcade;

use App\Enums\Arcade\ArcadeGameStatus;
use App\Enums\Arcade\ArcadeGameType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ArcadeGame extends Model
{
    public const MAX_RANKED_REWARD = 10000;

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

            $game->reward_settings = $game->validatedRewardSettings((array) ($game->reward_settings ?? []));
        });
    }

    public function scopePublicCatalog(Builder $query): Builder
    {
        return $query->whereIn('status', [ArcadeGameStatus::Active->value, ArcadeGameStatus::ComingSoon->value, ArcadeGameStatus::Maintenance->value, ArcadeGameStatus::Event->value]);
    }

    public function getRouteKeyName(): string { return 'key'; }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    /**
     * @return array{ranked_reward_enabled: bool, ranked_win_reward: int, ranked_draw_reward: int, ranked_loss_reward: int}
     */
    public function rankedRewardSettings(): array
    {
        $settings = (array) ($this->reward_settings ?? []);

        return [
            'ranked_reward_enabled' => (bool) ($settings['ranked_reward_enabled'] ?? false),
            'ranked_win_reward' => (int) ($settings['ranked_win_reward'] ?? 0),
            'ranked_draw_reward' => (int) ($settings['ranked_draw_reward'] ?? 0),
            'ranked_loss_reward' => (int) ($settings['ranked_loss_reward'] ?? 0),
        ];
    }

    /**
     * Preserve unrelated reward settings while validating the generic ranked keys.
     *
     * @param array<string, mixed> $settings
     * @return array<string, mixed>
     */
    private function validatedRewardSettings(array $settings): array
    {
        if (array_key_exists('ranked_reward_enabled', $settings)) {
            $enabled = filter_var($settings['ranked_reward_enabled'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($enabled === null) {
                throw ValidationException::withMessages(['reward_settings.ranked_reward_enabled' => 'Ranked reward enabled must be boolean.']);
            }
            $settings['ranked_reward_enabled'] = $enabled;
        }

        foreach (['ranked_win_reward', 'ranked_draw_reward', 'ranked_loss_reward'] as $key) {
            if (! array_key_exists($key, $settings)) {
                continue;
            }

            $value = filter_var($settings[$key], FILTER_VALIDATE_INT);
            if ($value === false || $value < 0 || $value > self::MAX_RANKED_REWARD) {
                throw ValidationException::withMessages([
                    'reward_settings.' . $key => 'Ranked reward must be an integer between 0 and ' . self::MAX_RANKED_REWARD . '.',
                ]);
            }

            $settings[$key] = $value;
        }

        return $settings;
    }
}
