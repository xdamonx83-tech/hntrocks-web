<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerComment;
use App\Models\HntMapMarkerVote;
use App\Models\User;
use App\Support\MapVoteVisitorIdentity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MapMarkerInteractionController extends Controller
{
    public function comments(Request $request, HntMapMarker $marker): JsonResponse
    {
        $viewer = $this->optionalApiUser($request);
        $this->assertCashSpot($marker);

        $comments = $marker->comments()
            ->with('user')
            ->oldest()
            ->limit(50)
            ->get();

        return response()->json([
            'comments' => $comments
                ->map(fn (HntMapMarkerComment $comment): array => $this->commentPayload($comment, $viewer))
                ->values(),
            'comment_count' => $marker->comments()->count(),
            'viewer_can_comment' => $viewer !== null,
        ]);
    }

    public function storeComment(Request $request, HntMapMarker $marker): JsonResponse
    {
        $this->assertCashSpot($marker);

        $comment = $marker->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $this->validatedBody($request),
        ]);
        $comment->load('user');

        return response()->json([
            'comment' => $this->commentPayload($comment, $request->user()),
            'comment_count' => $marker->comments()->count(),
        ], 201);
    }

    public function updateComment(Request $request, HntMapMarkerComment $comment): JsonResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);
        $this->assertCashSpot($comment->marker);

        $comment->update(['body' => $this->validatedBody($request)]);
        $comment->load('user');

        return response()->json([
            'comment' => $this->commentPayload($comment, $request->user()),
        ]);
    }

    public function destroyComment(Request $request, HntMapMarkerComment $comment): JsonResponse
    {
        $viewer = $request->user();

        abort_unless((int) $comment->user_id === (int) $viewer->id || $viewer->isAdmin(), 403);
        $this->assertCashSpot($comment->marker);

        $marker = $comment->marker;
        $comment->delete();

        return response()->json([
            'ok' => true,
            'comment_count' => $marker->comments()->count(),
        ]);
    }

    public function vote(Request $request, HntMapMarker $marker, MapVoteVisitorIdentity $visitorIdentity): JsonResponse
    {
        $viewer = $this->optionalApiUser($request);
        $validated = $request->validate([
            'value' => ['required', 'integer', Rule::in([1, -1])],
        ]);
        $this->assertCashSpot($marker);

        $visitorHash = null;

        if ($viewer === null) {
            $visitorId = $request->header('X-HNT-Visitor-ID') ?? $request->input('visitor_id');
            $request->merge([
                'visitor_id' => is_string($visitorId) ? trim($visitorId) : $visitorId,
            ]);
            $visitorId = $request->validate([
                'visitor_id' => ['required', 'string', 'max:255'],
            ])['visitor_id'];
            $visitorHash = $visitorIdentity->requestValueHash($visitorId);
        }

        $viewerVote = DB::transaction(function () use ($request, $marker, $validated, $viewer, $visitorHash, $visitorIdentity): ?int {
            $lockedMarker = HntMapMarker::query()->lockForUpdate()->findOrFail($marker->id);
            $this->assertCashSpot($lockedMarker);

            $voteQuery = HntMapMarkerVote::query()->where('hnt_map_marker_id', $lockedMarker->id);

            if ($viewer !== null) {
                $voteQuery->where('user_id', $viewer->id);
            } else {
                $voteQuery->where('visitor_hash', $visitorHash);
            }

            $vote = $voteQuery->lockForUpdate()->first();
            $value = (int) $validated['value'];

            if ($vote?->value === $value) {
                $vote->delete();

                return null;
            }

            $guestHashes = $viewer === null ? [
                'ip_hash' => $visitorIdentity->requestValueHash($request->ip()),
                'user_agent_hash' => $visitorIdentity->requestValueHash($request->userAgent()),
            ] : [];

            if ($vote) {
                $vote->update(['value' => $value, ...$guestHashes]);
            } else {
                $lockedMarker->votes()->create([
                    'user_id' => $viewer?->id,
                    'visitor_hash' => $visitorHash,
                    ...$guestHashes,
                    'value' => $value,
                ]);
            }

            return $value;
        });

        $counts = HntMapMarkerVote::query()
            ->where('hnt_map_marker_id', $marker->id)
            ->selectRaw('value, COUNT(*) as aggregate')
            ->groupBy('value')
            ->pluck('aggregate', 'value');

        return response()->json([
            'ok' => true,
            'up_count' => (int) ($counts[1] ?? 0),
            'down_count' => (int) ($counts[-1] ?? 0),
            'viewer_vote' => $viewerVote,
        ]);
    }

    private function optionalApiUser(Request $request): ?User
    {
        if (! $request->hasHeader('Authorization')) {
            return null;
        }

        $token = ApiAccessToken::findValidPlainToken($request->bearerToken());

        if (! $token || ! $token->user) {
            abort(401, 'Unauthenticated.');
        }

        $token->forceFill(['last_used_at' => now()])->save();
        Auth::setUser($token->user);
        $request->setUserResolver(fn (): User => $token->user);
        $request->attributes->set('api_access_token', $token);

        return $token->user;
    }

    private function assertCashSpot(HntMapMarker $marker): void
    {
        abort_unless($marker->type === 'cash' && $marker->status === 'approved', 404);
    }

    private function validatedBody(Request $request): string
    {
        $request->merge(['body' => trim((string) $request->input('body'))]);

        return $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ])['body'];
    }

    private function commentPayload(HntMapMarkerComment $comment, ?User $viewer): array
    {
        $comment->loadMissing('user');
        $author = $comment->user;
        $canEdit = $viewer !== null && (int) $comment->user_id === (int) $viewer->id;

        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
            'created_at_label' => $comment->created_at?->diffForHumans() ?? '',
            'user' => [
                'id' => $author?->id,
                'name' => $author?->name ?? 'User',
                'avatar_url' => $author?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg'),
            ],
            'can_edit' => $canEdit,
            'can_delete' => $canEdit || $viewer?->isAdmin() === true,
        ];
    }
}
