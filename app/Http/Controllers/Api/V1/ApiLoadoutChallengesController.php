<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LoadoutChallenge;
use App\Models\LoadoutChallengeSubmission;
use App\Services\GamificationService;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiLoadoutChallengesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $status = in_array($request->query('status'), ['running', 'planned', 'ended'], true)
            ? (string) $request->query('status')
            : null;

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

        return response()->json([
            'data' => [
                'challenges' => $challenges->map(fn (LoadoutChallenge $challenge): array => $this->challengePayload($challenge, $request))->values(),
                'stats' => [
                    'running' => $challenges->filter(fn (LoadoutChallenge $challenge): bool => $challenge->runtimeStatus() === 'running')->count(),
                    'planned' => $challenges->filter(fn (LoadoutChallenge $challenge): bool => $challenge->runtimeStatus() === 'planned')->count(),
                    'ended' => $challenges->filter(fn (LoadoutChallenge $challenge): bool => $challenge->runtimeStatus() === 'ended')->count(),
                    'accepted' => $challenges->sum(fn (LoadoutChallenge $challenge): int => (int) $challenge->accepted_submissions_count),
                ],
                'filters' => ['status' => $status],
                'media_limit_mb' => round(config('hunthub.upload_limits.loadout_challenge_media_kb', 102400) / 1024),
            ],
        ]);
    }

    public function show(Request $request, LoadoutChallenge $challenge): JsonResponse
    {
        abort_unless($challenge->status === LoadoutChallenge::STATUS_ACTIVE, 404);

        $challenge->loadCount(['submissions', 'acceptedSubmissions']);

        return response()->json([
            'data' => [
                'challenge' => $this->challengePayload($challenge, $request, includeSubmissions: true),
                'media_limit_mb' => round(config('hunthub.upload_limits.loadout_challenge_media_kb', 102400) / 1024),
            ],
        ]);
    }

    public function storeSubmission(Request $request, LoadoutChallenge $challenge, MediaService $mediaService, GamificationService $gamification): JsonResponse
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
                'metadata' => ['source' => 'loadout_challenge_submission_api'],
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

        $submission->loadMissing(['media', 'user.profile']);
        $challenge->loadCount(['submissions', 'acceptedSubmissions']);

        return response()->json([
            'message' => __('ui.loadout_submission_created'),
            'submission' => $this->submissionPayload($submission),
            'challenge' => $this->challengePayload($challenge, $request, includeSubmissions: true),
        ], 201);
    }

    private function challengePayload(LoadoutChallenge $challenge, Request $request, bool $includeSubmissions = false): array
    {
        $challenge->loadCount(['submissions', 'acceptedSubmissions']);

        $payload = [
            'id' => (int) $challenge->id,
            'title' => (string) $challenge->title,
            'slug' => (string) $challenge->slug,
            'summary' => (string) ($challenge->summary ?: __('ui.loadout_no_summary')),
            'description' => (string) ($challenge->description ?: ''),
            'rules' => (string) ($challenge->rules ?: ''),
            'loadout_notes' => (string) ($challenge->loadout_notes ?: ''),
            'status' => (string) $challenge->status,
            'status_label' => $challenge->statusLabel(),
            'runtime_status' => $challenge->runtimeStatus(),
            'runtime_status_label' => $challenge->runtimeStatusLabel(),
            'starts_at' => $challenge->starts_at?->toISOString(),
            'ends_at' => $challenge->ends_at?->toISOString(),
            'xp_reward' => (int) $challenge->xp_reward,
            'badge_slug' => (string) ($challenge->badge_slug ?: ''),
            'is_featured' => (bool) $challenge->is_featured,
            'can_submit' => $challenge->canAcceptSubmissions(),
            'submissions_count' => (int) ($challenge->submissions_count ?? 0),
            'accepted_submissions_count' => (int) ($challenge->accepted_submissions_count ?? 0),
        ];

        if ($includeSubmissions) {
            $payload['accepted_submissions'] = $challenge->acceptedSubmissions()
                ->with(['user.profile', 'media'])
                ->latest('reviewed_at')
                ->latest('created_at')
                ->limit(8)
                ->get()
                ->map(fn (LoadoutChallengeSubmission $submission): array => $this->submissionPayload($submission))
                ->values();
        }

        $user = $request->user();
        if ($user) {
            $payload['my_submissions'] = $challenge->submissions()
                ->where('user_id', $user->id)
                ->with('media')
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn (LoadoutChallengeSubmission $submission): array => $this->submissionPayload($submission))
                ->values();
        } else {
            $payload['my_submissions'] = [];
        }

        return $payload;
    }

    private function submissionPayload(LoadoutChallengeSubmission $submission): array
    {
        $submission->loadMissing(['user.profile', 'media']);
        $media = $submission->media;
        $user = $submission->user;

        return [
            'id' => (int) $submission->id,
            'outcome' => (string) ($submission->outcome ?: ''),
            'body' => (string) ($submission->body ?: ''),
            'status' => (string) $submission->status,
            'status_label' => $submission->statusLabel(),
            'created_at' => $submission->created_at?->toISOString(),
            'reviewed_at' => $submission->reviewed_at?->toISOString(),
            'media' => $media ? [
                'id' => (int) $media->id,
                'type' => (string) $media->type,
                'mime_type' => (string) $media->mime_type,
                'url' => $media->url(),
                'thumbnail_url' => $media->thumbnailUrl(),
            ] : null,
            'user' => $user ? [
                'id' => (int) $user->id,
                'name' => (string) ($user->name ?: $user->username),
                'username' => (string) $user->username,
                'avatar_url' => $user->avatarUrl(),
            ] : null,
        ];
    }
}
