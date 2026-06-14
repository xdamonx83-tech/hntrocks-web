<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CupSubmissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pointsPerBountyToken = (int) config('hunthub.cups.points_per_bounty_token', 2);
        $pointsPerKill = (int) config('hunthub.cups.points_per_kill', 1);
        $countsForScore = (int) $this->points > 0;

        return [
            'id' => $this->id,
            'cup_id' => (int) $this->cup_id,
            'cup_team_id' => (int) $this->cup_team_id,
            'submitted_by' => (int) $this->submitted_by,
            'kills' => (int) $this->kills,
            'bounty_tokens' => (int) $this->bounty_tokens,
            'extracted' => (bool) $this->extracted,
            'reported_kills' => $this->reported_kills !== null ? (int) $this->reported_kills : null,
            'reported_bounty_tokens' => $this->reported_bounty_tokens !== null ? (int) $this->reported_bounty_tokens : null,
            'reported_extracted' => $this->reported_extracted !== null ? (bool) $this->reported_extracted : null,
            'points' => (int) $this->points,
            'score_breakdown' => [
                'points_per_bounty_token' => $pointsPerBountyToken,
                'points_per_kill' => $pointsPerKill,
                'bounty_points' => $countsForScore ? (int) $this->bounty_tokens * $pointsPerBountyToken : 0,
                'kill_points' => $countsForScore ? (int) $this->kills * $pointsPerKill : 0,
            ],
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'result_summary' => $this->resultSummary(),
            'invalid_reason' => $this->invalidReasonLabel(),
            'ai_status' => match ($this->status) {
                'processed', 'approved', 'approved_manual' => 'accepted',
                'invalid', 'rejected', 'rejected_manual' => 'rejected',
                'review_required' => 'manual_review',
                default => 'pending',
            },
            'ai_hint' => $this->resultSummary(),
            'note' => $this->note,
            'review_note' => $this->review_note,
            'has_screenshot' => $this->screenshot_media_asset_id !== null,
            'screenshot_path' => $this->screenshot_media_asset_id !== null
                ? route('api.v1.cups.submissions.screenshot', [$this->cup, $this->resource], false)
                : null,
            'ai' => [
                'screen_type' => $this->screen_type,
                'valid_extract' => (bool) $this->ai_valid_extract,
                'kills' => (int) $this->ai_kills,
                'bounty_tokens' => (int) $this->ai_bounty_tokens,
                'confidence' => $this->ai_confidence !== null ? (float) $this->ai_confidence : null,
                'complete_screenshot' => $this->ai_complete_screenshot !== null ? (bool) $this->ai_complete_screenshot : null,
                'kills_source' => $this->ai_kills_source,
                'ambiguous_kills' => (bool) $this->ai_ambiguous_kills,
                'suspected_tampering' => (bool) $this->ai_suspected_tampering,
                'gamertag' => $this->ai_gamertag,
                'gamertag_confidence' => $this->ai_gamertag_confidence !== null ? (float) $this->ai_gamertag_confidence : null,
                'gamertag_mismatch' => (bool) $this->ai_gamertag_mismatch,
            ],
            'image' => [
                'width' => $this->image_width ? (int) $this->image_width : null,
                'height' => $this->image_height ? (int) $this->image_height : null,
            ],
            'submitted_at' => $this->submitted_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
            'reviewed_at' => $this->reviewed_at?->toISOString(),
        ];
    }
}
