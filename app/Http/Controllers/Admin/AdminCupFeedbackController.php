<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupFeedbackEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminCupFeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $feedbackEntries = CupFeedbackEntry::query()
            ->with(['cup:id,title,slug', 'user:id,name,username,email,avatar_path', 'assignee:id,name,username'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('cup_id'), fn ($query) => $query->where('cup_id', (int) $request->query('cup_id')))
            ->latest('submitted_at')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $cups = Cup::query()
            ->whereHas('feedbackEntries')
            ->orderByRaw('COALESCE(ends_at, starts_at, created_at) desc')
            ->get(['id', 'title', 'slug']);

        $stats = [
            'new' => CupFeedbackEntry::query()->where('status', CupFeedbackEntry::STATUS_NEW)->count(),
            'reviewing' => CupFeedbackEntry::query()->where('status', CupFeedbackEntry::STATUS_REVIEWING)->count(),
            'planned' => CupFeedbackEntry::query()->where('status', CupFeedbackEntry::STATUS_PLANNED)->count(),
            'resolved' => CupFeedbackEntry::query()->where('status', CupFeedbackEntry::STATUS_RESOLVED)->count(),
        ];

        return view('admin.cup-feedback.index', [
            'feedbackEntries' => $feedbackEntries,
            'cups' => $cups,
            'stats' => $stats,
            'filters' => $request->only(['status', 'category', 'cup_id']),
            'categoryOptions' => CupFeedbackEntry::categoryOptions(),
            'statusOptions' => CupFeedbackEntry::statusOptions(),
        ]);
    }

    public function update(Request $request, CupFeedbackEntry $feedback): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(CupFeedbackEntry::statusOptions()))],
            'admin_note' => ['nullable', 'string', 'max:3000'],
        ]);

        $isFinal = in_array($validated['status'], [
            CupFeedbackEntry::STATUS_RESOLVED,
            CupFeedbackEntry::STATUS_REJECTED,
            CupFeedbackEntry::STATUS_ARCHIVED,
        ], true);

        $feedback->forceFill([
            'status' => $validated['status'],
            'assigned_to' => in_array($validated['status'], [CupFeedbackEntry::STATUS_REVIEWING, CupFeedbackEntry::STATUS_PLANNED], true)
                ? $request->user()->id
                : $feedback->assigned_to,
            'resolved_by' => $isFinal ? $request->user()->id : null,
            'admin_note' => $validated['admin_note'] ?? null,
            'resolved_at' => $isFinal ? now() : null,
        ])->save();

        return back()->with('status', __('ui.cup_feedback_admin_saved'));
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
