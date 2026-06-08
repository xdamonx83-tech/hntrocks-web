<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CupFeedbackEntry extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_REVIEWING = 'reviewing';
    public const STATUS_PLANNED = 'planned';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'cup_id',
        'user_id',
        'category',
        'subject',
        'message',
        'rating_overall',
        'rating_rules',
        'rating_scoring',
        'rating_submission',
        'rating_fairness',
        'would_join_again',
        'preferred_next_format',
        'liked_options',
        'issue_options',
        'idea_options',
        'contact_allowed',
        'status',
        'assigned_to',
        'resolved_by',
        'admin_note',
        'submitted_at',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'liked_options' => 'array',
            'issue_options' => 'array',
            'idea_options' => 'array',
            'contact_allowed' => 'boolean',
            'rating_overall' => 'integer',
            'rating_rules' => 'integer',
            'rating_scoring' => 'integer',
            'rating_submission' => 'integer',
            'rating_fairness' => 'integer',
            'submitted_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function cup(): BelongsTo
    {
        return $this->belongsTo(Cup::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public static function categoryOptions(): array
    {
        return [
            'praise' => __('ui.cup_feedback_category_praise'),
            'problem' => __('ui.cup_feedback_category_problem'),
            'improvement' => __('ui.cup_feedback_category_improvement'),
            'rules' => __('ui.cup_feedback_category_rules'),
            'scoring' => __('ui.cup_feedback_category_scoring'),
            'submission' => __('ui.cup_feedback_category_submission'),
            'fairness' => __('ui.cup_feedback_category_fairness'),
            'idea' => __('ui.cup_feedback_category_idea'),
            'other' => __('ui.cup_feedback_category_other'),
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_NEW => __('ui.cup_feedback_status_new'),
            self::STATUS_READ => __('ui.cup_feedback_status_read'),
            self::STATUS_REVIEWING => __('ui.cup_feedback_status_reviewing'),
            self::STATUS_PLANNED => __('ui.cup_feedback_status_planned'),
            self::STATUS_RESOLVED => __('ui.cup_feedback_status_resolved'),
            self::STATUS_REJECTED => __('ui.cup_feedback_status_rejected'),
            self::STATUS_ARCHIVED => __('ui.cup_feedback_status_archived'),
        ];
    }

    public static function wouldJoinAgainOptions(): array
    {
        return [
            'yes' => __('ui.cup_feedback_join_yes'),
            'maybe' => __('ui.cup_feedback_join_maybe'),
            'no' => __('ui.cup_feedback_join_no'),
        ];
    }

    public static function nextFormatOptions(): array
    {
        return [
            'solo' => __('ui.cup_feedback_format_solo'),
            'duo' => __('ui.cup_feedback_format_duo'),
            'trio' => __('ui.cup_feedback_format_trio'),
            'team' => __('ui.cup_feedback_format_team'),
            'platform_split' => __('ui.cup_feedback_format_platform_split'),
            'objective' => __('ui.cup_feedback_format_objective'),
        ];
    }

    public static function likedOptions(): array
    {
        return [
            'scoring' => __('ui.cup_feedback_liked_scoring'),
            'rules' => __('ui.cup_feedback_liked_rules'),
            'submissions' => __('ui.cup_feedback_liked_submissions'),
            'leaderboard' => __('ui.cup_feedback_liked_leaderboard'),
            'hall_of_fame' => __('ui.cup_feedback_liked_hall_of_fame'),
            'prizes' => __('ui.cup_feedback_liked_prizes'),
            'community' => __('ui.cup_feedback_liked_community'),
        ];
    }

    public static function issueOptions(): array
    {
        return [
            'rules_unclear' => __('ui.cup_feedback_issue_rules_unclear'),
            'submission_difficult' => __('ui.cup_feedback_issue_submission_difficult'),
            'ai_unclear' => __('ui.cup_feedback_issue_ai_unclear'),
            'scoring_unclear' => __('ui.cup_feedback_issue_scoring_unclear'),
            'too_short' => __('ui.cup_feedback_issue_too_short'),
            'platform_fairness' => __('ui.cup_feedback_issue_platform_fairness'),
            'communication' => __('ui.cup_feedback_issue_communication'),
        ];
    }

    public static function ideaOptions(): array
    {
        return [
            'bonus_gauntlet' => __('ui.cup_feedback_idea_bonus_gauntlet'),
            'bonus_boss' => __('ui.cup_feedback_idea_bonus_boss'),
            'manual_review' => __('ui.cup_feedback_idea_manual_review'),
            'recordings' => __('ui.cup_feedback_idea_recordings'),
            'longer_runtime' => __('ui.cup_feedback_idea_longer_runtime'),
            'more_prizes' => __('ui.cup_feedback_idea_more_prizes'),
            'team_cup' => __('ui.cup_feedback_idea_team_cup'),
        ];
    }

    public function categoryLabel(): string
    {
        return self::categoryOptions()[$this->category] ?? $this->category;
    }

    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status] ?? $this->status;
    }

    public function wouldJoinAgainLabel(): string
    {
        return $this->would_join_again ? (self::wouldJoinAgainOptions()[$this->would_join_again] ?? $this->would_join_again) : '—';
    }

    public function nextFormatLabel(): string
    {
        return $this->preferred_next_format ? (self::nextFormatOptions()[$this->preferred_next_format] ?? $this->preferred_next_format) : '—';
    }

    public function averageRating(): ?float
    {
        $ratings = collect([
            $this->rating_overall,
            $this->rating_rules,
            $this->rating_scoring,
            $this->rating_submission,
            $this->rating_fairness,
        ])->filter(fn ($rating): bool => is_numeric($rating));

        return $ratings->isEmpty() ? null : round((float) $ratings->avg(), 1);
    }
}
