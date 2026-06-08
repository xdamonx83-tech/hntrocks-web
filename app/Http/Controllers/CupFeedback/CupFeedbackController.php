<?php

namespace App\Http\Controllers\CupFeedback;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupFeedbackEntry;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CupFeedbackController extends Controller
{
    public function create(Request $request): View
    {
        $cups = Cup::query()
            ->visible()
            ->whereIn('status', ['finished', 'archived', 'active', 'planned'])
            ->orderByRaw('COALESCE(ends_at, starts_at, created_at) desc')
            ->latest()
            ->limit(30)
            ->get();

        $selectedCup = null;
        if ($request->filled('cup')) {
            $selectedCup = $cups->firstWhere('slug', (string) $request->query('cup'));
        }

        if (! $selectedCup && $request->filled('cup_id')) {
            $selectedCup = $cups->firstWhere('id', (int) $request->query('cup_id'));
        }

        $selectedCup ??= $cups->first();

        $myFeedback = CupFeedbackEntry::query()
            ->with('cup:id,title,slug')
            ->where('user_id', $request->user()->id)
            ->latest('submitted_at')
            ->limit(5)
            ->get();

        return view(HntTheme::resolve('cup-feedback.create'), [
            'cups' => $cups,
            'selectedCup' => $selectedCup,
            'myFeedback' => $myFeedback,
            'categoryOptions' => CupFeedbackEntry::categoryOptions(),
            'statusOptions' => CupFeedbackEntry::statusOptions(),
            'joinOptions' => CupFeedbackEntry::wouldJoinAgainOptions(),
            'formatOptions' => CupFeedbackEntry::nextFormatOptions(),
            'likedOptions' => CupFeedbackEntry::likedOptions(),
            'issueOptions' => CupFeedbackEntry::issueOptions(),
            'ideaOptions' => CupFeedbackEntry::ideaOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cup_id' => ['nullable', 'integer', 'exists:cups,id'],
            'category' => ['required', 'string', Rule::in(array_keys(CupFeedbackEntry::categoryOptions()))],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'rating_overall' => ['required', 'integer', 'min:1', 'max:5'],
            'rating_rules' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rating_scoring' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rating_submission' => ['nullable', 'integer', 'min:1', 'max:5'],
            'rating_fairness' => ['nullable', 'integer', 'min:1', 'max:5'],
            'would_join_again' => ['required', 'string', Rule::in(array_keys(CupFeedbackEntry::wouldJoinAgainOptions()))],
            'preferred_next_format' => ['nullable', 'string', Rule::in(array_keys(CupFeedbackEntry::nextFormatOptions()))],
            'liked_options' => ['nullable', 'array'],
            'liked_options.*' => ['string', Rule::in(array_keys(CupFeedbackEntry::likedOptions()))],
            'issue_options' => ['nullable', 'array'],
            'issue_options.*' => ['string', Rule::in(array_keys(CupFeedbackEntry::issueOptions()))],
            'idea_options' => ['nullable', 'array'],
            'idea_options.*' => ['string', Rule::in(array_keys(CupFeedbackEntry::ideaOptions()))],
            'contact_allowed' => ['nullable', 'boolean'],
        ]);

        CupFeedbackEntry::create([
            'cup_id' => $validated['cup_id'] ?? null,
            'user_id' => $request->user()->id,
            'category' => $validated['category'],
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'rating_overall' => (int) $validated['rating_overall'],
            'rating_rules' => isset($validated['rating_rules']) ? (int) $validated['rating_rules'] : null,
            'rating_scoring' => isset($validated['rating_scoring']) ? (int) $validated['rating_scoring'] : null,
            'rating_submission' => isset($validated['rating_submission']) ? (int) $validated['rating_submission'] : null,
            'rating_fairness' => isset($validated['rating_fairness']) ? (int) $validated['rating_fairness'] : null,
            'would_join_again' => $validated['would_join_again'],
            'preferred_next_format' => $validated['preferred_next_format'] ?? null,
            'liked_options' => $validated['liked_options'] ?? [],
            'issue_options' => $validated['issue_options'] ?? [],
            'idea_options' => $validated['idea_options'] ?? [],
            'contact_allowed' => $request->boolean('contact_allowed', true),
            'status' => CupFeedbackEntry::STATUS_NEW,
            'submitted_at' => now(),
        ]);

        return redirect()->route('cup-feedback.create', array_filter([
            'cup_id' => $validated['cup_id'] ?? null,
        ]))->with('status', __('ui.cup_feedback_saved'));
    }
}
