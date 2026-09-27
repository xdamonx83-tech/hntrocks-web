<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProfileModerationEvent;
use App\Models\ProfileModerationFlag;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\GamificationService;
use App\Services\ProfileModerationService;
use App\Services\ReferralService;
use App\Support\HunterDna;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $users = User::query()
            ->with('profile')
            ->withCount([
                'profileModerationFlags as pending_profile_flags_count' => fn ($query) => $query
                    ->where('status', ProfileModerationFlag::STATUS_PENDING),
            ])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $term = '%'.trim((string) $request->string('q')).'%';

                $query->where(function ($subQuery) use ($term): void {
                    $subQuery->where('name', 'like', $term)
                        ->orWhere('username', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhereHas('profile', function ($profileQuery) use ($term): void {
                            $profileQuery
                                ->where('headline', 'like', $term)
                                ->orWhere('bio', 'like', $term);
                        });
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

    public function edit(Request $request, User $user): View
    {
        $this->guardAdmin($request);

        $user->loadMissing('profile');

        $profile = $user->profile;
        if (! $profile) {
            $profile = $user->profile()->create([
                'profile_visibility' => 'public',
            ]);
            $user->setRelation('profile', $profile);
        }

        $openFlags = ProfileModerationFlag::query()
            ->where('user_id', $user->id)
            ->where('status', ProfileModerationFlag::STATUS_PENDING)
            ->orderByDesc('score')
            ->orderByDesc('detected_at')
            ->get();

        $moderationEvents = ProfileModerationEvent::query()
            ->with('admin:id,name,username')
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit(30)
            ->get();

        return view('admin.users.edit', [
            'editedUser' => $user,
            'profile' => $profile,
            'openFlags' => $openFlags,
            'moderationEvents' => $moderationEvents,
        ]);
    }

    public function update(
        Request $request,
        User $user,
        ProfileModerationService $profileModeration,
        GamificationService $gamification,
        ReferralService $referrals
    ): RedirectResponse {
        $this->guardAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'username' => [
                'required',
                'string',
                'alpha_dash',
                'min:3',
                'max:32',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:160',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'hunt_role' => ['nullable', 'string', 'max:60'],
            'discord_name' => ['nullable', 'string', 'max:80'],
            'steam_url' => ['nullable', 'url', 'max:255'],
            'twitch_url' => ['nullable', 'url', 'max:255'],
            'youtube_url' => ['nullable', 'url', 'max:255'],
            'is_lfg_available' => ['nullable', 'boolean'],
            'profile_visibility' => ['required', 'in:public,registered,private'],
            'hunter_dna' => ['nullable', 'array:voice,preferred_mode,experience,temper,goals,mentor'],
            'hunter_dna.voice' => ['nullable', Rule::in(['yes', 'no', 'optional'])],
            'hunter_dna.preferred_mode' => ['nullable', Rule::in(['solo', 'duo', 'trio', 'flexible'])],
            'hunter_dna.experience' => ['nullable', Rule::in(['new', 'casual', 'experienced', 'veteran'])],
            'hunter_dna.temper' => ['nullable', Rule::in(['chill', 'focused', 'tryhard', 'chaotic'])],
            'hunter_dna.goals' => ['nullable', 'array', 'max:5'],
            'hunter_dna.goals.*' => ['nullable', Rule::in(['pvp', 'bounty', 'boss', 'extract', 'events', 'quests', 'teach', 'learn', 'memes'])],
            'hunter_dna.mentor' => ['nullable', 'boolean'],
            'hunter_dna_present' => ['nullable', 'boolean'],
            'moderation_reason' => ['nullable', 'string', 'max:120'],
            'moderation_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $profile = DB::transaction(function () use ($request, $user, $validated): UserProfile {
            $user->loadMissing('profile');

            $oldAccount = [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ];

            $profile = $user->profile ?: new UserProfile(['user_id' => $user->id]);
            $oldProfile = [];

            foreach ($this->editableProfileFields() as $field) {
                $oldProfile[$field] = $profile->{$field};
            }
            $oldHunterDna = HunterDna::normalize($profile->hunter_dna);

            $user->forceFill([
                'name' => trim((string) $validated['name']),
                'username' => trim((string) $validated['username']),
                'email' => trim((string) $validated['email']),
            ])->save();

            $profileData = [];
            foreach ($this->editableProfileFields() as $field) {
                if ($field === 'is_lfg_available') {
                    $profileData[$field] = (bool) ($validated[$field] ?? false);
                    continue;
                }

                $value = trim((string) ($validated[$field] ?? ''));
                $profileData[$field] = $value === '' ? null : $value;
            }

            if ($request->boolean('hunter_dna_present')) {
                $hunterDnaInput = $validated['hunter_dna'] ?? [];
                $hunterDnaInput['mentor'] = $request->boolean('hunter_dna.mentor');
                $hunterDna = HunterDna::normalize($hunterDnaInput);
                $profileData['hunter_dna'] = $hunterDna === [] ? null : $hunterDna;
                $profileData['hunter_dna_completed_at'] = HunterDna::isComplete($hunterDna) ? now() : null;
            }

            $profile = $user->profile()->updateOrCreate(
                ['user_id' => $user->id],
                $profileData
            );

            $reason = trim((string) ($validated['moderation_reason'] ?? ''));
            $note = trim((string) ($validated['moderation_note'] ?? ''));

            foreach ($oldAccount as $field => $oldValue) {
                $newValue = $user->{$field};
                if ((string) $oldValue === (string) $newValue) {
                    continue;
                }

                $this->recordEvent(
                    $request,
                    $user,
                    'admin_profile_edit',
                    $field,
                    $oldValue,
                    $newValue,
                    $reason,
                    $note
                );
            }

            foreach ($oldProfile as $field => $oldValue) {
                $newValue = $profile->{$field};

                if ($this->sameValue($oldValue, $newValue)) {
                    continue;
                }

                $this->recordEvent(
                    $request,
                    $user,
                    'admin_profile_edit',
                    $field,
                    $oldValue,
                    $newValue,
                    $reason,
                    $note
                );
            }

            if ($request->boolean('hunter_dna_present')) {
                $newHunterDna = HunterDna::normalize($profile->hunter_dna);

                if ($oldHunterDna !== $newHunterDna) {
                    $this->recordEvent(
                        $request,
                        $user,
                        'admin_profile_edit',
                        'hunter_dna',
                        json_encode($oldHunterDna, JSON_UNESCAPED_UNICODE),
                        json_encode($newHunterDna, JSON_UNESCAPED_UNICODE),
                        $reason,
                        $note
                    );
                }
            }

            return $profile;
        });

        $profileModeration->scan($profile);
        $gamification->evaluateProfile($user);
        $freshUser = $user->fresh(['profile']) ?? $user;
        $referrals->syncProfileCompletion($freshUser);

        return redirect()
            ->route('admin.users.edit', $freshUser)
            ->with('status', 'Nutzerprofil wurde aktualisiert und erneut geprüft.');
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

        $oldStatus = (string) ($user->status ?? 'active');

        $user->forceFill([
            'status' => $validated['status'],
            'suspended_at' => $validated['status'] === 'suspended' ? now() : null,
        ])->save();

        if ($oldStatus !== $validated['status']) {
            $this->recordEvent(
                $request,
                $user,
                'account_status_changed',
                'status',
                $oldStatus,
                $validated['status']
            );
        }

        return back()->with('status', 'Nutzerstatus wurde aktualisiert.');
    }

    public function toggleAdmin(Request $request, User $user): RedirectResponse
    {
        $this->guardAdmin($request);

        if ((int) $request->user()->id === (int) $user->id && $user->is_admin) {
            return back()->with('status', 'Du kannst dir selbst nicht die Adminrechte entziehen.');
        }

        $oldValue = (bool) $user->is_admin;

        $user->forceFill([
            'is_admin' => ! $oldValue,
        ])->save();

        $this->recordEvent(
            $request,
            $user,
            'admin_role_changed',
            'is_admin',
            $oldValue ? '1' : '0',
            $user->is_admin ? '1' : '0'
        );

        return back()->with('status', 'Adminrechte wurden aktualisiert.');
    }

    /**
     * @return array<int, string>
     */
    private function editableProfileFields(): array
    {
        return [
            'headline',
            'bio',
            'platform',
            'playstyle',
            'region',
            'language',
            'hunt_role',
            'discord_name',
            'steam_url',
            'twitch_url',
            'youtube_url',
            'is_lfg_available',
            'profile_visibility',
        ];
    }

    private function recordEvent(
        Request $request,
        User $user,
        string $action,
        ?string $field = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $reason = null,
        ?string $note = null
    ): void {
        ProfileModerationEvent::query()->create([
            'user_id' => $user->id,
            'admin_id' => $request->user()?->id,
            'action' => $action,
            'field' => $field,
            'old_value' => $oldValue === null ? null : (string) $oldValue,
            'new_value' => $newValue === null ? null : (string) $newValue,
            'reason' => filled($reason) ? $reason : null,
            'note' => filled($note) ? $note : null,
        ]);
    }

    private function sameValue(mixed $oldValue, mixed $newValue): bool
    {
        if (is_bool($oldValue) || is_bool($newValue)) {
            return (bool) $oldValue === (bool) $newValue;
        }

        return (string) ($oldValue ?? '') === (string) ($newValue ?? '');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }
}
