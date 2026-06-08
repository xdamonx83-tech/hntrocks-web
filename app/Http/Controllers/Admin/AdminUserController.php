<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $users = User::query()
            ->with('profile')
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('q')).'%';

                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('email', 'like', $term);
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($request->boolean('admins'), fn ($query) => $query->where('is_admin', true))
            ->latest()
            ->paginate(24)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'filters' => $request->only(['q', 'status', 'admins']),
        ]);
    }

    public function updateStatus(Request $request, User $user): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended'],
        ]);

        if ((int) $request->user()->id === (int) $user->id && $validated['status'] === 'suspended') {
            return back()->with('status', 'Du kannst deinen eigenen Admin-Account nicht sperren.');
        }

        $user->forceFill([
            'status' => $validated['status'],
            'suspended_at' => $validated['status'] === 'suspended' ? now() : null,
        ])->save();

        return back()->with('status', 'Nutzerstatus wurde aktualisiert.');
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        $this->guardAdmin($request);

        if ((int) $request->user()->id === (int) $user->id && $user->is_admin) {
            return back()->with('status', 'Du kannst dir selbst nicht die Adminrechte entziehen.');
        }

        $user->forceFill([
            'is_admin' => ! (bool) $user->is_admin,
        ])->save();

        return back()->with('status', 'Adminrechte wurden aktualisiert.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
