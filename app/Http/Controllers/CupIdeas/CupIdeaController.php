<?php

namespace App\Http\Controllers\CupIdeas;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupIdea;
use App\Services\GamificationService;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CupIdeaController extends Controller
{
    public function index(Request $request): View
    {
        $category = $request->query('category');
        $status = $request->query('status');
        $sort = in_array($request->query('sort'), ['top', 'new'], true) ? (string) $request->query('sort') : 'top';

        $categoryOptions = CupIdea::categoryOptions();
        $statusOptions = CupIdea::statusOptions();

        $ideas = CupIdea::query()
            ->publicVisible()
            ->with(['user.profile', 'cup', 'viewerVote'])
            ->withCount('votes')
            ->when(array_key_exists((string) $category, $categoryOptions), fn ($query) => $query->where('category', $category))
            ->when(array_key_exists((string) $status, $statusOptions) && $status !== CupIdea::STATUS_ARCHIVED, fn ($query) => $query->where('status', $status))
            ->orderByDesc('is_featured')
            ->when($sort === 'new', fn ($query) => $query->latest(), fn ($query) => $query->orderByDesc('votes_count')->latest())
            ->paginate(12)
            ->withQueryString();

        $stats = [
            'total' => CupIdea::query()->publicVisible()->count(),
            'planned' => CupIdea::query()->whereIn('status', [CupIdea::STATUS_PLANNED, CupIdea::STATUS_COMING_SOON])->count(),
            'implemented' => CupIdea::query()->where('status', CupIdea::STATUS_IMPLEMENTED)->count(),
        ];

        return view(HntTheme::resolve('cup-ideas.index'), [
            'ideas' => $ideas,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => array_filter($statusOptions, fn ($label, $key): bool => $key !== CupIdea::STATUS_ARCHIVED, ARRAY_FILTER_USE_BOTH),
            'cups' => Cup::query()->orderByDesc('starts_at')->orderByDesc('created_at')->limit(20)->get(['id', 'title']),
            'selectedCategory' => $category,
            'selectedStatus' => $status,
            'selectedSort' => $sort,
            'stats' => $stats,
        ]);
    }

    public function store(Request $request, GamificationService $gamification): RedirectResponse
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

        return redirect()->route('cup-ideas.index')->with('status', __('ui.cup_ideas_created'));
    }

    public function vote(Request $request, CupIdea $idea, GamificationService $gamification): RedirectResponse
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

        return back()->with('status', __('ui.cup_ideas_vote_saved'));
    }
}
