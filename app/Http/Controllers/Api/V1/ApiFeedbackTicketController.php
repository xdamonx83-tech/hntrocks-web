<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FeedbackTicket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiFeedbackTicketController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', Rule::in(array_keys(FeedbackTicket::typeOptions()))],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:4000'],
            'meta' => ['nullable', 'array'],
            'context' => ['nullable', 'array'],
        ]);

        $ticket = FeedbackTicket::create([
            'user_id' => $request->user()->id,
            'type' => $validated['type'],
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'status' => FeedbackTicket::STATUS_OPEN,
            'meta' => $validated['meta'] ?? $validated['context'] ?? null,
        ]);

        return response()->json([
            'message' => 'Feedback ticket saved.',
            'data' => [
                'id' => $ticket->id,
                'type' => $ticket->type,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'created_at' => optional($ticket->created_at)->toIso8601String(),
            ],
        ], 201);
    }
}
