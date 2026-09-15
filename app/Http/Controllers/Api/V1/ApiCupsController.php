<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\CupLeaderboardEntryResource;
use App\Http\Resources\Api\CupResource;
use App\Http\Resources\Api\CupSubmissionResource;
use App\Models\Cup;
use App\Models\CupSubmission;
use App\Models\CupTeam;
use App\Services\Cups\CupSubmissionAnalysisService;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApiCupsController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $cups = Cup::query()
            ->with('owner.profile')
            ->withCount('activeTeams')
            ->where('visibility', 'public')
            ->whereIn('status', ['planned', 'active', 'finished'])
            ->latest('starts_at')
            ->paginate(20);

        return CupResource::collection($cups);
    }

    public function show(Request $request, Cup $cup): JsonResponse
    {
        abort_unless($cup->visibility === 'public' || $cup->canManage($request->user()), 404);

        $cup->load([
            'owner.profile',
            'activeTeams.owner.profile',
            'activeTeams.members.user.profile',
        ]);

        $leaderboard = $this->leaderboardFor($cup);
        $viewerTeam = $cup->teamFor($request->user());
        $viewerSubmissions = collect();
        $eligibility = $cup->participationEligibility($request->user());

        if ($viewerTeam) {
            $viewerTeam->loadMissing('owner.profile');
            $viewerSubmissions = $this->viewerSubmissions($cup, $viewerTeam, 10);
        }

        return response()->json([
            'data' => new CupResource($cup),
            'leaderboard' => CupLeaderboardEntryResource::collection($leaderboard),
            'viewer' => [
                'can_manage' => $cup->canManage($request->user()),
                'can_register' => $viewerTeam === null && $cup->isRegistrationOpen() && (bool) ($eligibility['eligible'] ?? false),
                'eligibility' => $eligibility,
                'registered' => $viewerTeam !== null,
                'team' => $viewerTeam ? new CupLeaderboardEntryResource($viewerTeam) : null,
                'can_submit' => $viewerTeam !== null && $viewerTeam->status === 'active' && $cup->isSubmissionOpen() && $viewerTeam->canSubmitForCup($request->user()),
                'submission_cooldown_minutes' => max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30)),
                'submissions' => CupSubmissionResource::collection($viewerSubmissions),
            ],
        ]);
    }

    public function register(Request $request, Cup $cup, GamificationService $gamification, NotificationService $notifications): JsonResponse
    {
        abort_unless($cup->visibility === 'public' || $cup->canManage($request->user()), 404);

        $cup->loadMissing(['teams.members', 'owner.profile']);
        $existingTeam = $cup->teamFor($request->user());

        if ($existingTeam) {
            $existingTeam->loadMissing('owner.profile');

            return response()->json([
                'message' => $cup->isSoloLeaderboard()
                    ? __('ui.cup_team_error_already_registered')
                    : __('ui.cup_team_error_already_in_team'),
                'registered' => true,
                'team' => new CupLeaderboardEntryResource($existingTeam),
            ]);
        }

        if (! $cup->isRegistrationOpen()) {
            return response()->json([
                'message' => __('ui.cup_team_error_registration_closed'),
                'errors' => [
                    'cup' => [__('ui.cup_team_error_registration_closed')],
                ],
            ], 422);
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! ($eligibility['eligible'] ?? false)) {
            $message = implode(' ', $eligibility['messages'] ?? []);

            return response()->json([
                'message' => $message,
                'errors' => [
                    'cup' => [$message],
                ],
            ], 422);
        }

        if ($cup->isSoloLeaderboard()) {
            $request->validate([
                'name' => ['nullable', 'string', 'max:100'],
            ]);

            $displayName = $this->uniqueSoloParticipantName(
                $cup,
                $request->user()->username ?: $request->user()->name ?: __('ui.cup_player_fallback', ['id' => $request->user()->id]),
                (int) $request->user()->id
            );
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:100'],
            ]);

            $displayName = trim((string) $validated['name']);
        }

        $team = DB::transaction(function () use ($cup, $request, $displayName): CupTeam {
            $team = CupTeam::create([
                'cup_id' => $cup->id,
                'owner_id' => $request->user()->id,
                'name' => $displayName,
                'status' => 'active',
            ]);

            $team->members()->create([
                'user_id' => $request->user()->id,
                'role' => $cup->isSoloLeaderboard() ? 'participant' : 'captain',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            return $team;
        });

        $team->loadMissing('owner.profile');
        $gamification->award($request->user(), 'cup_team_created', source: $team, description: $cup->isSoloLeaderboard() ? __('ui.cup_participant_gamification_created') : __('ui.cup_team_gamification_created'));

        if ($cup->isSoloLeaderboard()) {
            $notifications->send($cup->owner, $request->user(), 'cup_participant_registered', __('ui.cup_participant_notification_created_title'), __('ui.cup_participant_notification_created_body', ['player' => $team->displayName(), 'cup' => $cup->title]), route('cups.show', $cup));
        } else {
            $notifications->send($cup->owner, $request->user(), 'cup_team_created', __('ui.cup_team_notification_created_title'), __('ui.cup_team_notification_created_body', ['team' => $team->name, 'cup' => $cup->title]), route('cups.show', $cup));
        }

        return response()->json([
            'message' => $cup->isSoloLeaderboard() ? __('ui.cup_participant_created_status') : __('ui.cup_team_created_status'),
            'registered' => true,
            'team' => new CupLeaderboardEntryResource($team),
        ], 201);
    }

    public function submit(
        Request $request,
        Cup $cup,
        MediaService $mediaService,
        CupSubmissionAnalysisService $analysisService,
        GamificationService $gamification,
        NotificationService $notifications
    ): JsonResponse {
        abort_unless($cup->visibility === 'public' || $cup->canManage($request->user()), 404);

        $cup->loadMissing(['teams.members', 'owner.profile']);
        $team = $cup->teamFor($request->user());

        if (! $team) {
            return response()->json([
                'message' => $cup->isSoloLeaderboard() ? __('ui.cup_submission_error_only_participant') : __('ui.cup_submission_error_only_members'),
                'errors' => [
                    'team' => [$cup->isSoloLeaderboard() ? __('ui.cup_submission_error_only_participant') : __('ui.cup_submission_error_only_members')],
                ],
            ], 403);
        }

        abort_unless((int) $team->cup_id === (int) $cup->id, 404);

        if (! $cup->isSubmissionOpen()) {
            $message = $cup->submissionClosedReason();

            return response()->json([
                'message' => $message,
                'errors' => [
                    'cup' => [$message],
                ],
            ], 422);
        }

        if ($team->status !== 'active') {
            $message = $team->isDisqualified() ? __('ui.cup_submission_error_disqualified') : __('ui.cup_submit_only_active');

            return response()->json([
                'message' => $message,
                'errors' => [
                    'team' => [$message],
                ],
            ], 403);
        }

        $team->setRelation('cup', $cup);
        $team->loadMissing('members');
        if (! $cup->isSoloLeaderboard() && ! $team->isCaptain($request->user())) {
            return response()->json([
                'message' => __('ui.cup_submission_error_only_captain'),
                'errors' => [
                    'team' => [__('ui.cup_submission_error_only_captain')],
                ],
            ], 403);
        }

        if (! $cup->isSoloLeaderboard() && ! $team->isComplete()) {
            $message = __('ui.cup_submission_error_team_incomplete', [
                'count' => $team->activeMembersCount(),
                'size' => $team->requiredMembersCount(),
            ]);

            return response()->json([
                'message' => $message,
                'errors' => [
                    'team' => [$message],
                ],
            ], 422);
        }

        $eligibility = $cup->participationEligibility($request->user());
        if (! ($eligibility['eligible'] ?? false)) {
            $message = implode(' ', $eligibility['messages'] ?? []);

            return response()->json([
                'message' => $message,
                'errors' => [
                    'submission' => [$message],
                ],
            ], 422);
        }

        $maxUploads = $cup->maxSubmissionsPerParticipant();
        if ($maxUploads !== null && $team->submissions()->count() >= $maxUploads) {
            $message = __('ui.cup_submission_error_limit_reached', ['count' => $maxUploads]);

            return response()->json([
                'message' => $message,
                'errors' => [
                    'submission' => [$message],
                ],
            ], 422);
        }

        $cooldownMinutes = max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30));
        if ($cooldownMinutes > 0 && $team->last_submission_at && $team->last_submission_at->gt(now()->subMinutes($cooldownMinutes))) {
            return response()->json([
                'message' => __('ui.cup_submission_error_cooldown', ['minutes' => $cooldownMinutes]),
                'errors' => [
                    'cooldown' => [__('ui.cup_submission_error_cooldown', ['minutes' => $cooldownMinutes])],
                ],
                'cooldown_minutes' => $cooldownMinutes,
                'retry_after_seconds' => max(1, now()->diffInSeconds($team->last_submission_at->copy()->addMinutes($cooldownMinutes), false)),
            ], 429);
        }

        $validated = $request->validate([
            'kills' => ['nullable', 'integer', 'min:0', 'max:99'],
            'bounty_tokens' => ['nullable', 'integer', 'min:0', 'max:4'],
            'extracted' => ['nullable', 'boolean'],
            'note' => ['nullable', 'string', 'max:1200'],
            'screenshot' => ['required', 'image', 'max:'.config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)],
        ]);

        $mediaService->assertAllowed($request->file('screenshot'), $request->user(), 'cups/screenshots');

        $analysis = $analysisService->analyze($cup, $request->file('screenshot'));
        if (! ($analysis['upload_ok'] ?? true)) {
            return response()->json([
                'message' => (string) ($analysis['message'] ?? __('ui.cup_submission_invalid_default')),
                'errors' => [
                    'screenshot' => [(string) ($analysis['message'] ?? __('ui.cup_submission_invalid_default'))],
                ],
                'analysis' => [
                    'status' => $analysis['status'] ?? 'invalid',
                    'invalid_reason' => $analysis['invalid_reason'] ?? null,
                ],
            ], 422);
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
                    'api_client' => true,
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
                'reported_kills' => array_key_exists('kills', $validated)
                    ? (int) $validated['kills']
                    : null,
                'reported_bounty_tokens' => array_key_exists('bounty_tokens', $validated)
                    ? (int) $validated['bounty_tokens']
                    : null,
                'reported_extracted' => array_key_exists('extracted', $validated)
                    ? (bool) $validated['extracted']
                    : null,
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

        $submission->loadMissing(['cup', 'team.owner.profile', 'submitter.profile']);
        $gamification->award($request->user(), 'cup_submission_created', source: $submission, description: __('ui.cup_submission_gamification_created'));

        if ($submission->status === 'review_required') {
            $notifications->send($cup->owner, $request->user(), 'cup_submission_review_required', __('ui.cup_submission_notification_review_title'), $cup->isSoloLeaderboard() ? __('ui.cup_submission_notification_review_body_solo', ['player' => $team->displayName()]) : __('ui.cup_submission_notification_review_body', ['team' => $team->name]), route('cups.show', $cup));
        } elseif ($submission->status === 'processed') {
            $notifications->send($cup->owner, $request->user(), 'cup_submission_processed', __('ui.cup_submission_notification_processed_title'), $cup->isSoloLeaderboard() ? __('ui.cup_submission_notification_processed_body_solo', ['player' => $team->displayName()]) : __('ui.cup_submission_notification_processed_body', ['team' => $team->name]), route('cups.show', $cup));
        }

        $team = $team->fresh(['owner.profile']) ?? $team;

        return response()->json([
            'message' => (string) ($analysis['message'] ?? __('ui.cup_submission_message_default')),
            'result' => [
                'type' => match ($submission->status) {
                    'processed', 'approved', 'approved_manual' => 'success',
                    'invalid', 'rejected', 'rejected_manual' => 'danger',
                    'review_required', 'pending' => 'warning',
                    default => 'info',
                },
                'status' => $submission->status,
                'status_label' => $submission->statusLabel(),
                'score' => __('ui.cup_submission_score_full', ['points' => $submission->points, 'kills' => $submission->kills, 'tokens' => $submission->bounty_tokens]),
                'summary' => $submission->resultSummary(),
            ],
            'submission' => new CupSubmissionResource($submission),
            'viewer' => [
                'registered' => true,
                'team' => new CupLeaderboardEntryResource($team),
                'can_submit' => $team->status === 'active' && $cup->isSubmissionOpen() && $team->canSubmitForCup($request->user()),
                'submission_cooldown_minutes' => $cooldownMinutes,
                'submissions' => CupSubmissionResource::collection($this->viewerSubmissions($cup, $team, 10)),
            ],
        ], 201);
    }

    public function screenshot(Request $request, Cup $cup, CupSubmission $submission): StreamedResponse
    {
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);

        $submission->loadMissing(['team.members', 'screenshot']);
        $asset = $submission->screenshot;

        abort_unless($asset && $asset->status === 'ready', 404);
        abort_unless($this->canViewSubmissionScreenshot($request, $cup, $submission), 403);

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

    private function leaderboardFor(Cup $cup)
    {
        return $cup->activeTeams
            ->sortBy([
                ['points_total', 'desc'],
                ['bounty_tokens_total', 'desc'],
                ['kills_total', 'desc'],
                ['submissions_approved_count', 'desc'],
                ['id', 'asc'],
            ])
            ->values();
    }

    private function viewerSubmissions(Cup $cup, CupTeam $team, int $limit)
    {
        return CupSubmission::query()
            ->with('cup')
            ->where('cup_id', $cup->id)
            ->where('cup_team_id', $team->id)
            ->latest('submitted_at')
            ->limit($limit)
            ->get();
    }

    private function canViewSubmissionScreenshot(Request $request, Cup $cup, CupSubmission $submission): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if ($cup->canManage($user) || (int) $submission->submitted_by === (int) $user->id) {
            return true;
        }

        return $submission->team?->hasMember($user) === true;
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

    private function applyGamertagConsistency(CupSubmissionAnalysisService $analysisService, array $analysis, CupTeam $team): array
    {
        $team->loadMissing('cup');

        if ($team->cup?->usesManualReviewScoring() === true) {
            return $analysis;
        }

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

    private function recalculateTeamTotals(CupTeam $team): void
    {
        $lockedTeam = CupTeam::query()->whereKey($team->id)->lockForUpdate()->firstOrFail();
        $totals = CupSubmission::query()
            ->where('cup_team_id', $lockedTeam->id)
            ->whereIn('status', CupSubmission::scoredStatuses())
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

    private function uniqueSoloParticipantName(Cup $cup, string $displayName, int $userId): string
    {
        $displayName = trim($displayName) !== '' ? trim($displayName) : __('ui.cup_player_fallback', ['id' => $userId]);
        $displayName = Str::limit($displayName, 100, '');

        if (! CupTeam::where('cup_id', $cup->id)->where('name', $displayName)->exists()) {
            return $displayName;
        }

        $suffix = ' #'.$userId;
        $base = Str::limit($displayName, max(1, 100 - strlen($suffix)), '');

        return $base.$suffix;
    }
}
