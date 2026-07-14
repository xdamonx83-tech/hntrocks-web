<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupSubmission;
use App\Models\CupTeam;
use App\Services\GamificationService;
use App\Services\NotificationService;
use App\Support\CupOrganizerAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CommunityCupReviewController extends Controller
{
    public function approve(
        Request $request,
        Cup $cup,
        CupSubmission $submission,
        GamificationService $gamification,
        NotificationService $notifications
    ): RedirectResponse {
        $this->authorizeOrganizer($request, $cup, $submission);

        if (in_array($submission->status, CupSubmission::scoredStatuses(), true)) {
            return back()->with('status', __('ui.cup_submission_already_scored'));
        }

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
            $this->lockRosterFromSubmission($team, $submission);
            $this->recalculateTeamTotals($team);
        });

        $freshSubmission = $submission->fresh(['submitter', 'team', 'cup']) ?? $submission;
        $gamification->award(
            $freshSubmission->submitter,
            'cup_submission_approved',
            source: $freshSubmission,
            description: __('ui.cup_submission_gamification_approved')
        );
        $notifications->send(
            $freshSubmission->submitter,
            $request->user(),
            'cup_submission_approved',
            __('ui.cup_submission_gamification_approved'),
            __('ui.cup_submission_notification_approved_body', ['cup' => $cup->title]),
            route('cups.show.section', [$cup, 'submissions'])
        );

        return redirect()
            ->route('cups.show.section', [$cup, 'submissions'])
            ->with('status', __('ui.cup_submission_approved_status'));
    }

    public function reject(
        Request $request,
        Cup $cup,
        CupSubmission $submission,
        NotificationService $notifications
    ): RedirectResponse {
        $this->authorizeOrganizer($request, $cup, $submission);
        $validated = $request->validate([
            'review_note' => ['nullable', 'string', 'max:1200'],
        ]);

        DB::transaction(function () use ($request, $submission, $validated): void {
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

            $team = $submission->team()->with('cup')->lockForUpdate()->firstOrFail();
            $this->recalculateTeamTotals($team);
        });

        $submission->loadMissing('submitter');
        $notifications->send(
            $submission->submitter,
            $request->user(),
            'cup_submission_rejected',
            __('ui.cup_submission_notification_rejected_title'),
            __('ui.cup_submission_notification_rejected_body', ['cup' => $cup->title]),
            route('cups.show.section', [$cup, 'submissions'])
        );

        return redirect()
            ->route('cups.show.section', [$cup, 'submissions'])
            ->with('status', __('ui.cup_submission_rejected_status'));
    }

    public function manualScore(Request $request, Cup $cup, CupSubmission $submission): RedirectResponse
    {
        $this->authorizeOrganizer($request, $cup, $submission);
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
            $this->lockRosterFromSubmission($team, $submission);
            $this->recalculateTeamTotals($team);
        });

        return redirect()
            ->route('cups.show.section', [$cup, 'submissions'])
            ->with('status', __('ui.cup_submission_manual_score_saved'));
    }

    public function screenshot(Request $request, Cup $cup, CupSubmission $submission): StreamedResponse
    {
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);
        $submission->loadMissing(['team.members', 'screenshot']);
        $asset = $submission->screenshot;

        abort_unless($asset && $asset->status === 'ready', 404);
        abort_unless($this->canViewScreenshot($request, $cup, $submission), 403);

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

    private function authorizeOrganizer(Request $request, Cup $cup, CupSubmission $submission): void
    {
        abort_unless(CupOrganizerAccess::canManage($cup, $request->user()), 403);
        abort_unless((int) $submission->cup_id === (int) $cup->id, 404);
    }

    private function canViewScreenshot(Request $request, Cup $cup, CupSubmission $submission): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        if (CupOrganizerAccess::canManage($cup, $user)) {
            return true;
        }

        if ((int) $submission->submitted_by === (int) $user->id) {
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
                'kills_total' => (int) $submissions->filter(fn (CupSubmission $entry): bool => (int) $entry->points > 0)->sum('kills'),
                'bounty_tokens_total' => (int) $submissions->filter(fn (CupSubmission $entry): bool => (int) $entry->points > 0)->sum('bounty_tokens'),
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
