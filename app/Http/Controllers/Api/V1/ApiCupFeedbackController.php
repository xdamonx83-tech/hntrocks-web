<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupFeedbackEntry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class ApiCupFeedbackController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $cups = Cup::query()
            ->visible()
            ->whereIn('status', ['finished', 'archived', 'active', 'planned'])
            ->orderByRaw('COALESCE(ends_at, starts_at, created_at) desc')
            ->latest()
            ->limit(30)
            ->get(['id', 'title', 'slug', 'status', 'starts_at', 'ends_at']);

        $recentFeedback = CupFeedbackEntry::query()
            ->with('cup:id,title,slug')
            ->where('user_id', $request->user()->id)
            ->latest('submitted_at')
            ->limit(5)
            ->get()
            ->map(fn (CupFeedbackEntry $entry): array => $this->feedbackPayload($entry))
            ->values();

        return response()->json([
            'data' => [
                'cups' => $cups->map(fn (Cup $cup): array => [
                    'id' => $cup->id,
                    'title' => $cup->title,
                    'slug' => $cup->slug,
                    'status' => $cup->status,
                    'starts_at' => optional($cup->starts_at)->toIso8601String(),
                    'ends_at' => optional($cup->ends_at)->toIso8601String(),
                ])->values(),
                'my_feedback' => $recentFeedback,
                'options' => [
                    'categories' => $this->optionPayload(CupFeedbackEntry::categoryOptions()),
                    'join' => $this->optionPayload(CupFeedbackEntry::wouldJoinAgainOptions()),
                    'formats' => $this->optionPayload(CupFeedbackEntry::nextFormatOptions()),
                    'liked' => $this->optionPayload(CupFeedbackEntry::likedOptions()),
                    'issues' => $this->optionPayload(CupFeedbackEntry::issueOptions()),
                    'ideas' => $this->optionPayload(CupFeedbackEntry::ideaOptions()),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge([
            'liked_options' => $this->normalizeOptionList($request->input('liked_options')),
            'issue_options' => $this->normalizeOptionList($request->input('issue_options')),
            'idea_options' => $this->normalizeOptionList($request->input('idea_options')),
        ]);

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

        $entry = CupFeedbackEntry::create([
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

        $entry->load('cup:id,title,slug');

        return response()->json([
            'message' => __('ui.cup_feedback_saved'),
            'data' => $this->feedbackPayload($entry),
        ], 201);
    }

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

    private function feedbackPayload(CupFeedbackEntry $entry): array
    {
        return [
            'id' => $entry->id,
            'cup' => $entry->cup ? [
                'id' => $entry->cup->id,
                'title' => $entry->cup->title,
                'slug' => $entry->cup->slug,
            ] : null,
            'category' => $entry->category,
            'category_label' => $entry->categoryLabel(),
            'subject' => $entry->subject,
            'message' => $entry->message,
            'rating_overall' => $entry->rating_overall,
            'would_join_again' => $entry->would_join_again,
            'preferred_next_format' => $entry->preferred_next_format,
            'status' => $entry->status,
            'status_label' => $entry->statusLabel(),
            'submitted_at' => optional($entry->submitted_at)->toIso8601String(),
        ];
    }

    private function normalizeOptionList(mixed $value): array
    {
        if (is_array($value)) {
            return collect(Arr::flatten($value))
                ->map(fn ($item): string => trim((string) $item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (is_string($value)) {
            return collect(preg_split('/[,|]/', $value) ?: [])
                ->map(fn ($item): string => trim($item))
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        return [];
    }
}
