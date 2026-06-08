<?php

namespace App\Http\Controllers\LoadoutChallenges;

use App\Http\Controllers\Controller;
use App\Models\LoadoutChallenge;
use App\Models\LoadoutChallengeSubmission;
use App\Services\GamificationService;
use App\Services\MediaService;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoadoutChallengeController extends Controller
{
    public function index(Request $request): View
    {
        $status = in_array($request->query('status'), ['running', 'planned', 'ended'], true) ? (string) $request->query('status') : null;

        $challenges = LoadoutChallenge::query()
            ->publicVisible()
            ->withCount(['submissions', 'acceptedSubmissions'])
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('starts_at')
            ->latest('id')
            ->get()
            ->filter(fn (LoadoutChallenge $challenge): bool => $status === null || $challenge->runtimeStatus() === $status)
            ->values();

        $stats = [
            'running' => $challenges->filter(fn (LoadoutChallenge $challenge): bool => $challenge->runtimeStatus() === 'running')->count(),
            'planned' => $challenges->filter(fn (LoadoutChallenge $challenge): bool => $challenge->runtimeStatus() === 'planned')->count(),
            'ended' => $challenges->filter(fn (LoadoutChallenge $challenge): bool => $challenge->runtimeStatus() === 'ended')->count(),
            'accepted' => $challenges->sum(fn (LoadoutChallenge $challenge): int => (int) $challenge->accepted_submissions_count),
        ];

        return HntTheme::view('loadout-challenges.index', [
            'challenges' => $challenges,
            'stats' => $stats,
            'selectedStatus' => $status,
        ]);
    }

    public function show(LoadoutChallenge $challenge): View
    {
        abort_unless($challenge->status === LoadoutChallenge::STATUS_ACTIVE, 404);

        $challenge->loadMissing(['creator.profile']);
        $challenge->loadCount(['submissions', 'acceptedSubmissions']);

        $acceptedSubmissions = $challenge->acceptedSubmissions()
            ->with(['user.profile', 'media'])
            ->latest('reviewed_at')
            ->latest('created_at')
            ->limit(8)
            ->get();

        $viewerSubmissions = collect();
        if (auth()->check()) {
            $viewerSubmissions = $challenge->submissions()
                ->where('user_id', auth()->id())
                ->with('media')
                ->latest()
                ->limit(5)
                ->get();
        }

        return HntTheme::view('loadout-challenges.show', [
            'challenge' => $challenge,
            'acceptedSubmissions' => $acceptedSubmissions,
            'viewerSubmissions' => $viewerSubmissions,
            'mediaLimitMb' => round(config('hunthub.upload_limits.loadout_challenge_media_kb', 102400) / 1024),
        ]);
    }

    public function storeSubmission(Request $request, LoadoutChallenge $challenge, MediaService $mediaService, GamificationService $gamification): RedirectResponse
    {
        abort_unless($challenge->status === LoadoutChallenge::STATUS_ACTIVE, 404);

        if (! $challenge->canAcceptSubmissions()) {
            throw ValidationException::withMessages([
                'body' => __('ui.loadout_submission_closed'),
            ]);
        }

        $validated = $request->validate([
            'outcome' => ['nullable', 'string', 'max:80'],
            'body' => ['required', 'string', 'min:8', 'max:4000'],
            'proof' => ['nullable', 'file', 'mimetypes:image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm', 'max:'.config('hunthub.upload_limits.loadout_challenge_media_kb', 102400)],
        ]);

        if ($request->hasFile('proof')) {
            $mediaService->assertAllowed($request->file('proof'), $request->user(), 'loadout_challenges');
        }

        $submission = LoadoutChallengeSubmission::create([
            'loadout_challenge_id' => $challenge->id,
            'user_id' => $request->user()->id,
            'outcome' => $validated['outcome'] ?? null,
            'body' => $validated['body'],
            'status' => LoadoutChallengeSubmission::STATUS_PENDING,
        ]);

        if ($request->hasFile('proof')) {
            $asset = $mediaService->store($request->file('proof'), $request->user(), 'loadout_challenges', [
                'visibility' => 'registered',
                'attachable' => $submission,
                'metadata' => ['source' => 'loadout_challenge_submission'],
            ]);

            $submission->update(['media_asset_id' => $asset->id]);
        }

        $gamification->award(
            $request->user(),
            'loadout_challenge_submission_created',
            source: $submission,
            description: __('ui.loadout_submission_xp_created', ['challenge' => $challenge->title]),
            metadata: ['challenge_slug' => $challenge->slug]
        );

        return redirect()->route('loadout-challenges.show', $challenge)->with('status', __('ui.loadout_submission_created'));
    }
}
