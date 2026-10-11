<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UserLoadout;
use App\Models\UserLoadoutComment;
use App\Models\UserLoadoutVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiCommunityLoadoutController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'sort' => ['sometimes', Rule::in(['new', 'popular', 'discussed'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:80'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $sort = $data['sort'] ?? 'new';
        $search = trim((string) ($data['search'] ?? ''));

        $query = $this->publicLoadouts()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('primary_weapon', 'like', '%'.$search.'%')
                    ->orWhere('secondary_weapon', 'like', '%'.$search.'%');
            }));

        if ($sort === 'popular') {
            $query->orderByRaw('(up_votes_count * 3 - down_votes_count * 2 + comments_count) DESC');
        } elseif ($sort === 'discussed') {
            $query->orderByDesc('comments_count');
        }

        $page = $query->latest('id')->paginate(18);
        $viewerVotes = $this->viewerVotes($request, $page->getCollection()->pluck('id')->all());

        return response()->json([
            'data' => [
                'loadouts' => $page->getCollection()->map(
                    fn (UserLoadout $loadout): array => $this->payload($loadout, $request, $viewerVotes[$loadout->id] ?? 0)
                )->values(),
                'sort' => $sort,
                'pagination' => [
                    'current_page' => $page->currentPage(),
                    'last_page' => $page->lastPage(),
                    'total' => $page->total(),
                    'per_page' => $page->perPage(),
                ],
            ],
        ]);
    }

    public function mine(Request $request): JsonResponse
    {
        $loadouts = UserLoadout::query()
            ->where('user_id', $request->user()->id)
            ->with('user')
            ->withCount(['upVotes', 'downVotes', 'comments'])
            ->latest('id')
            ->paginate(30);

        return response()->json([
            'data' => [
                'loadouts' => $loadouts->getCollection()->map(
                    fn (UserLoadout $loadout): array => $this->payload($loadout, $request, 0)
                )->values(),
                'pagination' => [
                    'current_page' => $loadouts->currentPage(),
                    'last_page' => $loadouts->lastPage(),
                    'total' => $loadouts->total(),
                ],
            ],
        ]);
    }

    public function show(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ensureVisible($loadout, $request);
        $loadout->load('user')->loadCount(['upVotes', 'downVotes', 'comments']);
        $vote = $this->viewerVotes($request, [$loadout->id]);

        return response()->json(['data' => [
            'loadout' => $this->payload($loadout, $request, $vote[$loadout->id] ?? 0),
        ]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->input($request);

        if ($request->user()->loadouts()->count() >= 100) {
            abort(422, 'Maximum of 100 loadouts reached.');
        }

        // Community creations never occupy the three featured profile slots.
        $loadout = $request->user()->loadouts()->create([
            ...$data,
            'visibility' => $data['visibility'] ?? 'public',
            'is_published' => ($data['visibility'] ?? 'public') === 'public',
            'is_active' => false,
        ]);

        $loadout->load('user')->loadCount(['upVotes', 'downVotes', 'comments']);

        return response()->json(['data' => ['loadout' => $this->payload($loadout, $request, 0)]], 201);
    }

    public function update(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ensureOwner($loadout, $request);
        $data = $this->input($request, true);
        $visibility = $data['visibility'] ?? $loadout->visibility;

        $loadout->update([
            ...$data,
            'is_published' => $visibility === 'public',
        ]);

        $loadout->refresh()->load('user')->loadCount(['upVotes', 'downVotes', 'comments']);

        return response()->json(['data' => ['loadout' => $this->payload($loadout, $request, 0)]]);
    }

    public function destroy(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ensureOwner($loadout, $request);
        $loadout->delete();

        return response()->json(['message' => 'Loadout deleted.']);
    }

    public function vote(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ensurePublic($loadout);
        abort_if((int) $loadout->user_id === (int) $request->user()->id, 422, 'You cannot rate your own loadout.');

        $data = $request->validate(['value' => ['required', 'integer', Rule::in([-1, 0, 1])]]);
        $vote = $loadout->votes()->where('user_id', $request->user()->id)->first();

        if ((int) $data['value'] === 0) {
            $vote?->delete();
        } else {
            $loadout->votes()->updateOrCreate(
                ['user_id' => $request->user()->id],
                ['value' => (int) $data['value']]
            );
        }

        $loadout->load('user')->loadCount(['upVotes', 'downVotes', 'comments']);

        return response()->json(['data' => [
            'loadout' => $this->payload($loadout, $request, (int) $data['value']),
        ]]);
    }

    public function comments(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ensureVisible($loadout, $request);

        $page = $loadout->comments()->with('user')->latest('id')->paginate(30);

        return response()->json(['data' => [
            'comments' => $page->getCollection()->map(
                fn (UserLoadoutComment $comment): array => $this->commentPayload($comment, $request, (int) $loadout->user_id)
            )->values(),
            'pagination' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'total' => $page->total(),
            ],
        ]]);
    }

    public function storeComment(Request $request, UserLoadout $loadout): JsonResponse
    {
        $this->ensurePublic($loadout);
        if (is_string($request->input('body'))) {
            $request->merge(['body' => trim($request->input('body'))]);
        }
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:1000', $this->safeText()],
        ]);

        $comment = $loadout->comments()->create([
            'user_id' => $request->user()->id,
            'body' => trim($data['body']),
        ]);

        return response()->json(['data' => [
            'comment' => $this->commentPayload($comment->load('user'), $request, (int) $loadout->user_id),
        ]], 201);
    }

    public function destroyComment(Request $request, UserLoadout $loadout, UserLoadoutComment $comment): JsonResponse
    {
        $this->ensureVisible($loadout, $request);
        abort_unless((int) $comment->user_loadout_id === (int) $loadout->id, 404);
        abort_unless(
            (int) $comment->user_id === (int) $request->user()->id ||
            (int) $loadout->user_id === (int) $request->user()->id,
            404
        );
        $comment->delete();

        return response()->json(['message' => 'Comment deleted.']);
    }

    private function input(Request $request, bool $updating = false): array
    {
        $normalized = [];
        foreach (['title', 'primary_weapon', 'secondary_weapon', 'primary_ammo', 'secondary_ammo', 'note'] as $field) {
            if (is_string($request->input($field))) {
                $normalized[$field] = trim($request->input($field));
            }
        }
        $request->merge($normalized);
        $required = $updating ? 'sometimes' : 'required';
        $clean = $this->safeText();

        return $request->validate([
            'title' => [$required, 'string', 'min:3', 'max:80', $clean],
            'primary_weapon' => [$required, 'string', 'max:80', $clean],
            'secondary_weapon' => ['sometimes', 'nullable', 'string', 'max:80', $clean],
            'primary_ammo' => ['sometimes', 'nullable', 'string', 'max:120', $clean],
            'secondary_ammo' => ['sometimes', 'nullable', 'string', 'max:120', $clean],
            'tools' => ['sometimes', 'array', 'max:4'],
            'tools.*' => ['string', 'max:80', $clean],
            'consumables' => ['sometimes', 'array', 'max:4'],
            'consumables.*' => ['string', 'max:80', $clean],
            'traits' => ['sometimes', 'array', 'max:6'],
            'traits.*' => ['string', 'max:80', $clean],
            'note' => ['sometimes', 'nullable', 'string', 'max:1000', $clean],
            'visibility' => ['sometimes', Rule::in(['public', 'private'])],
        ]);
    }

    private function safeText(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if (is_string($value) && preg_match('~<[^>]*>|(?:https?://|www\\.|://|discord\\.gg)~i', $value)) {
                $fail('HTML and external links are not allowed.');
            }
        };
    }

    private function publicLoadouts()
    {
        return UserLoadout::query()
            ->where('visibility', 'public')
            ->where('is_published', true)
            ->with('user')
            ->withCount(['upVotes', 'downVotes', 'comments']);
    }

    private function ensurePublic(UserLoadout $loadout): void
    {
        abort_unless($loadout->is_published && $loadout->visibility === 'public', 404);
    }

    private function ensureVisible(UserLoadout $loadout, Request $request): void
    {
        if ((int) $loadout->user_id === (int) ($request->user()?->id ?? 0)) {
            return;
        }
        $this->ensurePublic($loadout);
    }

    private function ensureOwner(UserLoadout $loadout, Request $request): void
    {
        abort_unless((int) $loadout->user_id === (int) $request->user()->id, 404);
    }

    /** @param array<int> $ids */
    private function viewerVotes(Request $request, array $ids): array
    {
        if (! $request->user() || $ids === []) {
            return [];
        }

        return UserLoadoutVote::query()
            ->where('user_id', $request->user()->id)
            ->whereIn('user_loadout_id', $ids)
            ->pluck('value', 'user_loadout_id')
            ->map(fn ($value): int => (int) $value)
            ->all();
    }

    private function payload(UserLoadout $loadout, Request $request, int $viewerVote): array
    {
        $author = $loadout->user;
        $up = (int) ($loadout->up_votes_count ?? 0);
        $down = (int) ($loadout->down_votes_count ?? 0);

        return [
            'id' => (int) $loadout->id,
            'title' => $loadout->title,
            'primary_weapon' => $loadout->primary_weapon,
            'secondary_weapon' => $loadout->secondary_weapon,
            'primary_ammo' => $loadout->primary_ammo,
            'secondary_ammo' => $loadout->secondary_ammo,
            'tools' => $loadout->tools ?? [],
            'consumables' => $loadout->consumables ?? [],
            'traits' => $loadout->traits ?? [],
            'note' => $loadout->note,
            'visibility' => $loadout->visibility,
            'is_published' => (bool) $loadout->is_published,
            'is_owner' => (int) $loadout->user_id === (int) ($request->user()?->id ?? 0),
            'viewer_vote' => $viewerVote,
            'up_votes_count' => $up,
            'down_votes_count' => $down,
            'comments_count' => (int) ($loadout->comments_count ?? 0),
            'score' => $up - $down,
            'created_at' => $loadout->created_at?->toISOString(),
            'updated_at' => $loadout->updated_at?->toISOString(),
            'author' => $author ? [
                'id' => (int) $author->id,
                'name' => (string) ($author->name ?: $author->username),
                'username' => (string) $author->username,
                'avatar_url' => $author->avatarUrl(),
            ] : null,
        ];
    }

    private function commentPayload(UserLoadoutComment $comment, Request $request, int $loadoutOwnerId): array
    {
        $author = $comment->user;

        return [
            'id' => (int) $comment->id,
            'body' => $comment->body,
            'created_at' => $comment->created_at?->toISOString(),
            'can_delete' => (int) $comment->user_id === (int) ($request->user()?->id ?? 0) ||
                $loadoutOwnerId === (int) ($request->user()?->id ?? 0),
            'author' => $author ? [
                'id' => (int) $author->id,
                'name' => (string) ($author->name ?: $author->username),
                'username' => (string) $author->username,
                'avatar_url' => $author->avatarUrl(),
            ] : null,
        ];
    }
}
