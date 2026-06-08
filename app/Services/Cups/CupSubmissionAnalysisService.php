<?php

namespace App\Services\Cups;

use App\Models\Cup;
use App\Models\CupSubmission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CupSubmissionAnalysisService
{
    public function analyze(Cup $cup, UploadedFile $file, ?int $ignoreSubmissionId = null): array
    {
        $path = (string) $file->getRealPath();
        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType() ?: 'image/png');
        $basic = $this->validateImage($path, $file);

        if (! $basic['ok']) {
            return $this->result([
                'upload_ok' => false,
                'status' => 'invalid',
                'invalid_reason' => 'invalid_image',
                'message' => $basic['message'],
                'image_width' => $basic['width'] ?? null,
                'image_height' => $basic['height'] ?? null,
            ]);
        }

        $sha256 = is_file($path) ? (hash_file('sha256', $path) ?: null) : null;
        $phash = $this->computePHash($path);

        $duplicate = $sha256 ? CupSubmission::query()
            ->where('cup_id', $cup->id)
            ->where('sha256_hash', $sha256)
            ->when($ignoreSubmissionId, fn ($query) => $query->whereKeyNot($ignoreSubmissionId))
            ->first(['id']) : null;

        if ($duplicate) {
            return $this->result([
                'status' => 'invalid',
                'invalid_reason' => 'duplicate_exact',
                'message' => __('ui.cup_analysis_duplicate_exact'),
                'sha256_hash' => $sha256,
                'phash' => $phash,
                'image_width' => $basic['width'],
                'image_height' => $basic['height'],
                'raw_ai_result' => ['duplicate_submission_id' => $duplicate->id],
            ]);
        }

        $similar = $phash ? $this->findSimilarSubmission($cup, $phash, $ignoreSubmissionId) : null;
        $ai = $this->parseSummaryScreen($cup, $path, $mimeType);

        if (! $ai['ok']) {
            return $this->result([
                'status' => 'review_required',
                'invalid_reason' => 'ai_unavailable',
                'message' => __('ui.cup_analysis_ai_unavailable'),
                'sha256_hash' => $sha256,
                'phash' => $phash,
                'image_width' => $basic['width'],
                'image_height' => $basic['height'],
                'raw_ai_result' => ['error' => $ai, 'similar_match' => $similar],
            ]);
        }

        $gamertagCheck = ['ok' => false, 'message' => 'skipped_for_awards_preset'];
        if ($cup->aiPromptPreset() !== 'awards_first_trophy') {
            $gamertagCheck = $this->parseBloodlineGamertag($path, $mimeType);
        }
        $aiGamertag = null;
        $aiGamertagNormalized = null;
        $aiGamertagConfidence = null;

        if (($gamertagCheck['ok'] ?? false) && ($gamertagCheck['found'] ?? false)) {
            $aiGamertag = trim((string) ($gamertagCheck['raw_name'] ?? ''));
            $aiGamertagNormalized = $this->normalizeGamertag((string) ($gamertagCheck['normalized_name'] ?? $aiGamertag));
            $aiGamertagConfidence = max(0.0, min(1.0, (float) ($gamertagCheck['confidence'] ?? 0)));
        }

        $screenType = (string) ($ai['screen_type'] ?? 'unknown');
        $validExtract = (bool) ($ai['valid_extract'] ?? false);
        $kills = max(0, min(99, (int) ($ai['kills'] ?? 0)));
        $bounty = max(0, min(4, (int) ($ai['bounty'] ?? 0)));
        $confidence = $ai['confidence'] ?? null;
        $complete = (bool) ($ai['complete_screenshot'] ?? true);
        $tampering = (bool) ($ai['suspected_tampering'] ?? false);
        $ambiguous = (bool) ($ai['ambiguous_kills'] ?? false);
        $ambiguousBounty = (bool) ($ai['ambiguous_bounty'] ?? false);
        $killsSource = (string) ($ai['kills_source'] ?? 'unknown');

        $status = 'processed';
        $invalidReason = null;
        $points = 0;
        $message = __('ui.cup_analysis_valid_run');

        if (! $complete) {
            $status = 'invalid';
            $invalidReason = 'invalid_image';
            $validExtract = false;
            $kills = 0;
            $bounty = 0;
            $message = __('ui.cup_analysis_invalid_upload_full');
        } elseif ($tampering) {
            $status = 'review_required';
            $invalidReason = 'tampering_suspected';
            $validExtract = false;
            $kills = 0;
            $bounty = 0;
            $message = __('ui.cup_analysis_tampering');
        } elseif (($confidence !== null && (float) $confidence < 0.65) || $screenType === 'unknown' || $ambiguous || $ambiguousBounty) {
            $status = 'review_required';
            $invalidReason = 'unreadable';
            $message = __('ui.cup_analysis_unreadable');
        } elseif (! $validExtract || $screenType === 'failed') {
            $status = 'invalid';
            $invalidReason = 'not_extracted';
            $message = __('ui.cup_analysis_not_extracted');
        } else {
            $points = CupSubmission::calculatePointsForCup($cup, $kills, $bounty, true);
            $message = $points > 0
                ? __('ui.cup_analysis_valid_points', ['points' => $points])
                : __('ui.cup_analysis_valid_no_bounty');
        }

        $platformReviewReason = (string) ($ai['platform_review_reason'] ?? '');
        if ($status === 'processed' && in_array($platformReviewReason, ['platform_mismatch', 'platform_unclear'], true)) {
            $status = 'review_required';
            $invalidReason = $platformReviewReason;
            $points = 0;
            $message = $platformReviewReason === 'platform_mismatch'
                ? __('ui.cup_analysis_platform_mismatch')
                : __('ui.cup_analysis_platform_unclear');
        }

        return $this->result([
            'status' => $status,
            'invalid_reason' => $invalidReason,
            'screen_type' => $screenType,
            'ai_valid_extract' => $validExtract,
            'ai_kills' => $kills,
            'ai_bounty_tokens' => $bounty,
            'ai_confidence' => $confidence,
            'ai_complete_screenshot' => $complete,
            'ai_kills_source' => $killsSource,
            'ai_ambiguous_kills' => $ambiguous,
            'ai_suspected_tampering' => $tampering,
            'ai_gamertag' => $aiGamertag,
            'ai_gamertag_normalized' => $aiGamertagNormalized,
            'ai_gamertag_confidence' => $aiGamertagConfidence,
            'ai_gamertag_mismatch' => false,
            'kills' => $kills,
            'bounty_tokens' => $bounty,
            'extracted' => $validExtract && $screenType === 'success',
            'points' => $points,
            'message' => $message,
            'sha256_hash' => $sha256,
            'phash' => $phash,
            'image_width' => $basic['width'],
            'image_height' => $basic['height'],
            'raw_ai_result' => [
                'normalized' => $ai['normalized'] ?? null,
                'raw_output_text' => $ai['raw_output_text'] ?? null,
                'raw_response' => $ai['raw_response'] ?? null,
                'similar_match' => $similar,
                'gamertag_check' => $gamertagCheck ?? null,
            ],
        ]);
    }

    public function normalizeGamertag(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/u', '', $value) ?: '';
        $value = preg_replace('/[^\pL\pN._#-]+/u', '', $value) ?: '';
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
    }

    private function result(array $overrides): array
    {
        return array_merge([
            'upload_ok' => true,
            'status' => 'review_required',
            'invalid_reason' => null,
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
            'raw_ai_result' => null,
        ], $overrides);
    }

    private function validateImage(string $path, UploadedFile $file): array
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return ['ok' => false, 'message' => __('ui.cup_analysis_upload_invalid')];
        }

        $mime = (string) ($file->getMimeType() ?: $file->getClientMimeType());
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/webp'], true)) {
            return ['ok' => false, 'message' => __('ui.cup_analysis_mime_error')];
        }

        $imageInfo = @getimagesize($path);
        if (! is_array($imageInfo) || empty($imageInfo[0]) || empty($imageInfo[1])) {
            return ['ok' => false, 'message' => __('ui.cup_analysis_unreadable_file')];
        }

        $width = (int) $imageInfo[0];
        $height = (int) $imageInfo[1];
        if ($width < 900 || $height < 500) {
            return ['ok' => false, 'message' => __('ui.cup_analysis_too_small'), 'width' => $width, 'height' => $height];
        }

        $ratio = $width / max(1, $height);
        if ($ratio < 1.45 || $ratio > 2.40) {
            return ['ok' => false, 'message' => __('ui.cup_analysis_full_horizontal'), 'width' => $width, 'height' => $height];
        }

        return ['ok' => true, 'width' => $width, 'height' => $height];
    }

    private function parseSummaryScreen(Cup $cup, string $path, string $mime): array
    {
        if ($cup->aiPromptPreset() === 'awards_first_trophy') {
            return $this->parseAwardsFirstTrophyScreen($cup, $path, $mime);
        }

        if (! $this->aiEnabled()) {
            return ['ok' => false, 'message' => 'ai_disabled'];
        }
        if ($this->aiApiKey() === '') {
            return ['ok' => false, 'message' => 'ai_unconfigured'];
        }

        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            return ['ok' => false, 'message' => 'file_read_failed'];
        }

        $schema = 'Schema: {"screen_type":"success|failed|unknown","valid_extract":true,"kills":0,"bounty":0,"confidence":0.0,"complete_screenshot":true,"kills_source":"hunter|none|monster|unknown","ambiguous_kills":false,"ambiguous_bounty":false,"suspected_tampering":false,"platform_hint":"PC|PlayStation|Xbox|unknown","platform_confidence":0.0,"notes":"short string"}';
        $promptLines = [
            $cup->aiPromptPreset() === 'console_platform'
                ? 'Analyze this Hunt: Showdown mission summary screenshot for an HNT.rocks console mini-cup. Return JSON only.'
                : 'Analyze this Hunt: Showdown mission summary screenshot for HNT.rocks cup scoring. Return JSON only.',
            $schema,
            'Scoring-critical rules:',
            '- valid_extract is true only if the screenshot shows a successful extraction summary. If the hunter died or extraction failed, screen_type=failed and valid_extract=false.',
            '- A run scores only when valid_extract=true and at least one extracted bounty/trophy token is visible. Kills without bounty must not score.',
            '- Bounty/trophy tokens are the primary objective. Read the extracted bounty/trophy token value from the bottom-right value in the mission-summary 2x2 stat grid and clamp to 0..4.',
            '- If the bounty/trophy token value is hidden, cropped, unclear, not native HUD, or you cannot distinguish it from another stat, set bounty=0, ambiguous_bounty=true, confidence<0.65.',
            '- Hunter/player kills are secondary bonus points. Read only the bottom-left hunter/player kills row. German: Getötete Jäger. English: Killed Hunters. If this row is 0 or +0, kills=0 even if monster kills are greater than 0.',
            '- Top-right monster/AI kills never count as hunter kills. If a non-zero number belongs to the monster row, set kills_source=monster and kills=0.',
            '- If you cannot distinguish hunter kills from monster kills, set kills=0, ambiguous_kills=true, confidence<0.65.',
            '- The upload must be a full uncropped horizontal mission summary screenshot. Cropped/zoomed/square/portrait/cut off/hidden layout means complete_screenshot=false, screen_type=unknown, valid_extract=false, kills=0, bounty=0, confidence<0.65.',
            '- Detect edited/manipulated screenshots. If any digit, plus sign, row label, icon, or value looks overpainted, inserted, cloned, sharpened differently, misaligned, brighter/darker, inconsistent spacing, or not native HUD, suspected_tampering=true, screen_type=unknown, valid_extract=false, kills=0, bounty=0, confidence<0.65.',
            '- The screenshot may be German, English, or another supported language. Position and icons are primary, text is secondary.',
            '- Always return platform_hint. Use PC, PlayStation, Xbox, or unknown. If the platform is not visually inferable, return unknown with low platform_confidence.',
        ];

        if ($cup->aiPromptPreset() === 'console_platform') {
            $allowedPlatforms = $cup->allowedPlatforms();
            $promptLines[] = 'Console mini-cup platform check:';
            $promptLines[] = '- Try to infer platform from visible controller button prompts, console UI elements, platform-specific overlays, or button glyphs.';
            $promptLines[] = '- PlayStation indicators include cross/circle/square/triangle button glyphs or PS-style prompts.';
            $promptLines[] = '- Xbox indicators include A/B/X/Y prompts or Xbox-style controller glyphs.';
            $promptLines[] = '- Keyboard/mouse prompts, PC window/launcher overlays, or Steam-style UI should be platform_hint=PC when visible.';
            $promptLines[] = '- Allowed platforms for this cup: '.($allowedPlatforms === [] ? 'any' : implode(', ', $allowedPlatforms)).'.';
            $promptLines[] = '- Do not mark the run invalid only because platform is unknown. Platform uncertainty should be handled as manual review by the site.';
        }

        $prompt = implode("\n", $promptLines);

        $request = $this->aiJsonRequest([
            ['type' => 'input_text', 'text' => $prompt],
            ['type' => 'input_image', 'image_url' => 'data:'.$mime.';base64,'.base64_encode($bytes)],
        ]);
        if (! $request['ok']) {
            return $request;
        }

        $parsed = is_array($request['parsed'] ?? null) ? $request['parsed'] : [];
        $screenType = trim((string) ($parsed['screen_type'] ?? 'unknown'));
        if (! in_array($screenType, ['success', 'failed', 'unknown'], true)) {
            $screenType = 'unknown';
        }
        $killsSource = trim((string) ($parsed['kills_source'] ?? 'unknown'));
        if (! in_array($killsSource, ['hunter', 'none', 'monster', 'unknown'], true)) {
            $killsSource = 'unknown';
        }

        $validExtract = (bool) ($parsed['valid_extract'] ?? false);
        $platformHint = $cup->normalizePlatform((string) ($parsed['platform_hint'] ?? '')) ?: 'unknown';
        $platformConfidence = max(0.0, min(1.0, (float) ($parsed['platform_confidence'] ?? 0)));
        $kills = max(0, min(99, (int) ($parsed['kills'] ?? 0)));
        $bounty = max(0, min(4, (int) ($parsed['bounty'] ?? 0)));
        $ambiguous = ! empty($parsed['ambiguous_kills']);
        $ambiguousBounty = ! empty($parsed['ambiguous_bounty']);
        $tampering = ! empty($parsed['suspected_tampering']);
        $confidence = max(0.0, min(1.0, (float) ($parsed['confidence'] ?? 0)));
        $complete = (bool) ($parsed['complete_screenshot'] ?? true);

        if ($killsSource === 'monster' || $killsSource === 'none') {
            $kills = 0;
        }
        if (($ambiguous || $ambiguousBounty) && $confidence > 0.64) {
            $confidence = 0.64;
        }

        $focus = $this->parseHunterRowFocus($path, $mime);
        $focusMeta = null;
        if (! empty($focus['ok'])) {
            $focusMeta = [
                'raw_text' => (string) ($focus['raw_text'] ?? ''),
                'has_leading_plus' => ! empty($focus['has_leading_plus']),
                'numeric_value' => $focus['numeric_value'] ?? null,
                'looks_original' => array_key_exists('looks_original', $focus) ? ! empty($focus['looks_original']) : null,
                'tampering_suspected' => ! empty($focus['tampering_suspected']),
                'confidence' => (float) ($focus['confidence'] ?? 0),
                'notes' => trim((string) ($focus['notes'] ?? '')),
            ];
            $raw = trim((string) ($focus['raw_text'] ?? ''));
            $value = $focus['numeric_value'] ?? null;
            if (! empty($focus['tampering_suspected']) || (array_key_exists('looks_original', $focus) && ! $focus['looks_original'])) {
                $tampering = true;
            }
            if ($raw !== '' && ! preg_match('/^\+\d{1,2}$/', $raw)) {
                $tampering = true;
            }
            if ($value !== null && empty($focus['has_leading_plus'])) {
                $tampering = true;
            }
            if ($killsSource === 'hunter' && $value !== null) {
                if ($kills !== (int) $value) {
                    $ambiguous = true;
                    $confidence = min($confidence, 0.64);
                }
                $kills = max(0, min(99, (int) $value));
            }
            if ((float) ($focus['confidence'] ?? 0) > 0 && (float) $focus['confidence'] < 0.65) {
                $confidence = min($confidence, 0.64);
            }
        }

        $bountyFocus = $this->parseBountyTokenFocus($path, $mime);
        $bountyFocusMeta = null;
        if (! empty($bountyFocus['ok'])) {
            $bountyFocusMeta = [
                'raw_text' => (string) ($bountyFocus['raw_text'] ?? ''),
                'numeric_value' => $bountyFocus['numeric_value'] ?? null,
                'looks_original' => array_key_exists('looks_original', $bountyFocus) ? ! empty($bountyFocus['looks_original']) : null,
                'tampering_suspected' => ! empty($bountyFocus['tampering_suspected']),
                'ambiguous_bounty' => ! empty($bountyFocus['ambiguous_bounty']),
                'confidence' => (float) ($bountyFocus['confidence'] ?? 0),
                'notes' => trim((string) ($bountyFocus['notes'] ?? '')),
            ];

            $value = $bountyFocus['numeric_value'] ?? null;

            if (! empty($bountyFocus['tampering_suspected']) || (array_key_exists('looks_original', $bountyFocus) && ! $bountyFocus['looks_original'])) {
                $tampering = true;
            }

            if (! empty($bountyFocus['ambiguous_bounty'])) {
                $ambiguousBounty = true;
                $confidence = min($confidence, 0.64);
            }

            if ($value !== null) {
                if ($bounty !== (int) $value) {
                    $ambiguousBounty = true;
                    $confidence = min($confidence, 0.64);
                }

                $bounty = max(0, min(4, (int) $value));
            }

            if ((float) ($bountyFocus['confidence'] ?? 0) > 0 && (float) $bountyFocus['confidence'] < 0.65) {
                $confidence = min($confidence, 0.64);
                $ambiguousBounty = true;
            }
        }

        $platformReviewReason = null;
        if ($cup->aiPromptPreset() === 'console_platform') {
            $allowedPlatforms = $cup->allowedPlatforms();
            if ($allowedPlatforms !== [] && $platformHint !== 'unknown' && $platformConfidence >= 0.70 && ! in_array($platformHint, $allowedPlatforms, true)) {
                $platformReviewReason = 'platform_mismatch';
            } elseif ($allowedPlatforms !== [] && ($platformHint === 'unknown' || $platformConfidence < 0.55)) {
                $platformReviewReason = 'platform_unclear';
            }
        }

        if ($tampering) {
            $screenType = 'unknown';
            $validExtract = false;
            $kills = 0;
            $bounty = 0;
            $confidence = min($confidence, 0.64);
        }

        return [
            'ok' => true,
            'screen_type' => $screenType,
            'valid_extract' => $validExtract,
            'kills' => $kills,
            'bounty' => $bounty,
            'kills_source' => $killsSource,
            'ambiguous_kills' => $ambiguous,
            'ambiguous_bounty' => $ambiguousBounty,
            'suspected_tampering' => $tampering,
            'confidence' => $confidence,
            'complete_screenshot' => $complete,
            'platform_hint' => $platformHint,
            'platform_confidence' => $platformConfidence,
            'platform_review_reason' => $platformReviewReason,
            'notes' => trim((string) ($parsed['notes'] ?? '')),
            'focus_check' => $focusMeta,
            'bounty_focus_check' => $bountyFocusMeta,
            'raw_response' => $request['raw_response'] ?? null,
            'raw_output_text' => $request['raw_output_text'] ?? null,
            'normalized' => [
                'screen_type' => $screenType,
                'valid_extract' => $validExtract,
                'kills' => $kills,
                'bounty' => $bounty,
                'kills_source' => $killsSource,
                'ambiguous_kills' => $ambiguous,
                'ambiguous_bounty' => $ambiguousBounty,
                'suspected_tampering' => $tampering,
                'confidence' => $confidence,
                'complete_screenshot' => $complete,
                'platform_hint' => $platformHint,
                'platform_confidence' => $platformConfidence,
                'platform_review_reason' => $platformReviewReason,
                'focus_check' => $focusMeta,
                'bounty_focus_check' => $bountyFocusMeta,
            ],
        ];
    }

    private function parseAwardsFirstTrophyScreen(Cup $cup, string $path, string $mime): array
    {
        if (! $this->aiEnabled()) {
            return ['ok' => false, 'message' => 'ai_disabled'];
        }
        if ($this->aiApiKey() === '') {
            return ['ok' => false, 'message' => 'ai_unconfigured'];
        }

        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') {
            return ['ok' => false, 'message' => 'file_read_failed'];
        }

        $schema = 'Schema: {"screen_type":"success|failed|unknown","valid_extract":true,"has_first_trophy_extraction":true,"has_killed_hunters_card":true,"kills":0,"bounty":1,"confidence":0.0,"complete_screenshot":true,"kills_source":"hunter|none|monster|unknown","ambiguous_kills":false,"ambiguous_bounty":false,"suspected_tampering":false,"notes":"short string"}';
        $prompt = implode("\n", [
            'Analyze this Hunt: Showdown Missionsdetails / Mission Details awards screen for the HNT.rocks Summer Trio Cup. Return JSON only.',
            $schema,
            'This preset is NOT the classic match-summary grid. It expects the awards/accolades card screen after the match.',
            'Scoring-critical rules:',
            '- valid_extract is true only if a visible accolade card says German "Erste Trophäen-Extraktion" or equivalent English "First Bounty Extraction" / "First Trophy Extraction".',
            '- This card is the required proof that the hunter extracted alive with at least one bounty/trophy. If the card is not clearly visible, valid_extract=false, bounty=0, screen_type=failed or unknown.',
            '- bounty must be 1 when valid_extract=true. Do not count additional tokens, Grand Slam, Running the Gauntlet, Contract Status, boss type, money, XP, clues or monster kills for this preset.',
            '- Read team hunter kills only from a visible accolade card labeled German "Getötete Jäger" or English "Killed Hunters". Return its numeric value as kills.',
            '- If the Killed Hunters / Getötete Jäger card is missing or unclear, set kills=0, ambiguous_kills=true, confidence<0.65.',
            '- Monster kills, AI kills, bounty points, XP, cash, event points, contract status points and other accolade numbers never count as hunter kills.',
            '- The upload should be a full uncropped horizontal awards/accolades screenshot. Cropped/zoomed/portrait/cut-off/composite screenshots mean complete_screenshot=false, valid_extract=false, kills=0, bounty=0, confidence<0.65.',
            '- Detect edited/manipulated screenshots. If any card title, digit, score, icon, or value looks inserted, overpainted, cloned, misaligned, inconsistent, brighter/darker, or not native HUD, suspected_tampering=true and confidence<0.65.',
            '- The screenshot may be German or English. Card title meaning and card visual context are primary. Do not guess hidden cards that require scrolling.',
        ]);

        $request = $this->aiJsonRequest([
            ['type' => 'input_text', 'text' => $prompt],
            ['type' => 'input_image', 'image_url' => 'data:'.$mime.';base64,'.base64_encode($bytes)],
        ]);
        if (! $request['ok']) {
            return $request;
        }

        $parsed = is_array($request['parsed'] ?? null) ? $request['parsed'] : [];
        $hasFirstTrophy = ! empty($parsed['has_first_trophy_extraction']);
        $hasKilledHuntersCard = ! empty($parsed['has_killed_hunters_card']);
        $validExtract = ! empty($parsed['valid_extract']) && $hasFirstTrophy;
        $screenType = trim((string) ($parsed['screen_type'] ?? ($validExtract ? 'success' : 'unknown')));
        if (! in_array($screenType, ['success', 'failed', 'unknown'], true)) {
            $screenType = $validExtract ? 'success' : 'unknown';
        }

        $killsSource = trim((string) ($parsed['kills_source'] ?? ($hasKilledHuntersCard ? 'hunter' : 'unknown')));
        if (! in_array($killsSource, ['hunter', 'none', 'monster', 'unknown'], true)) {
            $killsSource = 'unknown';
        }

        $kills = max(0, min(99, (int) ($parsed['kills'] ?? 0)));
        $bounty = $validExtract ? 1 : 0;
        $ambiguousKills = ! empty($parsed['ambiguous_kills']) || ! $hasKilledHuntersCard;
        $ambiguousBounty = false;
        $tampering = ! empty($parsed['suspected_tampering']);
        $confidence = max(0.0, min(1.0, (float) ($parsed['confidence'] ?? 0)));
        $complete = (bool) ($parsed['complete_screenshot'] ?? true);

        if ($killsSource !== 'hunter') {
            $kills = 0;
        }
        if ($ambiguousKills && $confidence > 0.64) {
            $confidence = 0.64;
        }
        if (! $validExtract || $tampering || ! $complete) {
            $bounty = 0;
        }
        if ($tampering) {
            $screenType = 'unknown';
            $validExtract = false;
            $kills = 0;
            $confidence = min($confidence, 0.64);
        }

        return [
            'ok' => true,
            'screen_type' => $validExtract ? 'success' : $screenType,
            'valid_extract' => $validExtract,
            'kills' => $kills,
            'bounty' => $bounty,
            'kills_source' => $killsSource,
            'ambiguous_kills' => $ambiguousKills,
            'ambiguous_bounty' => $ambiguousBounty,
            'suspected_tampering' => $tampering,
            'confidence' => $confidence,
            'complete_screenshot' => $complete,
            'platform_hint' => 'unknown',
            'platform_confidence' => 0.0,
            'platform_review_reason' => null,
            'notes' => trim((string) ($parsed['notes'] ?? '')),
            'raw_response' => $request['raw_response'] ?? null,
            'raw_output_text' => $request['raw_output_text'] ?? null,
            'normalized' => [
                'preset' => 'awards_first_trophy',
                'screen_type' => $validExtract ? 'success' : $screenType,
                'valid_extract' => $validExtract,
                'has_first_trophy_extraction' => $hasFirstTrophy,
                'has_killed_hunters_card' => $hasKilledHuntersCard,
                'kills' => $kills,
                'bounty' => $bounty,
                'kills_source' => $killsSource,
                'ambiguous_kills' => $ambiguousKills,
                'ambiguous_bounty' => $ambiguousBounty,
                'suspected_tampering' => $tampering,
                'confidence' => $confidence,
                'complete_screenshot' => $complete,
            ],
        ];
    }


    private function parseHunterRowFocus(string $path, string $mime): array
    {
        $row = $this->createRelativeCropPng($path, 0.04, 0.18, 0.42, 0.46);
        $value = $this->createRelativeCropPng($path, 0.18, 0.26, 0.36, 0.43);
        if (! $row || ! $value) {
            if ($row) @unlink($row);
            if ($value) @unlink($value);
            return ['ok' => false, 'message' => 'focus_crop_failed'];
        }
        try {
            $fullBytes = @file_get_contents($path);
            $rowBytes = @file_get_contents($row);
            $valueBytes = @file_get_contents($value);
            if (! is_string($fullBytes) || ! is_string($rowBytes) || ! is_string($valueBytes)) {
                return ['ok' => false, 'message' => 'focus_read_failed'];
            }
            $prompt = implode("\n", [
                'Analyze the Hunt: Showdown hunter-kills row using all provided images together. Return JSON only.',
                'Image 1 full screenshot. Image 2 left mission panel/hunter kills row. Image 3 hunter kills value.',
                'Schema: {"raw_text":"","has_leading_plus":false,"numeric_value":null,"looks_original":true,"tampering_suspected":false,"confidence":0.0,"notes":"short string"}',
                'Read only the bottom-left hunter kills row value. Preserve leading plus in raw_text. Valid examples: +0, +9, +12.',
                'If plus sign is not visible, has_leading_plus=false. If inserted digit, missing plus, inconsistent spacing/baseline/anti-aliasing/font, or not native HUD, tampering_suspected=true and looks_original=false. If uncertain, flag tampering.',
            ]);
            $request = $this->aiJsonRequest([
                ['type' => 'input_text', 'text' => $prompt],
                ['type' => 'input_image', 'image_url' => 'data:'.$mime.';base64,'.base64_encode($fullBytes)],
                ['type' => 'input_image', 'image_url' => 'data:image/png;base64,'.base64_encode($rowBytes)],
                ['type' => 'input_image', 'image_url' => 'data:image/png;base64,'.base64_encode($valueBytes)],
            ]);
            if (! $request['ok']) return $request;
            $p = is_array($request['parsed'] ?? null) ? $request['parsed'] : [];
            $num = $p['numeric_value'] ?? null;
            return [
                'ok' => true,
                'raw_text' => preg_replace('/\s+/', '', trim((string) ($p['raw_text'] ?? ''))),
                'has_leading_plus' => ! empty($p['has_leading_plus']),
                'numeric_value' => is_numeric($num) ? max(0, min(99, (int) $num)) : null,
                'looks_original' => array_key_exists('looks_original', $p) ? ! empty($p['looks_original']) : true,
                'tampering_suspected' => ! empty($p['tampering_suspected']),
                'confidence' => max(0.0, min(1.0, (float) ($p['confidence'] ?? 0))),
                'notes' => trim((string) ($p['notes'] ?? '')),
                'raw_response' => $request['raw_response'] ?? null,
                'raw_output_text' => $request['raw_output_text'] ?? null,
            ];
        } finally {
            @unlink($row);
            @unlink($value);
        }
    }


    private function parseBountyTokenFocus(string $path, string $mime): array
    {
        // Keep one wide context crop, but use tight row/value crops for the actual read.
        // The older crop included Hunter Progress XP below the row; that could make the AI read 0/null or mark the bounty value ambiguous.
        $row = $this->createRelativeCropPng($path, 0.22, 0.18, 0.50, 0.46);
        $tightRow = $this->createRelativeCropPng($path, 0.265, 0.260, 0.505, 0.355);
        $value = $this->createRelativeCropPng($path, 0.420, 0.255, 0.505, 0.350);
        if (! $row || ! $tightRow || ! $value) {
            if ($row) @unlink($row);
            if ($tightRow) @unlink($tightRow);
            if ($value) @unlink($value);
            return ['ok' => false, 'message' => 'bounty_focus_crop_failed'];
        }

        try {
            $fullBytes = @file_get_contents($path);
            $rowBytes = @file_get_contents($row);
            $tightRowBytes = @file_get_contents($tightRow);
            $valueBytes = @file_get_contents($value);
            if (! is_string($fullBytes) || ! is_string($rowBytes) || ! is_string($tightRowBytes) || ! is_string($valueBytes)) {
                return ['ok' => false, 'message' => 'bounty_focus_read_failed'];
            }

            $prompt = implode("\n", [
                'Analyze the Hunt: Showdown extracted bounty/trophy token value using all provided images together. Return JSON only.',
                'Image 1 full screenshot. Image 2 wider right-side mission grid context. Image 3 tight Bounty Token row. Image 4 tight value crop.',
                'Schema: {"raw_text":"","numeric_value":null,"looks_original":true,"tampering_suspected":false,"ambiguous_bounty":false,"confidence":0.0,"notes":"short string"}',
                'Read only the value on the same row as the Bounty Token / trophy-token label in the bottom-right statistic of the 2x2 mission-summary grid.',
                'The correct value is usually shown at the far right of the Bounty Token row, often as +0, +1, +2, +3, or +4. Return numeric_value as 0..4 without the plus sign.',
                'Important: ignore Hunter Progress XP, Bloodline XP, money, event points, levels, monster kills, hunter kills, and all numbers below the Bounty Token row.',
                'If Image 3 clearly shows the Bounty Token row and Image 4 clearly shows +1/+2/+3/+4, use that value and set ambiguous_bounty=false.',
                'Only set ambiguous_bounty=true when the tight row/value crop does not contain the Bounty Token row or the value is genuinely unreadable.',
                'If inserted digit, inconsistent spacing/baseline/anti-aliasing/font, or not native HUD, tampering_suspected=true and looks_original=false. Do not mark normal Hunt UI grain/compression as tampering.',
            ]);

            $request = $this->aiJsonRequest([
                ['type' => 'input_text', 'text' => $prompt],
                ['type' => 'input_image', 'image_url' => 'data:'.$mime.';base64,'.base64_encode($fullBytes)],
                ['type' => 'input_image', 'image_url' => 'data:image/png;base64,'.base64_encode($rowBytes)],
                ['type' => 'input_image', 'image_url' => 'data:image/png;base64,'.base64_encode($tightRowBytes)],
                ['type' => 'input_image', 'image_url' => 'data:image/png;base64,'.base64_encode($valueBytes)],
            ]);
            if (! $request['ok']) return $request;

            $p = is_array($request['parsed'] ?? null) ? $request['parsed'] : [];
            $num = $p['numeric_value'] ?? null;

            return [
                'ok' => true,
                'raw_text' => preg_replace('/\s+/', '', trim((string) ($p['raw_text'] ?? ''))),
                'numeric_value' => is_numeric($num) ? max(0, min(4, (int) $num)) : null,
                'looks_original' => array_key_exists('looks_original', $p) ? ! empty($p['looks_original']) : true,
                'tampering_suspected' => ! empty($p['tampering_suspected']),
                'ambiguous_bounty' => ! empty($p['ambiguous_bounty']),
                'confidence' => max(0.0, min(1.0, (float) ($p['confidence'] ?? 0))),
                'notes' => trim((string) ($p['notes'] ?? '')),
                'raw_response' => $request['raw_response'] ?? null,
                'raw_output_text' => $request['raw_output_text'] ?? null,
            ];
        } finally {
            @unlink($row);
            @unlink($tightRow);
            @unlink($value);
        }
    }


    private function parseBloodlineGamertag(string $path, string $mime): array
    {
        if (! $this->aiEnabled()) {
            return ['ok' => false, 'message' => 'ai_disabled'];
        }
        if ($this->aiApiKey() === '') {
            return ['ok' => false, 'message' => 'ai_unconfigured'];
        }

        $fullBytes = @file_get_contents($path);
        if (! is_string($fullBytes) || $fullBytes === '') {
            return ['ok' => false, 'message' => 'file_read_failed'];
        }

        $crop = $this->createRelativeCropPng($path, 0.06, 0.46, 0.50, 0.68);
        $tightCrop = $this->createRelativeCropPng($path, 0.09, 0.45, 0.45, 0.56);

        try {
            $prompt = implode("\n", [
                'Analyze this Hunt: Showdown mission summary screenshot. Return JSON only.',
                'Read the player gamertag in the Bloodline progress section near the lower-left of the mission summary panel.',
                'It is the name directly above the Bloodline progress bar. German label nearby: Blutlinien-Fortschritt. English label nearby: Bloodline Progress.',
                'Use the focused crops if present. The correct text is a player name, not the hunter name above the Hunter Progress bar.',
                'Do not return labels, XP numbers, hunter names, team names, level numbers, score numbers, or UI captions.',
                'Schema: {"found":true,"raw_name":"","normalized_name":"","confidence":0.0,"notes":"short string"}',
                'If a player name is clearly visible in the Bloodline Progress row, return it even if the screenshot has grain, compression, or low contrast.',
                'Only return found=false when the name is genuinely cropped, hidden, or unreadable.',
                'Preserve the visible gamertag in raw_name. normalized_name should remove spaces and keep letters, numbers, dot, underscore, dash and # only.',
            ]);

            $content = [
                ['type' => 'input_text', 'text' => $prompt],
                ['type' => 'input_image', 'image_url' => 'data:'.$mime.';base64,'.base64_encode($fullBytes)],
            ];

            foreach ([$crop, $tightCrop] as $focusedCrop) {
                if (is_string($focusedCrop) && is_file($focusedCrop)) {
                    $cropBytes = @file_get_contents($focusedCrop);
                    if (is_string($cropBytes) && $cropBytes !== '') {
                        $content[] = ['type' => 'input_image', 'image_url' => 'data:image/png;base64,'.base64_encode($cropBytes)];
                    }
                }
            }

            $request = $this->aiJsonRequest($content);
            if (! $request['ok']) {
                return $request;
            }

            $parsed = is_array($request['parsed'] ?? null) ? $request['parsed'] : [];
            $rawName = preg_replace('/\s+/u', ' ', trim((string) ($parsed['raw_name'] ?? ''))) ?: '';
            $rawName = substr($rawName, 0, 100);
            $normalized = $this->normalizeGamertag((string) ($parsed['normalized_name'] ?? $rawName));
            $confidence = max(0.0, min(1.0, (float) ($parsed['confidence'] ?? 0)));
            $found = ! empty($parsed['found']) && $rawName !== '' && $normalized !== null;

            if (! $found) {
                $confidence = min($confidence, 0.69);
            }

            return [
                'ok' => true,
                'found' => $found,
                'raw_name' => $found ? $rawName : null,
                'normalized_name' => $found ? $normalized : null,
                'confidence' => $confidence,
                'notes' => trim((string) ($parsed['notes'] ?? '')),
                'raw_response' => $request['raw_response'] ?? null,
                'raw_output_text' => $request['raw_output_text'] ?? null,
            ];
        } finally {
            if (is_string($crop)) {
                @unlink($crop);
            }
            if (isset($tightCrop) && is_string($tightCrop)) {
                @unlink($tightCrop);
            }
        }
    }

    private function aiJsonRequest(array $content): array
    {
        try {
            $response = Http::timeout($this->aiTimeoutSeconds())->withToken($this->aiApiKey())->acceptJson()->post($this->aiEndpoint(), [
                'model' => $this->aiModel(),
                'input' => [['role' => 'user', 'content' => $content]],
            ]);
        } catch (Throwable $e) {
            Log::warning('Cup AI request failed', ['message' => $e->getMessage()]);
            return ['ok' => false, 'message' => 'request_failed'];
        }
        $decoded = $response->json();
        if (! is_array($decoded)) return ['ok' => false, 'message' => 'invalid_json', 'http_code' => $response->status(), 'raw' => $response->body()];
        if ($response->failed()) return ['ok' => false, 'message' => 'http_'.$response->status(), 'http_code' => $response->status(), 'raw' => $decoded];
        $text = $this->openAiOutputText($decoded);
        $parsed = $this->extractJsonObject($text);
        if (! is_array($parsed)) return ['ok' => false, 'message' => 'output_parse_failed', 'raw_response' => $decoded, 'raw_output_text' => $text];
        return ['ok' => true, 'parsed' => $parsed, 'raw_response' => $decoded, 'raw_output_text' => $text];
    }

    private function openAiOutputText(array $payload): string
    {
        $text = trim((string) ($payload['output_text'] ?? ''));
        if ($text !== '') return $text;
        $parts = [];
        foreach (($payload['output'] ?? []) as $item) {
            foreach (($item['content'] ?? []) as $content) {
                $part = trim((string) ($content['text'] ?? ''));
                if ($part !== '') $parts[] = $part;
            }
        }
        return trim(implode("\n", $parts));
    }

    private function extractJsonObject(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') return null;
        $decoded = json_decode($text, true);
        if (is_array($decoded)) return $decoded;
        if (preg_match('/\{.*\}/s', $text, $m)) {
            $decoded = json_decode((string) $m[0], true);
            if (is_array($decoded)) return $decoded;
        }
        return null;
    }

    private function computePHash(string $path): ?string
    {
        if (! is_file($path) || ! is_readable($path) || ! function_exists('imagecreatefromstring')) return null;
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') return null;
        $src = @imagecreatefromstring($bytes);
        if (! $src) return null;
        $target = imagecreatetruecolor(8, 8);
        if (! $target) { imagedestroy($src); return null; }
        imagecopyresampled($target, $src, 0, 0, 0, 0, 8, 8, imagesx($src), imagesy($src));
        imagedestroy($src);
        $values = []; $sum = 0;
        for ($y = 0; $y < 8; $y++) for ($x = 0; $x < 8; $x++) {
            $rgb = imagecolorat($target, $x, $y);
            $gray = (int) round((($rgb >> 16 & 255) * .299) + (($rgb >> 8 & 255) * .587) + (($rgb & 255) * .114));
            $values[] = $gray; $sum += $gray;
        }
        imagedestroy($target);
        if ($values === []) return null;
        $avg = $sum / count($values); $bits = '';
        foreach ($values as $v) $bits .= $v >= $avg ? '1' : '0';
        $hex = '';
        for ($i = 0; $i < 64; $i += 4) $hex .= dechex(bindec(substr($bits, $i, 4)));
        return str_pad(strtolower($hex), 16, '0', STR_PAD_LEFT);
    }

    private function findSimilarSubmission(Cup $cup, string $phash, ?int $ignoreSubmissionId = null): ?array
    {
        if ($this->similarPHashDistanceThreshold() < 0) return null;

        $best = null; $bestDistance = null;
        $subs = CupSubmission::query()
            ->where('cup_id', $cup->id)
            ->whereNotNull('phash')
            ->when($ignoreSubmissionId, fn ($query) => $query->whereKeyNot($ignoreSubmissionId))
            ->latest('submitted_at')
            ->limit(250)
            ->get(['id', 'phash']);
        foreach ($subs as $sub) {
            $distance = $this->hammingDistance($phash, (string) $sub->phash);
            if ($distance === null || $distance > $this->similarPHashDistanceThreshold()) continue;
            if ($best === null || $distance < $bestDistance) {
                $best = ['similar_submission_id' => $sub->id, 'phash_distance' => $distance];
                $bestDistance = $distance;
            }
        }
        return $best;
    }

    private function similarPHashDistanceThreshold(): int
    {
        return max(-1, min(64, (int) env('HH_CUP_SIMILAR_PHASH_DISTANCE', -1)));
    }

    private function hammingDistance(string $leftHex, string $rightHex): ?int
    {
        $leftHex = strtolower(trim($leftHex)); $rightHex = strtolower(trim($rightHex));
        if ($leftHex === '' || $rightHex === '' || strlen($leftHex) !== strlen($rightHex)) return null;
        $distance = 0;
        for ($i = 0, $len = strlen($leftHex); $i < $len; $i++) {
            $xor = hexdec($leftHex[$i]) ^ hexdec($rightHex[$i]);
            while ($xor > 0) { $distance += $xor & 1; $xor >>= 1; }
        }
        return $distance;
    }

    private function createRelativeCropPng(string $path, float $leftRatio, float $topRatio, float $rightRatio, float $bottomRatio): ?string
    {
        if (! is_file($path) || ! is_readable($path) || ! function_exists('imagecreatefromstring')) return null;
        $bytes = @file_get_contents($path);
        if (! is_string($bytes) || $bytes === '') return null;
        $src = @imagecreatefromstring($bytes);
        if (! $src) return null;
        $w = imagesx($src); $h = imagesy($src);
        if ($w <= 0 || $h <= 0) { imagedestroy($src); return null; }
        $left = (int) max(0, min($w - 1, floor($w * max(0.0, min(1.0, $leftRatio)))));
        $top = (int) max(0, min($h - 1, floor($h * max(0.0, min(1.0, $topRatio)))));
        $right = (int) max($left + 1, min($w, ceil($w * max(0.0, min(1.0, $rightRatio)))));
        $bottom = (int) max($top + 1, min($h, ceil($h * max(0.0, min(1.0, $bottomRatio)))));
        $cw = $right - $left; $ch = $bottom - $top;
        if ($cw < 32 || $ch < 32) { imagedestroy($src); return null; }
        $crop = imagecreatetruecolor($cw, $ch);
        if (! $crop) { imagedestroy($src); return null; }
        imagealphablending($crop, false); imagesavealpha($crop, true);
        $transparent = imagecolorallocatealpha($crop, 0, 0, 0, 127);
        imagefilledrectangle($crop, 0, 0, $cw, $ch, $transparent);
        imagecopy($crop, $src, 0, 0, $left, $top, $cw, $ch);
        $tmp = tempnam(sys_get_temp_dir(), 'hhcup_');
        if (! is_string($tmp) || $tmp === '') { imagedestroy($crop); imagedestroy($src); return null; }
        $png = $tmp.'.png'; @unlink($tmp); $ok = @imagepng($crop, $png);
        imagedestroy($crop); imagedestroy($src);
        if (! $ok || ! is_file($png)) { @unlink($png); return null; }
        return $png;
    }

    private function aiApiKey(): string { return trim((string) (env('HH_CUP_OPENAI_API_KEY') ?: env('HH_OPENAI_API_KEY') ?: env('OPENAI_API_KEY') ?: '')); }
    private function aiModel(): string { return trim((string) (env('HH_CUP_OPENAI_MODEL') ?: 'gpt-5-mini')); }
    private function aiEndpoint(): string { return trim((string) (env('HH_CUP_OPENAI_ENDPOINT') ?: 'https://api.openai.com/v1/responses')); }
    private function aiTimeoutSeconds(): int { return max(5, min(120, (int) (env('HH_CUP_OPENAI_TIMEOUT') ?: 45))); }
    private function aiEnabled(): bool
    {
        $value = env('HH_CUP_AI_ENABLED', true);
        return is_bool($value) ? $value : in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }
}
