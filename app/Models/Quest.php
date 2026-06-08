<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Quest extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'category',
        'action',
        'target_count',
        'xp_reward',
        'badge_slug',
        'icon',
        'icon_path',
        'description',
        'period',
        'is_weekly_contract',
        'contract_starts_at',
        'contract_ends_at',
        'is_repeatable',
        'is_active',
        'notify_on_completion',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'target_count' => 'integer',
            'xp_reward' => 'integer',
            'is_weekly_contract' => 'boolean',
            'contract_starts_at' => 'datetime',
            'contract_ends_at' => 'datetime',
            'is_repeatable' => 'boolean',
            'is_active' => 'boolean',
            'notify_on_completion' => 'boolean',
        ];
    }


    public function contractStatus(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }

        $now = now();

        if ($this->contract_starts_at && $this->contract_starts_at->isFuture()) {
            return 'planned';
        }

        if ($this->contract_ends_at && $this->contract_ends_at->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    public function contractStatusLabel(): string
    {
        return match ($this->contractStatus()) {
            'active' => __('ui.contract_status_active'),
            'planned' => __('ui.contract_status_planned'),
            'expired' => __('ui.contract_status_expired'),
            'inactive' => __('ui.contract_status_inactive'),
            default => __('ui.contract_status_active'),
        };
    }

    public function actionLabel(): string
    {
        return self::actionOptions()[$this->action] ?? $this->action;
    }

    public static function actionOptions(): array
    {
        return [
            'feed_post_created' => __('ui.contract_action_feed_post_created'),
            'feed_comment_created' => __('ui.contract_action_feed_comment_created'),
            'feed_like_given' => __('ui.contract_action_feed_like_given'),
            'moment_created' => __('ui.contract_action_moment_created'),
            'moment_comment_created' => __('ui.contract_action_moment_comment_created'),
            'moment_like_given' => __('ui.contract_action_moment_like_given'),
            'lfg_post_created' => __('ui.contract_action_lfg_post_created'),
            'lfg_application_sent' => __('ui.contract_action_lfg_application_sent'),
            'team_created' => __('ui.contract_action_team_created'),
            'team_join_requested' => __('ui.contract_action_team_join_requested'),
            'team_lfg_post_created' => __('ui.contract_action_team_lfg_post_created'),
            'cup_submission_created' => __('ui.contract_action_cup_submission_created'),
            'cup_idea_submitted' => __('ui.contract_action_cup_idea_submitted'),
            'cup_idea_voted' => __('ui.contract_action_cup_idea_voted'),
            'loadout_challenge_submission_created' => __('ui.contract_action_loadout_challenge_submission_created'),
            'loadout_challenge_submission_accepted' => __('ui.contract_action_loadout_challenge_submission_accepted'),
            'moment_of_week_selected' => __('ui.contract_action_moment_of_week_selected'),
            'media_uploaded' => __('ui.contract_action_media_uploaded'),
        ];
    }


    public function iconUrl(): ?string
    {
        if ($this->icon_path) {
            return Storage::disk('public')->url($this->icon_path);
        }

        return null;
    }

    public function periodLabel(): string
    {
        return match ($this->period) {
            'daily' => 'Täglich',
            'weekly' => 'Wöchentlich',
            'monthly' => 'Monatlich',
            'seasonal' => 'Saisonal',
            default => 'Einmalig',
        };
    }

    public function progress(): HasMany
    {
        return $this->hasMany(QuestProgress::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quest_user')
            ->withPivot(['progress_count', 'completed_at', 'reward_claimed_at'])
            ->withTimestamps();
    }
}
