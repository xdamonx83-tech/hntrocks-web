<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupSubmission;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminCupSubmissionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $cupId = $request->integer('cup');
        $status = (string) $request->query('status', 'open');
        $search = trim((string) $request->query('q', ''));

        $query = CupSubmission::query()
            ->with([
                'cup:id,title,slug',
                'team:id,name',
                'submitter:id,name,username',
                'reviewer:id,name,username',
            ])
            ->latest('submitted_at');

        if ($cupId > 0) {
            $query->where('cup_id', $cupId);
        }

        if ($search !== '') {
            $query->whereHas('submitter', function ($userQuery) use ($search): void {
                $userQuery->where('username', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%');
            });
        }

        match ($status) {
            'open' => $query->whereIn('status', ['review_required', 'pending']),
            'approved' => $query->whereIn('status', ['approved_manual', 'approved', 'processed']),
            'rejected' => $query->whereIn('status', ['rejected_manual', 'rejected', 'invalid']),
            default => null,
        };

        return view('admin.cup-submissions.index', [
            'submissions' => $query->paginate(20)->withQueryString(),
            'cups' => Cup::query()
                ->orderByDesc('starts_at')
                ->orderByDesc('id')
                ->get(['id', 'title', 'slug']),
            'selectedCup' => $cupId,
            'selectedStatus' => in_array($status, ['all', 'open', 'approved', 'rejected'], true)
                ? $status
                : 'open',
            'search' => $search,
            'counts' => [
                'all' => CupSubmission::count(),
                'open' => CupSubmission::whereIn('status', ['review_required', 'pending'])->count(),
                'approved' => CupSubmission::whereIn('status', ['approved_manual', 'approved', 'processed'])->count(),
                'rejected' => CupSubmission::whereIn('status', ['rejected_manual', 'rejected', 'invalid'])->count(),
            ],
        ]);
    }
}
