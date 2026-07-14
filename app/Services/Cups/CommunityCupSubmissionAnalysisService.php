<?php

namespace App\Services\Cups;

use App\Models\Cup;
use App\Models\CupSubmission;
use App\Support\CupOrganizerAccess;
use Illuminate\Http\UploadedFile;

class CommunityCupSubmissionAnalysisService extends CupSubmissionAnalysisService
{
    public function analyze(Cup $cup, UploadedFile $file, ?int $ignoreSubmissionId = null): array
    {
        if (CupOrganizerAccess::usesAi($cup)) {
            return parent::analyze($cup, $file, $ignoreSubmissionId);
        }

        $path = (string) $file->getRealPath();
        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType());

        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return $this->manualResult([
                'upload_ok' => false,
                'status' => 'invalid',
                'invalid_reason' => 'invalid_image',
                'message' => __('ui.cup_analysis_upload_invalid'),
            ]);
        }

        if (! in_array($mimeType, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return $this->manualResult([
                'upload_ok' => false,
                'status' => 'invalid',
                'invalid_reason' => 'invalid_image',
                'message' => __('ui.cup_analysis_mime_error'),
            ]);
        }

        $imageInfo = @getimagesize($path);
        if (! is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return $this->manualResult([
                'upload_ok' => false,
                'status' => 'invalid',
                'invalid_reason' => 'invalid_image',
                'message' => __('ui.cup_analysis_unreadable_file'),
            ]);
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        if ($width < 900 || $height < 500) {
            return $this->manualResult([
                'upload_ok' => false,
                'status' => 'invalid',
                'invalid_reason' => 'invalid_image',
                'message' => __('ui.cup_analysis_too_small'),
                'image_width' => $width,
                'image_height' => $height,
            ]);
        }

        $ratio = $width / max(1, $height);
        if ($ratio < 1.45 || $ratio > 2.40) {
            return $this->manualResult([
                'upload_ok' => false,
                'status' => 'invalid',
                'invalid_reason' => 'invalid_image',
                'message' => __('ui.cup_analysis_full_horizontal'),
                'image_width' => $width,
                'image_height' => $height,
            ]);
        }

        $sha256 = hash_file('sha256', $path) ?: null;
        $duplicate = $sha256
            ? CupSubmission::query()
                ->where('cup_id', $cup->id)
                ->where('sha256_hash', $sha256)
                ->when($ignoreSubmissionId, fn ($query) => $query->where('id', '!=', $ignoreSubmissionId))
                ->first(['id'])
            : null;

        if ($duplicate) {
            return $this->manualResult([
                'status' => 'invalid',
                'invalid_reason' => 'duplicate_exact',
                'message' => __('ui.cup_analysis_duplicate_exact'),
                'sha256_hash' => $sha256,
                'image_width' => $width,
                'image_height' => $height,
                'raw_ai_result' => [
                    'verification_mode' => CupOrganizerAccess::VERIFICATION_MANUAL,
                    'duplicate_submission_id' => $duplicate->id,
                ],
            ]);
        }

        return $this->manualResult([
            'status' => 'review_required',
            'invalid_reason' => 'manual_review',
            'message' => __('ui.cup_analysis_submission_saved'),
            'sha256_hash' => $sha256,
            'image_width' => $width,
            'image_height' => $height,
            'raw_ai_result' => [
                'verification_mode' => CupOrganizerAccess::VERIFICATION_MANUAL,
                'ai_skipped' => true,
            ],
        ]);
    }

    private function manualResult(array $overrides): array
    {
        return array_merge([
            'upload_ok' => true,
            'status' => 'review_required',
            'invalid_reason' => 'manual_review',
            'screen_type' => null,
            'ai_valid_extract' => false,
            'ai_kills' => 0,
            'ai_bounty_tokens' => 0,
            'ai_confidence' => null,
            'ai_complete_screenshot' => null,
            'ai_kills_source' => null,
            'ai_ambiguous_kills' => false,
            'ai_suspected_tampering' => false,
            'ai_gamertag' => null,
            'ai_gamertag_normalized' => null,
            'ai_gamertag_confidence' => null,
            'ai_gamertag_mismatch' => false,
            'kills' => 0,
            'bounty_tokens' => 0,
            'extracted' => false,
            'points' => 0,
            'message' => __('ui.cup_analysis_submission_saved'),
            'sha256_hash' => null,
            'phash' => null,
            'image_width' => null,
            'image_height' => null,
            'raw_ai_result' => [
                'verification_mode' => CupOrganizerAccess::VERIFICATION_MANUAL,
                'ai_skipped' => true,
            ],
        ], $overrides);
    }
}
