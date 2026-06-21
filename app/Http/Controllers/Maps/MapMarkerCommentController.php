<?php

namespace App\Http\Controllers\Maps;

use App\Http\Controllers\Controller;
use App\Models\HntMapMarker;
use App\Models\HntMapMarkerComment;
use App\Models\User;
use App\Support\FeedTextRenderer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapMarkerCommentController extends Controller
{
    public function index(Request $request, HntMapMarker $marker): JsonResponse
    {
        $this->assertCommentableMarker($marker);

        $comments = $marker->comments()
            ->with('user')
            ->oldest()
            ->limit(50)
            ->get();

        return response()->json([
            'ok' => true,
            'comments' => $comments->map(fn (HntMapMarkerComment $comment): array => $this->commentPayload($comment, $request->user()))->values(),
            'comment_count' => $marker->comments()->count(),
            'viewer_can_comment' => $request->user() !== null,
            'routes' => [
                'store' => route('maps.markers.comments.store', $marker),
                'login' => route('login'),
            ],
        ]);
    }

    public function store(Request $request, HntMapMarker $marker): JsonResponse
    {
        $this->assertCommentableMarker($marker);
        $body = $this->validatedBody($request);

        $comment = $marker->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $body,
        ]);
        $comment->load('user');

        return response()->json([
            'ok' => true,
            'comment' => $this->commentPayload($comment, $request->user()),
            'comment_count' => $marker->comments()->count(),
        ], 201);
    }

    public function update(Request $request, HntMapMarkerComment $comment): JsonResponse
    {
        abort_unless((int) $comment->user_id === (int) $request->user()->id, 403);
        $this->assertCommentableMarker($comment->marker);

        $comment->update(['body' => $this->validatedBody($request)]);
        $comment->load('user');

        return response()->json([
            'ok' => true,
            'comment' => $this->commentPayload($comment, $request->user()),
        ]);
    }

    public function destroy(Request $request, HntMapMarkerComment $comment): JsonResponse
    {
        $viewer = $request->user();

        abort_unless((int) $comment->user_id === (int) $viewer->id || $viewer->isAdmin(), 403);
        $this->assertCommentableMarker($comment->marker);

        $marker = $comment->marker;
        $comment->delete();

        return response()->json([
            'ok' => true,
            'id' => $comment->id,
            'comment_count' => $marker->comments()->count(),
        ]);
    }

    private function assertCommentableMarker(HntMapMarker $marker): void
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
        $canDelete = $canEdit || $viewer?->isAdmin() === true;
        $profileUrl = $author && $viewer && (int) $author->id === (int) $viewer->id
            ? route('profile.show')
            : ($author ? route('profile.public', $author) : '#');

        return [
            'id' => $comment->id,
            'body' => $comment->body,
            'body_html' => FeedTextRenderer::render((string) $comment->body),
            'created_at_label' => $comment->created_at?->diffForHumans() ?? '',
            'updated_at_label' => $comment->updated_at?->diffForHumans() ?? '',
            'can_edit' => $canEdit,
            'can_delete' => $canDelete,
            'user' => [
                'id' => $author?->id,
                'name' => $author?->name ?? 'User',
                'avatar_url' => $author?->avatarUrl() ?? asset('assets/vikinger/img/default-avatar.svg'),
                'profile_url' => $profileUrl,
            ],
            'routes' => [
                'update' => $canEdit ? route('maps.marker-comments.update', $comment) : null,
                'delete' => $canDelete ? route('maps.marker-comments.destroy', $comment) : null,
            ],
        ];
    }
}
