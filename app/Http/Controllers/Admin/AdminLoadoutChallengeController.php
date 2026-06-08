<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\LoadoutChallenge;
use App\Models\LoadoutChallengeSubmission;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminLoadoutChallengeController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $status = $request->query('status');
        $submissionStatus = $request->query('submission_status');

        $challenges = LoadoutChallenge::query()
            ->with(['creator.profile'])
            ->withCount(['submissions', 'acceptedSubmissions'])
            ->when(array_key_exists((string) $status, LoadoutChallenge::statusOptions()), fn ($query) => $query->where('status', $status))
            ->orderByDesc('is_featured')
            ->orderByDesc('starts_at')
            ->orderBy('sort_order')
            ->paginate(12)
            ->withQueryString();

        $submissions = LoadoutChallengeSubmission::query()
            ->with(['challenge', 'user.profile', 'media', 'reviewedBy.profile'])
            ->when(array_key_exists((string) $submissionStatus, LoadoutChallengeSubmission::statusOptions()), fn ($query) => $query->where('status', $submissionStatus))
            ->latest()
            ->paginate(12, ['*'], 'submissions_page')
            ->withQueryString();

        $stats = [
            'active' => LoadoutChallenge::query()->where('status', LoadoutChallenge::STATUS_ACTIVE)->count(),
            'draft' => LoadoutChallenge::query()->where('status', LoadoutChallenge::STATUS_DRAFT)->count(),
            'archived' => LoadoutChallenge::query()->where('status', LoadoutChallenge::STATUS_ARCHIVED)->count(),
            'pending_submissions' => LoadoutChallengeSubmission::query()->where('status', LoadoutChallengeSubmission::STATUS_PENDING)->count(),
        ];

        return view('admin.loadout-challenges.index', [
            'challenges' => $challenges,
            'submissions' => $submissions,
            'stats' => $stats,
            'statusOptions' => LoadoutChallenge::statusOptions(),
            'submissionStatusOptions' => LoadoutChallengeSubmission::statusOptions(),
            'badges' => Badge::query()->where('is_active', true)->orderBy('name')->get(['slug', 'name']),
            'selectedStatus' => $status,
            'selectedSubmissionStatus' => $submissionStatus,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateChallenge($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title']);
        $data['created_by'] = $request->user()->id;
        $data['is_featured'] = $request->boolean('is_featured');

        LoadoutChallenge::create($data);

        return redirect()->route('admin.loadout-challenges.index')->with('status', __('ui.loadout_admin_created'));
    }

    public function update(Request $request, LoadoutChallenge $challenge): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateChallenge($request, $challenge);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['title'], $challenge);
        $data['is_featured'] = $request->boolean('is_featured');

        $challenge->update($data);

        return redirect()->route('admin.loadout-challenges.index', $request->only('status'))->with('status', __('ui.loadout_admin_updated'));
    }

    public function destroy(Request $request, LoadoutChallenge $challenge): RedirectResponse
    {
        $this->guardAdmin($request);

        if ($challenge->submissions()->exists()) {
            $challenge->update(['status' => LoadoutChallenge::STATUS_ARCHIVED]);

            return redirect()->route('admin.loadout-challenges.index')->with('status', __('ui.loadout_admin_archived'));
        }

        $challenge->delete();

        return redirect()->route('admin.loadout-challenges.index')->with('status', __('ui.loadout_admin_deleted'));
    }

    public function updateSubmission(Request $request, LoadoutChallengeSubmission $submission, GamificationService $gamification, NotificationService $notifications): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(LoadoutChallengeSubmission::statusOptions()))],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $submission->loadMissing(['challenge', 'user']);
        $oldStatus = $submission->status;

        $submission->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        if ($data['status'] === LoadoutChallengeSubmission::STATUS_ACCEPTED && $oldStatus !== LoadoutChallengeSubmission::STATUS_ACCEPTED) {
            $this->awardAcceptedSubmission($submission->fresh(['challenge', 'user']) ?? $submission, $gamification);
        }

        $notifications->send(
            $submission->user,
            $request->user(),
            'loadout_challenge_submission_'.$data['status'],
            __('ui.loadout_notification_title'),
            __('ui.loadout_notification_body_'.$data['status'], ['challenge' => $submission->challenge?->title ?? 'Loadout-Challenge']),
            route('loadout-challenges.show', $submission->challenge)
        );

        return redirect()->route('admin.loadout-challenges.index', $request->only('submission_status'))->with('status', __('ui.loadout_submission_admin_updated'));
    }

    private function validateChallenge(Request $request, ?LoadoutChallenge $challenge = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('loadout_challenges', 'slug')->ignore($challenge)],
            'summary' => ['nullable', 'string', 'max:240'],
            'description' => ['nullable', 'string', 'max:8000'],
            'rules' => ['nullable', 'string', 'max:8000'],
            'loadout_notes' => ['nullable', 'string', 'max:8000'],
            'status' => ['required', 'string', Rule::in(array_keys(LoadoutChallenge::statusOptions()))],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'xp_reward' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'badge_slug' => ['nullable', 'string', 'max:80', 'exists:badges,slug'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ]);

        if (! empty($data['starts_at']) && ! empty($data['ends_at']) && strtotime((string) $data['ends_at']) < strtotime((string) $data['starts_at'])) {
            throw ValidationException::withMessages([
                'ends_at' => __('ui.loadout_admin_date_error'),
            ]);
        }

        $data['slug'] = $data['slug'] ?? null;
        $data['badge_slug'] = $data['badge_slug'] ?: null;
        $data['xp_reward'] = (int) ($data['xp_reward'] ?? 0);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['starts_at'] = $data['starts_at'] ?: null;
        $data['ends_at'] = $data['ends_at'] ?: null;

        return $data;
    }

    private function uniqueSlug(string $value, ?LoadoutChallenge $ignore = null): string
    {
        $base = Str::slug($value) ?: 'loadout-challenge';
        $slug = $base;
        $i = 2;

        while (LoadoutChallenge::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function awardAcceptedSubmission(LoadoutChallengeSubmission $submission, GamificationService $gamification): void
    {
        $challenge = $submission->challenge;
        $user = $submission->user;

        if (! $challenge || ! $user || $submission->xp_awarded_at || (int) $challenge->xp_reward <= 0) {
            return;
        }

        $gamification->award(
            $user,
            'loadout_challenge_submission_accepted',
            (int) $challenge->xp_reward,
            $submission,
            __('ui.loadout_xp_description', ['challenge' => $challenge->title]),
            ['challenge_slug' => $challenge->slug]
        );

        $submission->update(['xp_awarded_at' => now()]);
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
