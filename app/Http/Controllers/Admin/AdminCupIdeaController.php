<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CupIdea;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCupIdeaController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $categoryOptions = CupIdea::categoryOptions();
        $statusOptions = CupIdea::statusOptions();
        $category = $request->query('category');
        $status = $request->query('status');

        $ideas = CupIdea::query()
            ->with(['user.profile', 'cup', 'reviewedBy'])
            ->withCount('votes')
            ->when(array_key_exists((string) $category, $categoryOptions), fn ($query) => $query->where('category', $category))
            ->when(array_key_exists((string) $status, $statusOptions), fn ($query) => $query->where('status', $status))
            ->orderByDesc('is_featured')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'new' => CupIdea::query()->where('status', CupIdea::STATUS_NEW)->count(),
            'reviewing' => CupIdea::query()->where('status', CupIdea::STATUS_REVIEWING)->count(),
            'planned' => CupIdea::query()->whereIn('status', [CupIdea::STATUS_PLANNED, CupIdea::STATUS_COMING_SOON])->count(),
            'implemented' => CupIdea::query()->where('status', CupIdea::STATUS_IMPLEMENTED)->count(),
        ];

        return view('admin.cup-ideas.index', [
            'ideas' => $ideas,
            'stats' => $stats,
            'categoryOptions' => $categoryOptions,
            'statusOptions' => $statusOptions,
            'selectedCategory' => $category,
            'selectedStatus' => $status,
        ]);
    }

    public function update(Request $request, CupIdea $idea): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(CupIdea::statusOptions()))],
            'admin_note' => ['nullable', 'string', 'max:4000'],
            'is_featured' => ['nullable', 'boolean'],
        ]);

        $idea->update([
            'status' => $data['status'],
            'admin_note' => $data['admin_note'] ?? null,
            'is_featured' => $request->boolean('is_featured'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        return redirect()->route('admin.cup-ideas.index', $request->only(['category', 'status']))->with('status', __('ui.cup_ideas_admin_updated'));
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
