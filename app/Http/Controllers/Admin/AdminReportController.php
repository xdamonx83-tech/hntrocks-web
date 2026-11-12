<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $reports = Report::query()
            ->with(['reporter', 'assignee', 'resolver', 'reportable'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->filled('reason'), fn ($query) => $query->where('reason', $request->string('reason')))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'filters' => $request->only(['status', 'reason']),
        ]);
    }

    public function update(Request $request, Report $report): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'in:open,in_review,resolved,rejected'],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
            'public_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $previousStatus = $report->status;
        $previousPublicResponse = $report->public_response;
        $publicResponse = array_key_exists('public_response', $validated)
            ? trim((string) ($validated['public_response'] ?? ''))
            : (string) $report->public_response;
        $publicResponse = $publicResponse !== '' ? $publicResponse : null;

        $report->forceFill([
            'status' => $validated['status'],
            'assigned_to' => $validated['status'] === 'in_review' ? $request->user()->id : $report->assigned_to,
            'resolved_by' => in_array($validated['status'], ['resolved', 'rejected'], true) ? $request->user()->id : null,
            'resolution_note' => $validated['resolution_note'] ?? $report->resolution_note,
            'public_response' => $publicResponse,
            'resolved_at' => in_array($validated['status'], ['resolved', 'rejected'], true) ? now() : null,
        ])->save();

        if ($previousStatus !== $report->status || $previousPublicResponse !== $publicResponse) {
            try {
                // A system notification, not a message from the reported user.
                // Never include the private resolution_note in a notification.
                app(NotificationService::class)->send(
                    $report->reporter,
                    null,
                    'moderation_report_status',
                    'Deine Meldung wurde aktualisiert',
                    'Der Bearbeitungsstatus deiner Meldung #'.$report->id.' wurde aktualisiert.',
                    '/settings/support/reports',
                    ['report_id' => $report->id, 'status' => $report->status]
                );
            } catch (Throwable $error) {
                // The moderation decision is committed even if push fails.
                Log::warning('Moderation report status notification failed.', [
                    'report_id' => $report->id,
                    'error' => $error->getMessage(),
                ]);
            }
        }

        return back()->with('status', 'Report wurde aktualisiert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
