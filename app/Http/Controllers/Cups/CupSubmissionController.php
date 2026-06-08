<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupSubmission;
use App\Models\CupTeam;
use App\Models\User;
use App\Services\Cups\CupSubmissionAnalysisService;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class CupSubmissionController extends Controller
{
    public function store(Request $request, Cup $cup, CupTeam $team, MediaService $mediaService, CupSubmissionAnalysisService $analysisService, GamificationService $gamification, NotificationService $notifications): RedirectResponse|JsonResponse
    {
        abort_unless((int) $team->cup_id === (int) $cup->id, 404);

        if (! $team->hasMember($request->user())) {
            return $this->cupSubmissionError($request, $cup->isSoloLeaderboard() ? __('ui.cup_submission_error_only_participant') : __('ui.cup_submission_error_only_members'));
        }
        if (! $cup->isSubmissionOpen()) {
            return $this->cupSubmissionError($request, $cup->submissionClosedReason());
        }
        if ($team->status !== 'active') {
            return $this->cupSubmissionError($request, $team->isDisqualified() ? __('ui.cup_submission_error_disqualified') : __('ui.cup_submit_only_active'));
        }

        $team->setRelation('cup', $cup);
        $team->loadMissing('members');
        if (! $cup->isSoloLeaderboard()) {
            if (! $team->isCaptain($request->user())) {
                return $this->cupSubmissionError($request, __('ui.cup_submission_error_only_captain'));
            }

            if (! $team->isComplete()) {
                return $this->cupSubmissionError($request, __('ui.cup_submission_error_team_incomplete', [
                    'count' => $team->activeMembersCount(),
                    'size' => $team->requiredMembersCount(),
                ]));
            }
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! $eligibility['eligible']) {
            return $this->cupSubmissionError($request, implode(' ', $eligibility['messages']));
        }

        $maxUploads = $cup->maxSubmissionsPerParticipant();
        if ($maxUploads !== null) {
            $usedUploads = CupSubmission::query()->where('cup_team_id', $team->id)->count();
            if ($usedUploads >= $maxUploads) {
                return $this->cupSubmissionError($request, __('ui.cup_submission_error_limit_reached', ['count' => $maxUploads]));
            }
        }

        $cooldownMinutes = max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30));
        if ($cooldownMinutes > 0 && $team->last_submission_at && $team->last_submission_at->gt(now()->subMinutes($cooldownMinutes))) {
            return $this->cupSubmissionError($request, __('ui.cup_submission_error_cooldown', ['minutes' => $cooldownMinutes]));
        }

        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:1200'],
            'screenshot' => ['required', 'image', 'max:'.config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)],
        ]);

        try {
            $mediaService->assertAllowed($request->file('screenshot'), $request->user(), 'cups/screenshots');

            $analysis = $analysisService->analyze($cup, $request->file('screenshot'));
            if (! ($analysis['upload_ok'] ?? true)) {
                return $this->cupSubmissionError($request, (string) ($analysis['message'] ?? __('ui.cup_submission_invalid_default')), withInput: true);
            }

            $submission = DB::transaction(function () use ($request, $cup, $team, $mediaService, $validated, &$analysis, $analysisService): CupSubmission {
                $lockedTeam = CupTeam::query()->whereKey($team->id)->with(['cup', 'members'])->lockForUpdate()->firstOrFail();
                abort_unless($lockedTeam->status === 'active', 403);
                abort_unless($cup->isSubmissionOpen(), 403);
                abort_unless($cup->isSoloLeaderboard() || ($lockedTeam->isCaptain($request->user()) && $lockedTeam->isComplete()), 403);
                $analysis = $this->applyGamertagConsistency($analysisService, $analysis, $lockedTeam);
                $asset = $mediaService->store($request->file('screenshot'), $request->user(), 'cups/screenshots', [
                    'disk' => (string) config('hunthub.cups.submission_screenshot_disk', 'local'),
                    'visibility' => 'private',
                    'metadata' => [
                        'cup_ai_status' => $analysis['status'] ?? null,
                        'cup_ai_invalid_reason' => $analysis['invalid_reason'] ?? null,
                        'cup_ai_sha256_hash' => $analysis['sha256_hash'] ?? null,
                        'cup_ai_phash' => $analysis['phash'] ?? null,
                        'cup_ai_gamertag' => $analysis['ai_gamertag'] ?? null,
                        'cup_ai_gamertag_mismatch' => $analysis['ai_gamertag_mismatch'] ?? false,
                    ],
                ]);

                $submission = CupSubmission::create([
                    'cup_id' => $cup->id,
                    'cup_team_id' => $lockedTeam->id,
                    'submitted_by' => $request->user()->id,
                    'screenshot_media_asset_id' => $asset->id,
                    'kills' => (int) ($analysis['kills'] ?? 0),
                    'bounty_tokens' => min(4, (int) ($analysis['bounty_tokens'] ?? 0)),
                    'extracted' => (bool) ($analysis['extracted'] ?? false),
                    'points' => (int) ($analysis['points'] ?? 0),
                    'status' => (string) ($analysis['status'] ?? 'review_required'),
                    'note' => $validated['note'] ?? null,
                    'screen_type' => $analysis['screen_type'] ?? null,
                    'ai_valid_extract' => (bool) ($analysis['ai_valid_extract'] ?? false),
                    'ai_kills' => (int) ($analysis['ai_kills'] ?? 0),
                    'ai_bounty_tokens' => min(4, (int) ($analysis['ai_bounty_tokens'] ?? 0)),
                    'ai_confidence' => $analysis['ai_confidence'] ?? null,
                    'ai_complete_screenshot' => $analysis['ai_complete_screenshot'] ?? null,
                    'ai_kills_source' => $analysis['ai_kills_source'] ?? null,
                    'ai_ambiguous_kills' => (bool) ($analysis['ai_ambiguous_kills'] ?? false),
                    'ai_suspected_tampering' => (bool) ($analysis['ai_suspected_tampering'] ?? false),
                    'ai_gamertag' => $analysis['ai_gamertag'] ?? null,
                    'ai_gamertag_normalized' => $analysis['ai_gamertag_normalized'] ?? null,
                    'ai_gamertag_confidence' => $analysis['ai_gamertag_confidence'] ?? null,
                    'ai_gamertag_mismatch' => (bool) ($analysis['ai_gamertag_mismatch'] ?? false),
                    'ai_invalid_reason' => $analysis['invalid_reason'] ?? null,
                    'ai_raw_result' => $analysis['raw_ai_result'] ?? null,
                    'sha256_hash' => $analysis['sha256_hash'] ?? null,
                    'phash' => $analysis['phash'] ?? null,
                    'image_width' => $analysis['image_width'] ?? null,
                    'image_height' => $analysis['image_height'] ?? null,
                    'submitted_at' => now(),
                    'processed_at' => now(),
                ]);

                $mediaService->attach($asset, $submission);
                $lockedTeam->forceFill(['last_submission_at' => now()])->save();
                $this->lockRosterFromSubmission($lockedTeam, $submission);
                $this->recalculateTeamTotals($lockedTeam);

                return $submission;
            });

            $gamification->award($request->user(), 'cup_submission_created', source: $submission, description: __('ui.cup_submission_gamification_created'));

            if ($submission->status === 'review_required') {
                $notifications->send($cup->owner, $request->user(), 'cup_submission_review_required', __('ui.cup_submission_notification_review_title'), $cup->isSoloLeaderboard() ? __('ui.cup_submission_notification_review_body_solo', ['player' => $team->displayName()]) : __('ui.cup_submission_notification_review_body', ['team' => $team->name]), route('cups.show.section', [$cup, 'submissions']));
            } elseif ($submission->status === 'processed') {
                $notifications->send($cup->owner, $request->user(), 'cup_submission_processed', __('ui.cup_submission_notification_processed_title'), $cup->isSoloLeaderboard() ? __('ui.cup_submission_notification_processed_body_solo', ['player' => $team->displayName()]) : __('ui.cup_submission_notification_processed_body', ['team' => $team->name]), route('cups.show.section', [$cup, 'submissions']));
            }
        } catch (Throwable $exception) {
            Log::error('Cup submission failed', [
                'cup_id' => $cup->id,
                'team_id' => $team->id,
                'user_id' => $request->user()?->id,
                'message' => $exception->getMessage(),
            ]);

            return $this->cupSubmissionError($request, 'Die Einreichung konnte nicht verarbeitet werden. Bitte prüfe das Server-Log und versuche es danach erneut.', status: 500);
        }

        $resultType = match ($submission->status) {
            'processed', 'approved', 'approved_manual' => 'success',
            'invalid', 'rejected', 'rejected_manual' => 'danger',
            'review_required', 'pending' => 'warning',
            default => 'info',
        };

        $resultTitle = match ($submission->status) {
            'processed' => __('ui.cup_submission_result_auto'),
            'invalid' => __('ui.cup_submission_result_invalid'),
            'review_required' => __('ui.cup_submission_result_review'),
            default => __('ui.cup_submission_result_default'),
        };

        $result = [
            'type' => $resultType,
            'title' => $resultTitle,
            'message' => (string) ($analysis['message'] ?? __('ui.cup_submission_message_default')),
            'status' => $submission->statusLabel(),
            'score' => __('ui.cup_submission_score_full', ['points' => $submission->points, 'kills' => $submission->kills, 'tokens' => $submission->bounty_tokens]),
            'summary' => $submission->resultSummary(),
            'details' => [
                ['label' => 'Punkte', 'value' => (string) $submission->points],
                ['label' => 'Kills', 'value' => (string) $submission->kills],
                ['label' => 'Bounty', 'value' => (string) $submission->bounty_tokens],
                ['label' => 'Extraktion', 'value' => $submission->extracted ? 'Ja' : 'Nein'],
                ['label' => 'Status', 'value' => $submission->statusLabel()],
            ],
        ];

        if ($this->wantsCupSubmissionJson($request)) {
            return response()->json([
                'ok' => true,
                'message' => $result['message'],
                'redirect_url' => route('cups.show.section', [$cup, 'submissions']),
                'result' => $result,
                'submission_id' => $submission->id,
                'estimated_total_seconds' => (int) config('hunthub.cups.submission_analysis_estimated_seconds', 45),
            ]);
        }

        return redirect()->route('cups.show.section', [$cup, 'submissions'])->with('cup_submission_result', $result);
    }

    private function wantsCupSubmissionJson(Request $request): bool
    {
        return $request->expectsJson() || $request->ajax();
    }

    private function cupSubmissionError(Request $request, string $message, int $status = 422, bool $withInput = false): RedirectResponse|JsonResponse
    {
        if ($this->wantsCupSubmissionJson($request)) {
            return response()->json([
                'ok' => false,
                'message' => $message,
                'errors' => ['submission' => [$message]],
            ], $status);
        }

        $redirect = back()->withErrors(['submission' => $message]);
        return $withInput ? $redirect->withInput() : $redirect;
    }

    public function approve(Request $request, Cup $cup, CupSubmission $submission, GamificationService $gamification, NotificationService $notifications): RedirectResponse
    {
        abort_unless($cup->canManage($request->user()), 403);
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);

        if (in_array($submission->status, CupSubmission::scoredStatuses(), true)) return back()->with('status', __('ui.cup_submission_already_scored'));

        DB::transaction(function () use ($request, $cup, $submission): void {
            $kills = max(0, (int) $submission->kills);
            $bountyTokens = max(0, min(4, (int) $submission->bounty_tokens));
            $points = CupSubmission::calculatePointsForCup($cup, $kills, $bountyTokens, true);

            $submission->update([
                'status' => 'approved_manual',
                'extracted' => true,
                'points' => $points,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'processed_at' => now(),
                'review_note' => $request->input('review_note'),
                'ai_invalid_reason' => null,
                'ai_gamertag_mismatch' => false,
            ]);

            $team = $submission->team()->with('cup')->lockForUpdate()->firstOrFail();
            $this->lockTeamGamertagFromSubmission($team, $submission);
            $this->lockRosterFromSubmission($team, $submission);
            $this->recalculateTeamTotals($team);
        });

        $freshSubmission = $submission->fresh(['submitter', 'team', 'cup']) ?? $submission;
        $gamification->award($freshSubmission->submitter, 'cup_submission_approved', source: $freshSubmission, description: __('ui.cup_submission_gamification_approved'));
        $notifications->send($freshSubmission->submitter, $request->user(), 'cup_submission_approved', __('ui.cup_submission_gamification_approved'), __('ui.cup_submission_notification_approved_body', ['cup' => $cup->title]), route('cups.show.section', [$cup, 'submissions']));

        return redirect()->route('cups.show.section', [$cup, 'submissions'])->with('status', __('ui.cup_submission_approved_status'));
    }

    public function reject(Request $request, Cup $cup, CupSubmission $submission, NotificationService $notifications): RedirectResponse
    {
        abort_unless($cup->canManage($request->user()), 403);
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);

        $validated = $request->validate(['review_note' => ['nullable', 'string', 'max:1200']]);

        DB::transaction(function () use ($request, $cup, $submission, $validated): void {
            $submission->update([
                'status' => 'rejected_manual',
                'extracted' => false,
                'points' => 0,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'processed_at' => now(),
                'review_note' => $validated['review_note'] ?? null,
                'ai_invalid_reason' => 'manual_reject',
            ]);

            $team = $submission->team()->lockForUpdate()->firstOrFail();
            $this->recalculateTeamTotals($team);
        });

        $submission->loadMissing('submitter');
        $notifications->send($submission->submitter, $request->user(), 'cup_submission_rejected', __('ui.cup_submission_notification_rejected_title'), __('ui.cup_submission_notification_rejected_body', ['cup' => $cup->title]), route('cups.show.section', [$cup, 'submissions']));

        return redirect()->route('cups.show.section', [$cup, 'submissions'])->with('status', __('ui.cup_submission_rejected_status'));
    }


    public function manualScore(Request $request, Cup $cup, CupSubmission $submission): RedirectResponse
    {
        abort_unless($cup->canManage($request->user()), 403);
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);

        $validated = $request->validate([
            'kills' => ['required', 'integer', 'min:0', 'max:99'],
            'bounty_tokens' => ['required', 'integer', 'min:0', 'max:4'],
            'points' => ['nullable', 'integer', 'min:0', 'max:999'],
            'review_note' => ['nullable', 'string', 'max:1200'],
        ]);

        DB::transaction(function () use ($request, $cup, $submission, $validated): void {
            $kills = max(0, min(99, (int) $validated['kills']));
            $bountyTokens = max(0, min(4, (int) $validated['bounty_tokens']));
            $points = array_key_exists('points', $validated) && $validated['points'] !== null && $validated['points'] !== ''
                ? max(0, min(999, (int) $validated['points']))
                : CupSubmission::calculatePointsForCup($cup, $kills, $bountyTokens, true);

            $raw = is_array($submission->ai_raw_result) ? $submission->ai_raw_result : [];
            $history = is_array($raw['manual_score_overrides'] ?? null) ? $raw['manual_score_overrides'] : [];
            $history[] = [
                'at' => now()->toIso8601String(),
                'by' => $request->user()->id,
                'previous' => [
                    'status' => $submission->status,
                    'kills' => (int) $submission->kills,
                    'bounty_tokens' => (int) $submission->bounty_tokens,
                    'points' => (int) $submission->points,
                ],
                'new' => [
                    'kills' => $kills,
                    'bounty_tokens' => $bountyTokens,
                    'points' => $points,
                ],
            ];
            $raw['manual_score_override'] = end($history) ?: null;
            $raw['manual_score_overrides'] = array_slice($history, -10);

            $submission->forceFill([
                'status' => 'approved_manual',
                'kills' => $kills,
                'bounty_tokens' => $bountyTokens,
                'extracted' => true,
                'points' => $points,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'processed_at' => now(),
                'review_note' => $validated['review_note'] ?? $submission->review_note,
                'ai_invalid_reason' => null,
                'ai_gamertag_mismatch' => false,
                'ai_raw_result' => $raw,
            ])->save();

            $team = $submission->team()->with('cup')->lockForUpdate()->firstOrFail();
            $this->lockTeamGamertagFromSubmission($team, $submission);
            $this->lockRosterFromSubmission($team, $submission);
            $this->recalculateTeamTotals($team);
        });

        return redirect()->route('cups.show.section', [$cup, 'submissions'])->with('status', __('ui.cup_submission_manual_score_saved'));
    }

    public function rescore(Request $request, Cup $cup, CupSubmission $submission, CupSubmissionAnalysisService $analysisService): RedirectResponse
    {
        abort_unless($cup->canManage($request->user()), 403);
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);

        $submission->loadMissing(['screenshot', 'team']);
        $asset = $submission->screenshot;

        if (! $asset) {
            return back()->withErrors(['submission' => __('ui.cup_submission_rescore_missing_file')]);
        }

        $disk = filled($asset->disk) ? (string) $asset->disk : 'public';
        $path = ltrim((string) $asset->path, '/');

        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            return back()->withErrors(['submission' => __('ui.cup_submission_rescore_missing_file')]);
        }

        $tmpPath = tempnam(sys_get_temp_dir(), 'hnt-cup-rescore-');
        if (! is_string($tmpPath) || $tmpPath === '') {
            return back()->withErrors(['submission' => __('ui.cup_submission_rescore_failed')]);
        }

        try {
            $bytes = Storage::disk($disk)->get($path);
            file_put_contents($tmpPath, $bytes);

            $extension = trim((string) $asset->extension) ?: (pathinfo($path, PATHINFO_EXTENSION) ?: 'jpg');
            $mimeType = trim((string) $asset->mime_type) ?: (Storage::disk($disk)->mimeType($path) ?: 'image/jpeg');
            $uploadedFile = new UploadedFile($tmpPath, 'cup-submission-'.$submission->id.'.'.$extension, $mimeType, null, true);

            $analysis = $analysisService->analyze($cup, $uploadedFile, $submission->id);
        } finally {
            @unlink($tmpPath);
        }

        if (! ($analysis['upload_ok'] ?? true)) {
            return back()->withErrors(['submission' => (string) ($analysis['message'] ?? __('ui.cup_submission_rescore_failed'))]);
        }

        DB::transaction(function () use ($request, $submission, &$analysis, $analysisService): void {
            $team = CupTeam::query()->whereKey($submission->cup_team_id)->with('cup')->lockForUpdate()->firstOrFail();
            $analysis = $this->applyGamertagConsistency($analysisService, $analysis, $team);

            $raw = is_array($analysis['raw_ai_result'] ?? null) ? $analysis['raw_ai_result'] : [];
            $raw['rescore'] = [
                'at' => now()->toIso8601String(),
                'by' => $request->user()->id,
                'previous' => [
                    'status' => $submission->status,
                    'kills' => (int) $submission->kills,
                    'bounty_tokens' => (int) $submission->bounty_tokens,
                    'points' => (int) $submission->points,
                    'invalid_reason' => $submission->ai_invalid_reason,
                ],
            ];

            $submission->forceFill([
                'kills' => (int) ($analysis['kills'] ?? 0),
                'bounty_tokens' => min(4, (int) ($analysis['bounty_tokens'] ?? 0)),
                'extracted' => (bool) ($analysis['extracted'] ?? false),
                'points' => (int) ($analysis['points'] ?? 0),
                'status' => (string) ($analysis['status'] ?? 'review_required'),
                'screen_type' => $analysis['screen_type'] ?? null,
                'ai_valid_extract' => (bool) ($analysis['ai_valid_extract'] ?? false),
                'ai_kills' => (int) ($analysis['ai_kills'] ?? 0),
                'ai_bounty_tokens' => min(4, (int) ($analysis['ai_bounty_tokens'] ?? 0)),
                'ai_confidence' => $analysis['ai_confidence'] ?? null,
                'ai_complete_screenshot' => $analysis['ai_complete_screenshot'] ?? null,
                'ai_kills_source' => $analysis['ai_kills_source'] ?? null,
                'ai_ambiguous_kills' => (bool) ($analysis['ai_ambiguous_kills'] ?? false),
                'ai_suspected_tampering' => (bool) ($analysis['ai_suspected_tampering'] ?? false),
                'ai_gamertag' => $analysis['ai_gamertag'] ?? null,
                'ai_gamertag_normalized' => $analysis['ai_gamertag_normalized'] ?? null,
                'ai_gamertag_confidence' => $analysis['ai_gamertag_confidence'] ?? null,
                'ai_gamertag_mismatch' => (bool) ($analysis['ai_gamertag_mismatch'] ?? false),
                'ai_invalid_reason' => $analysis['invalid_reason'] ?? null,
                'ai_raw_result' => $raw,
                'sha256_hash' => $analysis['sha256_hash'] ?? $submission->sha256_hash,
                'phash' => $analysis['phash'] ?? $submission->phash,
                'image_width' => $analysis['image_width'] ?? $submission->image_width,
                'image_height' => $analysis['image_height'] ?? $submission->image_height,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'processed_at' => now(),
            ])->save();

            if (in_array($submission->status, CupSubmission::scoredStatuses(), true)) {
                $this->lockTeamGamertagFromSubmission($team, $submission);
            }

            $this->lockRosterFromSubmission($team, $submission);
            $this->recalculateTeamTotals($team);
        });

        $submission->refresh();

        return redirect()->route('cups.show.section', [$cup, 'submissions'])->with('cup_submission_result', [
            'type' => in_array($submission->status, CupSubmission::scoredStatuses(), true) ? 'success' : ($submission->status === 'invalid' ? 'danger' : 'warning'),
            'title' => __('ui.cup_submission_rescored_status'),
            'message' => (string) ($analysis['message'] ?? __('ui.cup_submission_rescored_status')),
            'status' => $submission->statusLabel(),
            'score' => __('ui.cup_submission_score_full', ['points' => $submission->points, 'kills' => $submission->kills, 'tokens' => $submission->bounty_tokens]),
            'summary' => $submission->resultSummary(),
        ]);
    }

    public function screenshot(Request $request, Cup $cup, CupSubmission $submission): StreamedResponse
    {
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);

        $submission->loadMissing(['team.members', 'screenshot']);

        $asset = $submission->screenshot;

        abort_unless($asset && $asset->status === 'ready', 404);
        abort_unless($this->canViewSubmissionScreenshot($request->user(), $cup, $submission), 403);

        $disk = filled($asset->disk) ? (string) $asset->disk : 'public';
        $path = ltrim((string) $asset->path, '/');

        abort_unless($path !== '' && Storage::disk($disk)->exists($path), 404);

        $extension = trim((string) $asset->extension) ?: pathinfo($path, PATHINFO_EXTENSION);
        $filename = 'cup-submission-'.$submission->id.($extension ? '.'.$extension : '');
        $mimeType = $asset->mime_type ?: (Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream');

        return Storage::disk($disk)->response($path, $filename, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ], 'inline');
    }


    private function applyGamertagConsistency(CupSubmissionAnalysisService $analysisService, array $analysis, CupTeam $team): array
    {
        $currentName = trim((string) ($analysis['ai_gamertag'] ?? ''));
        $currentNormalized = trim((string) ($analysis['ai_gamertag_normalized'] ?? ''));
        if ($currentNormalized === '' && $currentName !== '') {
            $currentNormalized = $analysisService->normalizeGamertag($currentName) ?: '';
            $analysis['ai_gamertag_normalized'] = $currentNormalized ?: null;
        }

        $confidence = $analysis['ai_gamertag_confidence'] ?? null;
        $confidence = is_numeric($confidence) ? max(0.0, min(1.0, (float) $confidence)) : null;
        $analysis['ai_gamertag_confidence'] = $confidence;

        $threshold = $this->gamertagConfidenceThreshold();
        $reliableCurrent = $currentName !== '' && $currentNormalized !== '' && $confidence !== null && $confidence >= $threshold;
        $lockedNormalized = trim((string) $team->detected_gamertag_normalized);
        $lockedName = trim((string) $team->detected_gamertag);
        $status = (string) ($analysis['status'] ?? 'review_required');

        $raw = is_array($analysis['raw_ai_result'] ?? null) ? $analysis['raw_ai_result'] : [];
        $raw['gamertag_consistency'] = [
            'detected' => $currentName ?: null,
            'detected_normalized' => $currentNormalized ?: null,
            'detected_confidence' => $confidence,
            'expected' => $lockedName ?: null,
            'expected_normalized' => $lockedNormalized ?: null,
            'threshold' => $threshold,
        ];

        if ($reliableCurrent && $lockedNormalized === '' && $status === 'processed') {
            $team->forceFill([
                'detected_gamertag' => $currentName,
                'detected_gamertag_normalized' => $currentNormalized,
                'detected_gamertag_confidence' => $confidence,
                'detected_gamertag_locked_at' => now(),
            ])->save();

            $lockedName = $currentName;
            $lockedNormalized = $currentNormalized;
            $raw['gamertag_consistency']['locked_now'] = true;
        }

        if ($status !== 'invalid') {
            if (! $reliableCurrent) {
                $analysis['ai_gamertag_mismatch'] = false;

                if ($this->gamertagUnreadableBlocks()) {
                    $analysis['status'] = 'review_required';
                    $analysis['invalid_reason'] = 'gamertag_unreadable';
                    $analysis['points'] = 0;
                    $analysis['message'] = __('ui.cup_analysis_gamertag_unreadable');
                    $raw['gamertag_consistency']['decision'] = 'review_unreadable';
                } else {
                    // Do not zero an otherwise valid run just because the name confidence is slightly below threshold.
                    // Mismatches still block when a reliable different gamertag is detected.
                    $raw['gamertag_consistency']['decision'] = 'accepted_unreadable_warning';
                    $raw['gamertag_consistency']['warning'] = 'gamertag_unreadable';
                }
            } elseif ($lockedNormalized !== '' && ! hash_equals($lockedNormalized, $currentNormalized)) {
                $analysis['status'] = 'review_required';
                $analysis['invalid_reason'] = 'gamertag_mismatch';
                $analysis['points'] = 0;
                $analysis['ai_gamertag_mismatch'] = true;
                $analysis['message'] = __('ui.cup_analysis_gamertag_mismatch', [
                    'expected' => $lockedName ?: __('ui.cup_unknown'),
                    'detected' => $currentName,
                ]);
                $raw['gamertag_consistency']['decision'] = 'review_mismatch';
            } else {
                $analysis['ai_gamertag_mismatch'] = false;
                $raw['gamertag_consistency']['decision'] = 'accepted';
            }
        }

        $analysis['raw_ai_result'] = $raw;

        return $analysis;
    }


    private function lockRosterFromSubmission(CupTeam $team, CupSubmission $submission): void
    {
        $team->loadMissing('cup');

        if ($team->cup?->isSoloLeaderboard() === true || $team->isRosterLocked()) {
            return;
        }

        if (in_array($submission->status, ['invalid', 'rejected', 'rejected_manual'], true)) {
            return;
        }

        $team->forceFill(['roster_locked_at' => now()])->save();
    }

    private function lockTeamGamertagFromSubmission(CupTeam $team, CupSubmission $submission): void
    {
        if (filled($team->detected_gamertag_normalized)) {
            return;
        }

        $name = trim((string) $submission->ai_gamertag);
        $normalized = trim((string) $submission->ai_gamertag_normalized);
        $confidence = $submission->ai_gamertag_confidence;

        if ($name === '' || $normalized === '' || ! is_numeric($confidence) || (float) $confidence < $this->gamertagConfidenceThreshold()) {
            return;
        }

        $team->forceFill([
            'detected_gamertag' => $name,
            'detected_gamertag_normalized' => $normalized,
            'detected_gamertag_confidence' => (float) $confidence,
            'detected_gamertag_locked_at' => now(),
        ])->save();
    }

    private function gamertagConfidenceThreshold(): float
    {
        return max(0.5, min(0.95, (float) env('HH_CUP_GAMERTAG_CONFIDENCE', 0.62)));
    }

    private function gamertagUnreadableBlocks(): bool
    {
        $value = env('HH_CUP_GAMERTAG_UNREADABLE_BLOCKS', false);

        return is_bool($value)
            ? $value
            : in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function canViewSubmissionScreenshot(?User $user, Cup $cup, CupSubmission $submission): bool
    {
        if (! $user) {
            return false;
        }

        if ($cup->canManage($user)) {
            return true;
        }

        if ((int) $submission->submitted_by === (int) $user->id) {
            return true;
        }

        $team = $submission->team;

        return $team?->hasMember($user) === true;
    }

    private function recalculateTeamTotals(CupTeam $team): void
    {
        $lockedTeam = CupTeam::query()->whereKey($team->id)->with('cup')->lockForUpdate()->firstOrFail();
        $maxScoredRuns = $lockedTeam->cup?->maxScoredSubmissionsPerParticipant();

        $query = CupSubmission::query()
            ->where('cup_team_id', $lockedTeam->id)
            ->whereIn('status', CupSubmission::scoredStatuses())
            ->orderByDesc('points')
            ->orderByDesc('bounty_tokens')
            ->orderByDesc('kills')
            ->orderBy('submitted_at')
            ->orderBy('id');

        if ($maxScoredRuns !== null) {
            $submissions = $query->limit($maxScoredRuns)->get(['points', 'kills', 'bounty_tokens']);

            $lockedTeam->forceFill([
                'points_total' => (int) $submissions->sum('points'),
                'kills_total' => (int) $submissions->filter(fn (CupSubmission $submission): bool => (int) $submission->points > 0)->sum('kills'),
                'bounty_tokens_total' => (int) $submissions->filter(fn (CupSubmission $submission): bool => (int) $submission->points > 0)->sum('bounty_tokens'),
                'submissions_approved_count' => $submissions->count(),
            ])->save();

            return;
        }

        $totals = $query
            ->selectRaw('COALESCE(SUM(points), 0) as points_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN points > 0 THEN kills ELSE 0 END), 0) as kills_sum')
            ->selectRaw('COALESCE(SUM(CASE WHEN points > 0 THEN bounty_tokens ELSE 0 END), 0) as bounty_sum')
            ->selectRaw('COUNT(*) as submissions_count')
            ->first();

        $lockedTeam->forceFill([
            'points_total' => (int) ($totals?->points_sum ?? 0),
            'kills_total' => (int) ($totals?->kills_sum ?? 0),
            'bounty_tokens_total' => (int) ($totals?->bounty_sum ?? 0),
            'submissions_approved_count' => (int) ($totals?->submissions_count ?? 0),
        ])->save();
    }
}
