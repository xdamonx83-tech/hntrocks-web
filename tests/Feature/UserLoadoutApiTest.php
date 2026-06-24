<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\User;
use App\Models\UserLoadout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserLoadoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_list_a_loadout(): void
    {
        $user = $this->user();

        $this->postAs($user, '/api/v1/me/loadouts', [
            'title' => 'Boss Killer',
            'primary_weapon' => 'Romero 77',
            'secondary_weapon' => 'Conversion Pistol',
            'tools' => ['Knife', 'Medkit'],
            'consumables' => ['Vitality Shot'],
            'traits' => ['Packmule'],
            'note' => 'Close range and affordable.',
            'visibility' => 'private',
            'sort_order' => 2,
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Boss Killer')
            ->assertJsonPath('data.visibility', 'private');

        $this->getAs($user, '/api/v1/me/loadouts')
            ->assertOk()
            ->assertJsonPath('section', 'loadouts')
            ->assertJsonPath('items.0.title', 'Boss Killer')
            ->assertJsonPath('items.0.visibility', 'private')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('meta.limit', 3);
    }

    public function test_user_may_have_at_most_three_active_loadouts(): void
    {
        $user = $this->user();

        foreach (range(1, 3) as $number) {
            $this->postAs($user, '/api/v1/me/loadouts', ['title' => 'Loadout '.$number])->assertCreated();
        }

        $this->postAs($user, '/api/v1/me/loadouts', ['title' => 'Fourth'])->assertUnprocessable();
        $this->assertSame(3, $user->loadouts()->count());
    }

    public function test_array_limits_are_enforced_after_normalization(): void
    {
        $user = $this->user();

        foreach ([
            'tools' => ['1', '2', '3', '4', '5'],
            'consumables' => ['1', '2', '3', '4', '5'],
            'traits' => ['1', '2', '3', '4', '5', '6', '7'],
        ] as $field => $values) {
            $this->postAs($user, '/api/v1/me/loadouts', [
                'title' => 'Invalid '.$field,
                $field => $values,
            ])->assertUnprocessable()->assertJsonValidationErrors($field);
        }
    }

    public function test_array_values_are_trimmed_and_empty_or_duplicate_values_are_removed(): void
    {
        $user = $this->user();

        $this->postAs($user, '/api/v1/me/loadouts', [
            'title' => 'Clean arrays',
            'tools' => [' Knife ', '', 'Knife', '  ', 'Medkit'],
            'consumables' => [' Vitality Shot ', 'Vitality Shot'],
            'traits' => [' Packmule ', 'Packmule', 'Doctor'],
        ])->assertCreated()
            ->assertJsonPath('data.tools', ['Knife', 'Medkit'])
            ->assertJsonPath('data.consumables', ['Vitality Shot'])
            ->assertJsonPath('data.traits', ['Packmule', 'Doctor']);

        $loadout = $user->loadouts()->firstOrFail();
        $this->assertSame(['Knife', 'Medkit'], $loadout->tools);
    }

    public function test_public_profile_section_only_shows_public_active_loadouts(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $this->loadout($owner, ['title' => 'Public active']);
        $this->loadout($owner, ['title' => 'Private active', 'visibility' => 'private']);
        $this->loadout($owner, ['title' => 'Public inactive', 'is_active' => false]);

        $this->getAs($viewer, '/api/v1/users/'.$owner->username.'/profile-sections/loadouts')
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.title', 'Public active')
            ->assertJsonMissing(['title' => 'Private active'])
            ->assertJsonMissing(['title' => 'Public inactive'])
            ->assertJsonMissingPath('items.0.user_id');

        $this->getAs($owner, '/api/v1/me/loadouts')
            ->assertOk()
            ->assertJsonCount(3, 'items')
            ->assertJsonFragment(['title' => 'Private active']);
    }

    public function test_foreign_user_cannot_update_or_delete_a_loadout(): void
    {
        $owner = $this->user();
        $other = $this->user();
        $loadout = $this->loadout($owner);

        $this->patchAs($other, '/api/v1/me/loadouts/'.$loadout->id, ['title' => 'Stolen'])
            ->assertNotFound();
        $this->deleteAs($other, '/api/v1/me/loadouts/'.$loadout->id)
            ->assertNotFound();

        $this->assertDatabaseHas('user_loadouts', ['id' => $loadout->id, 'title' => 'Loadout']);
    }

    public function test_owner_can_update_and_delete_a_loadout(): void
    {
        $user = $this->user();
        $loadout = $this->loadout($user);

        $this->patchAs($user, '/api/v1/me/loadouts/'.$loadout->id, [
            'title' => 'Updated',
            'visibility' => 'private',
        ])->assertOk()
            ->assertJsonPath('data.title', 'Updated')
            ->assertJsonPath('data.visibility', 'private');

        $this->deleteAs($user, '/api/v1/me/loadouts/'.$loadout->id)->assertOk();
        $this->assertDatabaseMissing('user_loadouts', ['id' => $loadout->id]);
    }

    public function test_external_links_and_html_are_rejected_in_free_text_fields(): void
    {
        foreach ([
            'title' => 'https://example.com',
            'primary_weapon' => 'www.example.com',
            'secondary_weapon' => '<b>Dolch</b>',
            'note' => 'discord.gg/hunt',
            'tools' => ['Knife', 'custom://invite'],
        ] as $field => $unsafe) {
            $payload = ['title' => 'Safe title', $field => $unsafe];
            $this->postAs($this->user(), '/api/v1/me/loadouts', $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(is_array($unsafe) ? $field.'.1' : $field);
        }
    }

    public function test_existing_profile_sections_still_work(): void
    {
        $this->getAs($this->user(), '/api/v1/me/profile-sections/badges')
            ->assertOk()
            ->assertJsonPath('section', 'badges');
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function postAs(User $user, string $uri, array $payload = [])
    {
        return $this->withToken($this->token($user))->postJson($uri, $payload);
    }

    private function patchAs(User $user, string $uri, array $payload = [])
    {
        return $this->withToken($this->token($user))->patchJson($uri, $payload);
    }

    private function deleteAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->deleteJson($uri);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Loadout API test')['access_token'];
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

    private function loadout(User $user, array $attributes = []): UserLoadout
    {
        return $user->loadouts()->create(array_merge([
            'title' => 'Loadout',
            'visibility' => 'public',
            'is_active' => true,
        ], $attributes));
    }
}
