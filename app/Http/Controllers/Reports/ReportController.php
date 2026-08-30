<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupSubmission;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\Guide;
use App\Models\GuideComment;
use App\Models\LfgPost;
use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\MomentComment;
use App\Models\Report;
use App\Models\Team;
use App\Models\TeamLfgPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:60'],
            'id' => ['required', 'integer'],
            'reason' => ['required', Rule::in($this->allowedReasons())],
            'body' => ['nullable', 'string', 'max:2000'],
        ]);

        $reportable = $this->resolveReportable($validated['type'], (int) $validated['id']);

        if ($this->isOwnReportable($reportable, $request->user())) {
            return $this->reportResponse($request, __('ui.report_own_content'), 422);
        }

        $existingReport = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->where('reportable_type', $reportable::class)
            ->where('reportable_id', $reportable->id)
            ->whereIn('status', ['open', 'in_review'])
            ->first();

        if ($existingReport) {
            return $this->reportResponse($request, __('ui.report_duplicate'), 200, $reportable, true);
        }

        Report::create([
            'reporter_id' => $request->user()->id,
            'reportable_type' => $reportable::class,
            'reportable_id' => $reportable->id,
            'category' => $validated['type'],
            'reason' => $validated['reason'],
            'body' => $validated['body'] ?? null,
            'status' => 'open',
        ]);

        return $this->reportResponse($request, __('ui.report_success'), 200, $reportable, true);
    }


    /**
     * @return array<int, string>
     */
    private function allowedReasons(): array
    {
        return ['spam', 'abuse', 'hate', 'nsfw', 'fraud', 'cheating', 'privacy', 'other'];
    }

    private function isOwnReportable(Model $reportable, ?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return match (true) {
            $reportable instanceof User => (int) $reportable->id === (int) $user->id,
            $reportable instanceof FeedPost => (int) $reportable->user_id === (int) $user->id,
            $reportable instanceof FeedComment => (int) $reportable->user_id === (int) $user->id,
            $reportable instanceof Guide => (int) $reportable->author_id === (int) $user->id,
            $reportable instanceof GuideComment => (int) $reportable->user_id === (int) $user->id,
            $reportable instanceof LfgPost => $reportable->isOwner($user),
            $reportable instanceof Team => $reportable->canManage($user),
            $reportable instanceof TeamLfgPost => $reportable->canManage($user),
            $reportable instanceof MediaAsset => (int) $reportable->user_id === (int) $user->id,
            $reportable instanceof Moment => (int) $reportable->user_id === (int) $user->id,
            $reportable instanceof MomentComment => (int) $reportable->user_id === (int) $user->id,
            $reportable instanceof Cup => (int) $reportable->owner_id === (int) $user->id,
            $reportable instanceof CupSubmission => (int) $reportable->submitted_by === (int) $user->id,
            default => false,
        };
    }

    private function reportResponse(Request $request, string $message, int $status = 200, ?Model $reportable = null, bool $reported = false): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            $payload = [
                'message' => $message,
                'reported' => $reported,
            ];

            if ($reportable) {
                $payload['report'] = [
                    'type' => $this->publicReportableType($reportable),
                    'id' => $reportable->id,
                ];
            }

            return response()->json($payload, $status);
        }

        if ($status >= 400) {
            return back()->withErrors(['report' => $message]);
        }

        return back()->with('status', $message);
    }

    private function publicReportableType(Model $reportable): string
    {
        return match (true) {
            $reportable instanceof User => 'user',
            $reportable instanceof FeedPost => 'feed_post',
            $reportable instanceof FeedComment => 'feed_comment',
            $reportable instanceof Guide => 'guide',
            $reportable instanceof GuideComment => 'guide_comment',
            $reportable instanceof LfgPost => 'lfg',
            $reportable instanceof Team => 'team',
            $reportable instanceof TeamLfgPost => 'team_lfg',
            $reportable instanceof MediaAsset => 'media',
            $reportable instanceof Moment => 'moment',
            $reportable instanceof MomentComment => 'moment_comment',
            $reportable instanceof Cup => 'cup',
            $reportable instanceof CupSubmission => 'cup_submission',
            default => 'content',
        };
    }

    private function resolveReportable(string $type, int $id): Model
    {
        $class = match ($type) {
            'user' => User::class,
            'feed_post' => FeedPost::class,
            'feed_comment' => FeedComment::class,
            'guide' => Guide::class,
            'guide_comment' => GuideComment::class,
            'team' => Team::class,
            'lfg' => LfgPost::class,
            'team_lfg' => TeamLfgPost::class,
            'media' => MediaAsset::class,
            'moment' => Moment::class,
            'moment_comment' => MomentComment::class,
            'cup' => Cup::class,
            'cup_submission' => CupSubmission::class,
            default => abort(404),
        };

        return $class::findOrFail($id);
    }
}
