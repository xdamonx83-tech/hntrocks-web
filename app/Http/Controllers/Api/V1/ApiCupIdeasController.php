<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupIdea;
use App\Services\GamificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiCupIdeasController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $category = (string) $request->query('category', '');
        $status = (string) $request->query('status', '');
        $sort = in_array($request->query('sort'), ['top', 'new'], true) ? (string) $request->query('sort') : 'top';

        $categoryOptions = CupIdea::categoryOptions();
        $statusOptions = array_filter(
            CupIdea::statusOptions(),
            fn (string $label, string $key): bool => $key !== CupIdea::STATUS_ARCHIVED,
            ARRAY_FILTER_USE_BOTH
        );

        $ideas = CupIdea::query()
            ->publicVisible()
            ->with(['user.profile', 'cup', 'viewerVote'])
            ->withCount('votes')
            ->when(array_key_exists($category, $categoryOptions), fn ($query) => $query->where('category', $category))
            ->when(array_key_exists($status, $statusOptions), fn ($query) => $query->where('status', $status))
            ->orderByDesc('is_featured')
            ->when($sort === 'new', fn ($query) => $query->latest(), fn ($query) => $query->orderByDesc('votes_count')->latest())
            ->paginate(20);

        return response()->json([
            'data' => [
                'ideas' => $ideas->getCollection()->map(fn (CupIdea $idea): array => $this->ideaPayload($idea))->values(),
                'categories' => $this->optionPayload($categoryOptions),
                'statuses' => $this->optionPayload($statusOptions),
                'cups' => Cup::query()
                    ->orderByDesc('starts_at')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get(['id', 'title'])
                    ->map(fn (Cup $cup): array => ['id' => (int) $cup->id, 'title' => (string) $cup->title])
                    ->values(),
                'stats' => [
                    'total' => CupIdea::query()->publicVisible()->count(),
                    'planned' => CupIdea::query()->whereIn('status', [CupIdea::STATUS_PLANNED, CupIdea::STATUS_COMING_SOON])->count(),
                    'implemented' => CupIdea::query()->where('status', CupIdea::STATUS_IMPLEMENTED)->count(),
                ],
                'filters' => [
                    'category' => $category,
                    'status' => $status,
                    'sort' => $sort,
                ],
                'pagination' => [
                    'current_page' => $ideas->currentPage(),
                    'last_page' => $ideas->lastPage(),
                    'per_page' => $ideas->perPage(),
                    'total' => $ideas->total(),
                ],
            ],
        ]);
    }

    public function store(Request $request, GamificationService $gamification): JsonResponse
    {
        $data = $request->validate([
            'cup_id' => ['nullable', 'integer', 'exists:cups,id'],
            'title' => ['required', 'string', 'min:4', 'max:160'],
            'category' => ['required', 'string', Rule::in(array_keys(CupIdea::categoryOptions()))],
            'description' => ['required', 'string', 'min:12', 'max:6000'],
        ]);

        $idea = CupIdea::create([
            'user_id' => $request->user()->id,
            'cup_id' => $data['cup_id'] ?? null,
            'title' => $data['title'],
            'category' => $data['category'],
            'description' => $data['description'],
            'status' => CupIdea::STATUS_NEW,
        ]);

        $gamification->award(
            $request->user(),
            'cup_idea_submitted',
            source: $idea,
            description: __('ui.cup_ideas_xp_submitted'),
            metadata: ['category' => $idea->category]
        );

        $idea->loadMissing(['user.profile', 'cup', 'viewerVote']);

        return response()->json([
            'message' => __('ui.cup_ideas_created'),
            'idea' => $this->ideaPayload($idea),
        ], 201);
    }

    public function vote(Request $request, CupIdea $idea, GamificationService $gamification): JsonResponse
    {
        abort_if($idea->status === CupIdea::STATUS_ARCHIVED, 404);

        $vote = null;

        DB::transaction(function () use ($request, $idea, &$vote): void {
            $existing = $idea->votes()->where('user_id', $request->user()->id)->first();

            if ($existing) {
                $existing->delete();
            } else {
                $vote = $idea->votes()->create(['user_id' => $request->user()->id]);
            }

            $idea->forceFill(['votes_count' => $idea->votes()->count()])->save();
        });

        if ($vote) {
            $gamification->award(
                $request->user(),
                'cup_idea_voted',
                source: $idea,
                description: __('ui.cup_ideas_xp_voted'),
                metadata: ['cup_idea_id' => $idea->id]
            );
        }

        $idea->loadMissing(['user.profile', 'cup', 'viewerVote']);

        return response()->json([
            'message' => __('ui.cup_ideas_vote_saved'),
            'idea' => $this->ideaPayload($idea->refresh()->load(['user.profile', 'cup', 'viewerVote'])),
        ]);
    }

    /**
     * @param array<string, string> $options
     * @return array<int, array<string, string>>
     */
    private function optionPayload(array $options): array
    {
        return collect($options)
            ->map(fn (string $label, string $key): array => [
                'key' => $key,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    private function ideaPayload(CupIdea $idea): array
    {
        $author = $idea->user;

        return [
            'id' => (int) $idea->id,
            'title' => (string) $idea->title,
            'description' => (string) $idea->description,
            'category' => (string) $idea->category,
            'category_label' => $idea->categoryLabel(),
            'status' => (string) $idea->status,
            'status_label' => $idea->statusLabel(),
            'is_featured' => (bool) $idea->is_featured,
            'votes_count' => (int) ($idea->votes_count ?? $idea->votes()->count()),
            'viewer_voted' => $idea->relationLoaded('viewerVote') && $idea->viewerVote !== null,
            'created_at' => $idea->created_at?->toISOString(),
            'cup' => $idea->cup ? [
                'id' => (int) $idea->cup->id,
                'title' => (string) $idea->cup->title,
                'slug' => (string) $idea->cup->slug,
            ] : null,
            'author' => $author ? [
                'id' => (int) $author->id,
                'name' => (string) ($author->name ?: $author->username),
                'username' => (string) $author->username,
                'avatar_url' => $author->avatarUrl(),
            ] : null,
        ];
    }
}
