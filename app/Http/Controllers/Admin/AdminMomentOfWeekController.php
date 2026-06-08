<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Models\MomentSpotlight;
use App\Services\GamificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMomentOfWeekController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $spotlights = MomentSpotlight::query()
            ->with(['moment.user.profile', 'moment.media', 'moment.cover', 'selectedBy'])
            ->latest('week_starts_at')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $candidateMoments = Moment::query()
            ->with(['user.profile', 'media', 'cover'])
            ->published()
            ->latest('published_at')
            ->limit(40)
            ->get();

        $stats = [
            'active' => MomentSpotlight::query()->where('is_active', true)->where('status', MomentSpotlight::STATUS_ACTIVE)->count(),
            'archived' => MomentSpotlight::query()->where('status', MomentSpotlight::STATUS_ARCHIVED)->count(),
            'moments' => Moment::query()->published()->count(),
        ];

        return view('admin.moment-of-week.index', [
            'spotlights' => $spotlights,
            'candidateMoments' => $candidateMoments,
            'statusOptions' => MomentSpotlight::statusOptions(),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request, GamificationService $gamification): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateSpotlight($request);
        $data['selected_by'] = $request->user()->id;
        $data['is_active'] = $request->boolean('is_active');
        $data['published_at'] = $data['status'] === MomentSpotlight::STATUS_ACTIVE ? now() : null;

        if ($data['is_active'] && $data['status'] === MomentSpotlight::STATUS_ACTIVE) {
            MomentSpotlight::query()->update(['is_active' => false]);
        }

        $spotlight = MomentSpotlight::create($data);

        $this->awardSpotlightOwner($spotlight->fresh(['moment.user']) ?? $spotlight, $gamification);

        return redirect()->route('admin.moment-of-week.index')->with('status', __('ui.moment_week_admin_created'));
    }

    public function update(Request $request, MomentSpotlight $spotlight, GamificationService $gamification): RedirectResponse
    {
        $this->guardAdmin($request);

        $data = $this->validateSpotlight($request);
        $data['selected_by'] = $request->user()->id;
        $data['is_active'] = $request->boolean('is_active');
        $data['published_at'] = $data['status'] === MomentSpotlight::STATUS_ACTIVE ? ($spotlight->published_at ?: now()) : null;

        if ($data['is_active'] && $data['status'] === MomentSpotlight::STATUS_ACTIVE) {
            MomentSpotlight::query()->whereKeyNot($spotlight->id)->update(['is_active' => false]);
        }

        $wasAwardable = $this->isAwardableSpotlight($spotlight);

        $spotlight->update($data);

        $freshSpotlight = $spotlight->fresh(['moment.user']) ?? $spotlight;
        if (! $wasAwardable && $this->isAwardableSpotlight($freshSpotlight)) {
            $this->awardSpotlightOwner($freshSpotlight, $gamification);
        }

        return redirect()->route('admin.moment-of-week.index')->with('status', __('ui.moment_week_admin_updated'));
    }

    public function destroy(Request $request, MomentSpotlight $spotlight): RedirectResponse
    {
        $this->guardAdmin($request);
        $spotlight->delete();

        return redirect()->route('admin.moment-of-week.index')->with('status', __('ui.moment_week_admin_deleted'));
    }

    private function validateSpotlight(Request $request): array
    {
        $data = $request->validate([
            'moment_id' => ['required', 'integer', 'exists:moments,id'],
            'title' => ['nullable', 'string', 'max:160'],
            'note' => ['nullable', 'string', 'max:2000'],
            'week_starts_at' => ['nullable', 'date'],
            'week_ends_at' => ['nullable', 'date', 'after_or_equal:week_starts_at'],
            'status' => ['required', 'string', Rule::in(array_keys(MomentSpotlight::statusOptions()))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['week_starts_at'] = $data['week_starts_at'] ?: null;
        $data['week_ends_at'] = $data['week_ends_at'] ?: null;
        $data['title'] = $data['title'] ?: null;
        $data['note'] = $data['note'] ?: null;

        return $data;
    }

    private function isAwardableSpotlight(MomentSpotlight $spotlight): bool
    {
        return $spotlight->status === MomentSpotlight::STATUS_ACTIVE && (bool) $spotlight->is_active;
    }

    private function awardSpotlightOwner(MomentSpotlight $spotlight, GamificationService $gamification): void
    {
        if (! $this->isAwardableSpotlight($spotlight)) {
            return;
        }

        $spotlight->loadMissing(['moment.user']);
        $owner = $spotlight->moment?->user;

        if (! $owner) {
            return;
        }

        $gamification->award(
            $owner,
            'moment_of_week_selected',
            source: $spotlight,
            description: __('ui.moment_week_xp_selected'),
            metadata: ['moment_id' => $spotlight->moment_id]
        );
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
