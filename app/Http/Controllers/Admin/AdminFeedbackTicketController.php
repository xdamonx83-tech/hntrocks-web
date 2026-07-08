<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedbackTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminFeedbackTicketController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $tickets = FeedbackTicket::query()
            ->with('user:id,name,username,email')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        $stats = [
            'open' => FeedbackTicket::query()->where('status', FeedbackTicket::STATUS_OPEN)->count(),
            'in_review' => FeedbackTicket::query()->where('status', FeedbackTicket::STATUS_IN_REVIEW)->count(),
            'closed' => FeedbackTicket::query()->where('status', FeedbackTicket::STATUS_CLOSED)->count(),
        ];

        return view('admin.feedback-tickets.index', [
            'tickets' => $tickets,
            'filters' => $request->only(['status', 'type']),
            'statusOptions' => FeedbackTicket::statusOptions(),
            'typeOptions' => FeedbackTicket::typeOptions(),
            'stats' => $stats,
        ]);
    }

    public function update(Request $request, FeedbackTicket $ticket): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(FeedbackTicket::statusOptions()))],
        ]);

        $ticket->forceFill([
            'status' => $validated['status'],
        ])->save();

        return back()->with('status', 'Feedback-Ticket wurde aktualisiert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
