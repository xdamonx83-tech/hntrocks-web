<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CupSubmission extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'cup_id', 'cup_team_id', 'submitted_by', 'reviewed_by', 'screenshot_media_asset_id',
        'kills', 'bounty_tokens', 'banishes', 'extracted', 'reported_kills', 'reported_bounty_tokens',
        'reported_extracted', 'points', 'status', 'note', 'review_note',
        'screen_type', 'ai_valid_extract', 'ai_kills', 'ai_bounty_tokens', 'ai_confidence',
        'ai_complete_screenshot', 'ai_kills_source', 'ai_ambiguous_kills', 'ai_suspected_tampering',
        'ai_gamertag', 'ai_gamertag_normalized', 'ai_gamertag_confidence', 'ai_gamertag_mismatch',
        'ai_invalid_reason', 'ai_raw_result', 'sha256_hash', 'phash', 'image_width', 'image_height',
        'processed_at', 'submitted_at', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'kills' => 'integer', 'bounty_tokens' => 'integer', 'banishes' => 'integer', 'extracted' => 'boolean',
            'reported_kills' => 'integer', 'reported_bounty_tokens' => 'integer',
            'reported_extracted' => 'boolean', 'points' => 'integer',
            'ai_valid_extract' => 'boolean', 'ai_kills' => 'integer', 'ai_bounty_tokens' => 'integer',
            'ai_confidence' => 'float', 'ai_complete_screenshot' => 'boolean', 'ai_ambiguous_kills' => 'boolean',
            'ai_suspected_tampering' => 'boolean', 'ai_gamertag_confidence' => 'float',
            'ai_gamertag_mismatch' => 'boolean', 'ai_raw_result' => 'array', 'image_width' => 'integer',
            'image_height' => 'integer', 'processed_at' => 'datetime', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime',
        ];
    }

    public function cup(): BelongsTo { return $this->belongsTo(Cup::class); }
    public function team(): BelongsTo { return $this->belongsTo(CupTeam::class, 'cup_team_id'); }
    public function submitter(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function screenshot(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'screenshot_media_asset_id'); }

    public static function calculatePoints(int $kills, int $bountyTokens, bool $extracted): int
    {
        if ((bool) config('hunthub.cups.require_extract_for_score', true) && ! $extracted) return 0;

        $kills = max(0, $kills);
        $bountyTokens = min(4, max(0, $bountyTokens));

        if ((bool) config('hunthub.cups.require_bounty_for_score', true) && $bountyTokens <= 0) return 0;

        $bountyPoints = $bountyTokens * max(0, (int) config('hunthub.cups.points_per_bounty_token', 2));
        $killPoints = $kills * max(0, (int) config('hunthub.cups.points_per_kill', 1));

        return $bountyPoints + $killPoints;
    }

    public static function calculatePointsForCup(
        Cup $cup,
        int $kills,
        int $bountyTokens,
        bool $extracted,
        int $banishes = 0,
    ): int {
        if ($cup->usesManualReviewScoring()) {
            if (! $extracted || $bountyTokens <= 0) {
                return 0;
            }

            $kills = max(0, $kills);
            $bountyTokens = min(4, max(0, $bountyTokens));
            $banishes = min(2, max(0, $banishes));

            return $kills + ($bountyTokens * 2) + ($banishes * 2);
        }

        if (! $cup->usesSummerFirstTrophyScoring()) {
            return self::calculatePoints($kills, $bountyTokens, $extracted);
        }

        if (! $extracted || $bountyTokens <= 0) {
            return 0;
        }

        $kills = max(0, $kills);
        $killPoints = $kills * max(0, (int) data_get($cup->settings, 'scoring.points_per_kill', 1));
        $extractBonus = max(0, (int) data_get($cup->settings, 'scoring.first_trophy_extraction_bonus', 5));

        return $killPoints + $extractBonus;
    }

    public static function scoredStatuses(): array
    {
        return ['processed', 'approved', 'approved_manual'];
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'processed' => __('ui.cup_submission_status_processed'),
            'invalid' => __('ui.cup_submission_status_invalid'),
            'review_required' => __('ui.cup_submission_status_review_required'),
            'approved_manual' => __('ui.cup_submission_status_approved_manual'),
            'rejected_manual' => __('ui.cup_submission_status_rejected_manual'),
            'pending' => __('ui.cup_submission_status_pending'),
            'approved' => __('ui.cup_submission_status_approved'),
            'rejected' => __('ui.cup_submission_status_rejected'),
            default => __('ui.cup_status_unknown'),
        };
    }

    public function invalidReasonLabel(): ?string
    {
        if (! $this->ai_invalid_reason) return null;
        return match ($this->ai_invalid_reason) {
            'not_extracted' => __('ui.cup_submission_reason_not_extracted'),
            'duplicate_exact' => __('ui.cup_submission_reason_duplicate_exact'),
            'duplicate_similar' => __('ui.cup_submission_reason_duplicate_similar'),
            'invalid_image' => __('ui.cup_submission_reason_invalid_image'),
            'unreadable' => __('ui.cup_submission_reason_unreadable'),
            'bounty_out_of_range' => __('ui.cup_submission_reason_bounty_out_of_range'),
            'tampering_suspected' => __('ui.cup_submission_reason_tampering_suspected'),
            'gamertag_mismatch' => __('ui.cup_submission_reason_gamertag_mismatch'),
            'gamertag_unreadable' => __('ui.cup_submission_reason_gamertag_unreadable'),
            'ai_unavailable' => __('ui.cup_submission_reason_ai_unavailable'),
            'manual_reject' => __('ui.cup_submission_reason_manual_reject'),
            default => __('ui.cup_submission_reason_default'),
        };
    }

    public function resultSummary(): string
    {
        if ($this->status === 'review_required') return $this->invalidReasonLabel() ?: __('ui.cup_submission_summary_waiting_review');
        if ($this->status === 'invalid') return $this->invalidReasonLabel() ?: __('ui.cup_submission_summary_invalid');
        if ($this->ai_gamertag_mismatch) return __('ui.cup_submission_summary_gamertag_mismatch');
        if (! $this->extracted) return __('ui.cup_submission_summary_no_extract');
        if ((int) $this->bounty_tokens <= 0) return __('ui.cup_submission_summary_no_bounty');
        if ((int) $this->kills > 0) return __('ui.cup_submission_summary_bounty_and_kills');
        return __('ui.cup_submission_summary_bounty_only');
    }
}
