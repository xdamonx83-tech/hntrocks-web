<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\Quest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminWeeklyContractController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $contracts = Quest::query()
            ->where('is_weekly_contract', true)
            ->withCount(['progress as completed_count' => fn ($query) => $query->whereNotNull('completed_at')])
            ->withCount('progress')
            ->orderByDesc('contract_starts_at')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $allContracts = Quest::query()->where('is_weekly_contract', true)->get();

        $stats = [
            'active' => $allContracts->filter(fn (Quest $contract): bool => $contract->contractStatus() === 'active')->count(),
            'planned' => $allContracts->filter(fn (Quest $contract): bool => $contract->contractStatus() === 'planned')->count(),
            'expired' => $allContracts->filter(fn (Quest $contract): bool => $contract->contractStatus() === 'expired')->count(),
            'inactive' => $allContracts->filter(fn (Quest $contract): bool => $contract->contractStatus() === 'inactive')->count(),
        ];

        return view('admin.contracts.index', [
            'contracts' => $contracts,
            'stats' => $stats,
            'actionOptions' => Quest::actionOptions(),
            'badges' => Badge::query()->where('is_active', true)->orderBy('name')->get(['slug', 'name']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateContract($request);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name']);
        $data['category'] = 'weekly_contract';
        $data['period'] = 'weekly';
        $data['is_weekly_contract'] = true;
        $data['is_repeatable'] = false;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['notify_on_completion'] = $request->boolean('notify_on_completion', true);

        Quest::create($data);

        return redirect()->route('admin.contracts.index')->with('status', __('ui.contract_admin_created'));
    }

    public function update(Request $request, Quest $contract): RedirectResponse
    {
        $this->guardAdmin($request);
        abort_unless((bool) $contract->is_weekly_contract, 404);

        $data = $this->validateContract($request, $contract);
        $data['slug'] = $this->uniqueSlug($data['slug'] ?: $data['name'], $contract);
        $data['category'] = 'weekly_contract';
        $data['period'] = 'weekly';
        $data['is_weekly_contract'] = true;
        $data['is_repeatable'] = false;
        $data['is_active'] = $request->boolean('is_active');
        $data['notify_on_completion'] = $request->boolean('notify_on_completion');

        $contract->update($data);

        return redirect()->route('admin.contracts.index')->with('status', __('ui.contract_admin_updated'));
    }

    public function destroy(Request $request, Quest $contract): RedirectResponse
    {
        $this->guardAdmin($request);
        abort_unless((bool) $contract->is_weekly_contract, 404);

        if ($contract->progress()->exists()) {
            $contract->update(['is_active' => false]);

            return redirect()->route('admin.contracts.index')->with('status', __('ui.contract_admin_deactivated'));
        }

        $contract->delete();

        return redirect()->route('admin.contracts.index')->with('status', __('ui.contract_admin_deleted'));
    }

    private function validateContract(Request $request, ?Quest $contract = null): array
    {
        $actionKeys = array_keys(Quest::actionOptions());

        $data = $request->validate([
            'name' => ['required', 'string', 'max:140'],
            'slug' => ['nullable', 'string', 'max:100', Rule::unique('quests', 'slug')->ignore($contract)],
            'description' => ['nullable', 'string', 'max:2000'],
            'action' => ['required', 'string', Rule::in($actionKeys)],
            'target_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'xp_reward' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'badge_slug' => ['nullable', 'string', 'max:80', 'exists:badges,slug'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'contract_starts_at' => ['nullable', 'date'],
            'contract_ends_at' => ['nullable', 'date'],
        ]);

        $data['slug'] = $data['slug'] ?? null;
        $data['badge_slug'] = $data['badge_slug'] ?: null;
        $data['xp_reward'] = (int) ($data['xp_reward'] ?? 0);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['contract_starts_at'] = $data['contract_starts_at'] ?: null;
        $data['contract_ends_at'] = $data['contract_ends_at'] ?: null;

        if ($data['contract_starts_at'] && $data['contract_ends_at'] && strtotime((string) $data['contract_ends_at']) < strtotime((string) $data['contract_starts_at'])) {
            throw ValidationException::withMessages([
                'contract_ends_at' => __('ui.contract_admin_date_error'),
            ]);
        }

        return $data;
    }

    private function uniqueSlug(string $value, ?Quest $ignore = null): string
    {
        $base = Str::slug($value) ?: 'auftrag';
        $slug = $base;
        $i = 2;

        while (Quest::query()
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
