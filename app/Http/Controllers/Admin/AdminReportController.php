<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
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
        ]);

        $report->forceFill([
            'status' => $validated['status'],
            'assigned_to' => $validated['status'] === 'in_review' ? $request->user()->id : $report->assigned_to,
            'resolved_by' => in_array($validated['status'], ['resolved', 'rejected'], true) ? $request->user()->id : null,
            'resolution_note' => $validated['resolution_note'] ?? $report->resolution_note,
            'resolved_at' => in_array($validated['status'], ['resolved', 'rejected'], true) ? now() : null,
        ])->save();

        return back()->with('status', 'Report wurde aktualisiert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
