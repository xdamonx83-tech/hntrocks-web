<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HunterDnaProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_stores_valid_hunter_dna_and_returns_summary(): void
    {
        $user = $this->user();

        $response = $this->postProfile($user, [
            'platform' => 'pc',
            'hunter_dna' => $this->completeHunterDna(),
        ])->assertOk()
            ->assertJsonPath('user.profile.hunter_dna.voice', 'yes')
            ->assertJsonPath('user.profile.hunter_dna.preferred_mode', 'trio')
            ->assertJsonPath('user.profile.hunter_dna.goals.0', 'boss')
            ->assertJsonPath('user.profile.hunter_dna.mentor', false)
            ->assertJsonPath('user.profile.hunter_dna_summary.completed', true)
            ->assertJsonPath('user.profile.hunter_dna_summary.traits_count', 7);

        $this->assertSame(64, $response->json('user.profile.hunter_dna_summary.completion_score'));
        $this->assertSame($this->completeHunterDna(), $user->profile()->firstOrFail()->hunter_dna);
    }

    public function test_empty_hunter_dna_is_returned_consistently(): void
    {
        $this->postProfile($this->user(), ['hunter_dna' => []])
            ->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['profile' => ['hunter_dna', 'hunter_dna_completed_at', 'hunter_dna_summary']],
                'counts',
                'profile_summary',
            ])
            ->assertJsonPath('user.profile.hunter_dna', [])
            ->assertJsonPath('user.profile.hunter_dna_completed_at', null)
            ->assertJsonPath('user.profile.hunter_dna_summary.completion_score', 0);
    }

    public function test_invalid_hunter_dna_values_and_unknown_keys_are_rejected(): void
    {
        $this->postProfile($this->user(), [
            'hunter_dna' => [
                'voice' => 'sometimes',
                'unknown' => 'value',
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['hunter_dna', 'hunter_dna.voice']);
    }

    public function test_goals_are_deduplicated_while_request_order_is_preserved(): void
    {
        $user = $this->user();

        $this->postProfile($user, [
            'hunter_dna' => ['goals' => ['quests', 'pvp', 'quests', 'boss', 'pvp']],
        ])->assertOk()
            ->assertJsonPath('user.profile.hunter_dna.goals', ['quests', 'pvp', 'boss']);

        $this->assertSame(
            ['quests', 'pvp', 'boss'],
            $user->profile()->firstOrFail()->hunter_dna['goals']
        );
    }

    public function test_completed_at_is_set_at_four_dna_traits_and_cleared_when_incomplete(): void
    {
        $user = $this->user();

        $this->postProfile($user, [
            'hunter_dna' => [
                'voice' => 'optional',
                'preferred_mode' => 'flexible',
                'goals' => ['learn'],
                'mentor' => false,
            ],
        ])->assertOk();

        $this->assertNotNull($user->profile()->firstOrFail()->hunter_dna_completed_at);

        $this->postProfile($user, [
            'hunter_dna' => ['voice' => 'no'],
        ])->assertOk()
            ->assertJsonPath('user.profile.hunter_dna_summary.completed', false);

        $this->assertNull($user->profile()->firstOrFail()->hunter_dna_completed_at);
    }

    public function test_existing_profile_fields_remain_updateable(): void
    {
        $user = $this->user();

        $this->postProfile($user, [
            'platform' => 'playstation',
            'playstyle' => 'stealth',
            'region' => 'eu',
            'language' => 'de',
        ])->assertOk()
            ->assertJsonPath('user.profile.platform', 'playstation')
            ->assertJsonPath('user.profile.playstyle', 'stealth')
            ->assertJsonPath('user.profile.region', 'eu')
            ->assertJsonPath('user.profile.language', 'de');
    }

    private function postProfile(User $user, array $payload)
    {
        return $this->withToken($this->token($user))->postJson('/api/v1/me/profile', $payload);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Hunter DNA API test')['access_token'];
    }

    private function user(): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => 'Hunter '.$suffix,
            'username' => 'hunter_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }

    private function completeHunterDna(): array
    {
        return [
            'voice' => 'yes',
            'preferred_mode' => 'trio',
            'experience' => 'experienced',
            'temper' => 'focused',
            'goals' => ['boss'],
            'mentor' => false,
        ];
    }
}
