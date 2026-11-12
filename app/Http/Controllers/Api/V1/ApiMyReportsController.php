<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiMyReportsController extends Controller
{
    /**
     * A reporter may see their own moderation submissions, never those of
     * other users. Staff-only resolution_note is intentionally excluded.
     */
    public function index(Request $request): JsonResponse
    {
        $reports = Report::query()
            ->where('reporter_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        return response()->json([
            'data' => $reports->getCollection()
                ->map(fn (Report $report): array => $this->present($report))
                ->values(),
            'pagination' => [
                'current_page' => $reports->currentPage(),
                'last_page' => $reports->lastPage(),
                'total' => $reports->total(),
                'per_page' => $reports->perPage(),
            ],
        ]);
    }

    public function show(Request $request, Report $report): JsonResponse
    {
        abort_unless(
            $report->reporter_id !== null
                && (int) $report->reporter_id === (int) $request->user()->id,
            404
        );

        return response()->json(['data' => $this->present($report)]);
    }

    private function present(Report $report): array
    {
        return [
            'id' => (int) $report->id,
            'category' => $report->category,
            'reason' => $report->reason,
            'body' => $report->body,
            'status' => $report->status,
            'public_response' => $report->public_response,
            'created_at' => $report->created_at?->toIso8601String(),
            'updated_at' => $report->updated_at?->toIso8601String(),
            'resolved_at' => $report->resolved_at?->toIso8601String(),
        ];
    }
}
