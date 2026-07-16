<?php

namespace Tests\Feature\Concerns;

use App\Models\ApiAccessToken;
use App\Models\Team;
use App\Models\TeamContractTemplate;
use App\Models\User;

trait CreatesTeamDomain
{
    protected function user(string $prefix = 'hunter'): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => ucfirst($prefix).' '.$suffix,
            'username' => $prefix.'_'.$suffix,
            'email' => $prefix.'_'.$suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }

    protected function team(?User $owner = null, array $members = [], array $overrides = []): Team
    {
        $owner ??= $this->user('owner');
        $suffix = bin2hex(random_bytes(4));
        $team = Team::query()->create([
            'owner_id' => $owner->id,
            'name' => 'Team '.$suffix,
            'slug' => 'team-'.$suffix,
            'visibility' => $overrides['visibility'] ?? 'public',
            'recruitment_status' => 'open',
            'status' => 'active',
        ]);

        $team->members()->create(['user_id' => $owner->id, 'role' => 'owner', 'status' => 'active', 'joined_at' => now()]);
        foreach ($members as $member) {
            [$user, $role, $status] = [$member[0], $member[1] ?? 'member', $member[2] ?? 'active'];
            $team->members()->create(['user_id' => $user->id, 'role' => $role, 'status' => $status, 'joined_at' => $status === 'active' ? now() : null]);
        }

        return $team;
    }

    protected function template(array $overrides = []): TeamContractTemplate
    {
        $suffix = bin2hex(random_bytes(4));

        return TeamContractTemplate::query()->create([
            'key' => $overrides['key'] ?? 'template-'.$suffix,
            'name_key' => 'teams.contracts.test.name',
            'description_key' => 'teams.contracts.test.description',
            'category' => $overrides['category'] ?? 'community',
            'metric' => $overrides['metric'] ?? 'team_post_created',
            'target_value' => $overrides['target_value'] ?? 2,
            'minimum_contributors' => $overrides['minimum_contributors'] ?? 1,
            'duration_days' => $overrides['duration_days'] ?? 7,
            'is_repeatable' => $overrides['is_repeatable'] ?? true,
            'cooldown_days' => $overrides['cooldown_days'] ?? null,
            'team_xp_reward' => $overrides['team_xp_reward'] ?? 250,
            'rocks_reward' => $overrides['rocks_reward'] ?? 20,
            'is_active' => true,
        ]);
    }

    protected function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Team domain test')['access_token'];
    }
}
