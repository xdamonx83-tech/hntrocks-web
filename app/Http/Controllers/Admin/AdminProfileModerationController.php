<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileModerationEvent;
use App\Models\ProfileModerationFlag;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\ProfileModerationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminProfileModerationController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $status = (string) $request->query('status', ProfileModerationFlag::STATUS_PENDING);
        $allowedStatuses = [
            ProfileModerationFlag::STATUS_PENDING,
            ProfileModerationFlag::STATUS_CONFIRMED,
            ProfileModerationFlag::STATUS_DISMISSED,
            ProfileModerationFlag::STATUS_ACTIONED,
            ProfileModerationFlag::STATUS_SUPERSEDED,
        ];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = ProfileModerationFlag::STATUS_PENDING;
        }

        $flags = ProfileModerationFlag::query()
            ->with(['user.profile', 'reviewedBy:id,name,username'])
            ->where('status', $status)
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('q')).'%';

                $query->where(function ($search) use ($term): void {
                    $search
                        ->where('excerpt', 'like', $term)
                        ->orWhere('reason', 'like', $term)
                        ->orWhereHas('user', function ($userQuery) use ($term): void {
                            $userQuery
                                ->where('name', 'like', $term)
                                ->orWhere('username', 'like', $term)
                                ->orWhere('email', 'like', $term);
                        });
                });
            })
            ->when($request->filled('category'), fn ($query) => $query->where('category', $request->string('category')))
            ->when($request->filled('min_score'), fn ($query) => $query->where('score', '>=', max(0, min(100, (int) $request->integer('min_score')))))
            ->orderByDesc('score')
            ->orderByDesc('detected_at')
            ->paginate(30)
            ->withQueryString();

        $categories = ProfileModerationFlag::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $counts = [
            'pending' => ProfileModerationFlag::query()->where('status', ProfileModerationFlag::STATUS_PENDING)->count(),
            'high' => ProfileModerationFlag::query()
                ->where('status', ProfileModerationFlag::STATUS_PENDING)
                ->where('score', '>=', 80)
                ->count(),
            'confirmed' => ProfileModerationFlag::query()->where('status', ProfileModerationFlag::STATUS_CONFIRMED)->count(),
            'actioned' => ProfileModerationFlag::query()->where('status', ProfileModerationFlag::STATUS_ACTIONED)->count(),
        ];

        return view('admin.profile-moderation.index', [
            'flags' => $flags,
            'categories' => $categories,
            'counts' => $counts,
            'filters' => [
                'q' => $request->query('q'),
                'status' => $status,
                'category' => $request->query('category'),
                'min_score' => $request->query('min_score'),
            ],
        ]);
    }

    public function review(Request $request, ProfileModerationFlag $flag): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'decision' => ['required', Rule::in([
                ProfileModerationFlag::STATUS_CONFIRMED,
                ProfileModerationFlag::STATUS_DISMISSED,
            ])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $flag->forceFill([
            'status' => $validated['decision'],
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'admin_note' => trim((string) ($validated['admin_note'] ?? '')) ?: null,
        ])->save();

        ProfileModerationEvent::query()->create([
            'user_id' => $flag->user_id,
            'admin_id' => $request->user()->id,
            'action' => $validated['decision'] === ProfileModerationFlag::STATUS_CONFIRMED
                ? 'flag_confirmed'
                : 'flag_dismissed',
            'field' => $flag->field,
            'reason' => $flag->category,
            'note' => $flag->admin_note,
            'metadata' => [
                'flag_id' => $flag->id,
                'score' => $flag->score,
                'reason' => $flag->reason,
            ],
        ]);

        return back()->with('status', 'Moderationshinweis wurde bearbeitet.');
    }

    public function rescan(Request $request, User $user, ProfileModerationService $moderation): RedirectResponse
    {
        $this->guardAdmin($request);

        $profile = $user->profile()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $moderation->scan($profile);

        ProfileModerationEvent::query()->create([
            'user_id' => $user->id,
            'admin_id' => $request->user()->id,
            'action' => 'profile_rescanned',
        ]);

        return back()->with('status', 'Profil wurde erneut automatisch geprüft.');
    }

    public function hideField(Request $request, User $user, ProfileModerationService $moderation): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'field' => ['required', Rule::in(['headline', 'bio'])],
            'reason' => ['required', 'string', 'max:120'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $user->profile()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $field = $validated['field'];
        $oldValue = $profile->{$field};

        if (blank($oldValue)) {
            return back()->with('status', ucfirst($field).' ist bereits leer.');
        }

        $profile->forceFill([
            $field => null,
        ])->save();

        ProfileModerationFlag::query()
            ->where('user_id', $user->id)
            ->where('field', $field)
            ->whereIn('status', [
                ProfileModerationFlag::STATUS_PENDING,
                ProfileModerationFlag::STATUS_CONFIRMED,
            ])
            ->update([
                'status' => ProfileModerationFlag::STATUS_ACTIONED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => trim((string) ($validated['admin_note'] ?? '')) ?: null,
            ]);

        ProfileModerationEvent::query()->create([
            'user_id' => $user->id,
            'admin_id' => $request->user()->id,
            'action' => 'profile_field_hidden',
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => null,
            'reason' => $validated['reason'],
            'note' => trim((string) ($validated['admin_note'] ?? '')) ?: null,
        ]);

        $moderation->scan($profile);

        return back()->with('status', ucfirst($field).' wurde ausgeblendet und kann wiederhergestellt werden.');
    }

    public function restoreField(Request $request, User $user, ProfileModerationService $moderation): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'field' => ['required', Rule::in(['headline', 'bio'])],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $field = $validated['field'];

        $lastStateEvent = ProfileModerationEvent::query()
            ->where('user_id', $user->id)
            ->where('field', $field)
            ->whereIn('action', [
                'profile_field_hidden',
                'profile_field_restored',
                'profile_field_cleared',
                'admin_profile_edit',
            ])
            ->latest('id')
            ->first();

        if (! $lastStateEvent || $lastStateEvent->action !== 'profile_field_hidden' || blank($lastStateEvent->old_value)) {
            return back()->with('status', 'Für dieses Feld gibt es keinen aktuell ausgeblendeten Inhalt.');
        }

        $profile = $user->profile()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $currentValue = $profile->{$field};
        $restoredValue = $lastStateEvent->old_value;

        $profile->forceFill([
            $field => $restoredValue,
        ])->save();

        ProfileModerationEvent::query()->create([
            'user_id' => $user->id,
            'admin_id' => $request->user()->id,
            'action' => 'profile_field_restored',
            'field' => $field,
            'old_value' => $currentValue,
            'new_value' => $restoredValue,
            'reason' => 'Moderationsinhalt wiederhergestellt',
            'note' => trim((string) ($validated['admin_note'] ?? '')) ?: null,
            'metadata' => [
                'source_event_id' => $lastStateEvent->id,
            ],
        ]);

        $moderation->scan($profile);

        return back()->with('status', ucfirst($field).' wurde wiederhergestellt und erneut geprüft.');
    }

    public function clearField(Request $request, User $user, ProfileModerationService $moderation): RedirectResponse
    {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'field' => ['required', Rule::in(['headline', 'bio'])],
            'reason' => ['required', 'string', 'max:120'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = $user->profile()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $field = $validated['field'];
        $oldValue = $profile->{$field};

        $profile->forceFill([
            $field => null,
        ])->save();

        ProfileModerationFlag::query()
            ->where('user_id', $user->id)
            ->where('field', $field)
            ->where('status', ProfileModerationFlag::STATUS_PENDING)
            ->update([
                'status' => ProfileModerationFlag::STATUS_ACTIONED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'admin_note' => trim((string) ($validated['admin_note'] ?? '')) ?: null,
            ]);

        ProfileModerationEvent::query()->create([
            'user_id' => $user->id,
            'admin_id' => $request->user()->id,
            'action' => 'profile_field_cleared',
            'field' => $field,
            'old_value' => $oldValue,
            'new_value' => null,
            'reason' => $validated['reason'],
            'note' => trim((string) ($validated['admin_note'] ?? '')) ?: null,
        ]);

        $moderation->scan($profile);

        return back()->with('status', ucfirst($field).' wurde entfernt.');
    }

    public function backfill(Request $request, ProfileModerationService $moderation): RedirectResponse
    {
        $this->guardAdmin($request);

        $scanned = 0;

        UserProfile::query()
            ->with('user')
            ->orderBy('id')
            ->chunkById(200, function ($profiles) use ($moderation, &$scanned): void {
                foreach ($profiles as $profile) {
                    $moderation->scan($profile);
                    $scanned++;
                }
            });

        return back()->with('status', $scanned.' bestehende Profile wurden geprüft.');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
