<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\User;
use App\Models\UserLoadout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityLoadoutApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hunters_can_publish_loadouts_without_using_featured_profile_slots(): void
    {
        $user = $this->hunter();
        $this->postAs($user, '/api/v1/loadouts', [
            'title' => 'Bayou ambush',
            'primary_weapon' => 'Romero 77',
            'secondary_weapon' => 'Conversion Pistol',
            'primary_ammo' => 'Buckshot',
            'tools' => ['Knife', 'Medkit'],
            'traits' => ['Packmule'],
        ])->assertCreated()
            ->assertJsonPath('data.loadout.is_published', true)
            ->assertJsonPath('data.loadout.primary_ammo', 'Buckshot');

        $loadout = $user->loadouts()->firstOrFail();
        $this->assertFalse($loadout->is_active);
        $this->getAs($user, '/api/v1/loadouts')
            ->assertOk()
            ->assertJsonPath('data.loadouts.0.title', 'Bayou ambush');
    }

    public function test_private_and_unpublished_loadouts_are_not_discoverable(): void
    {
        $owner = $this->hunter();
        $viewer = $this->hunter();
        $private = $this->loadout($owner, ['title' => 'Private', 'visibility' => 'private', 'is_published' => true]);
        $draft = $this->loadout($owner, ['title' => 'Draft', 'is_published' => false]);
        $public = $this->loadout($owner, ['title' => 'Public']);

        $this->getAs($viewer, '/api/v1/loadouts')->assertJsonCount(1, 'data.loadouts')
            ->assertJsonPath('data.loadouts.0.id', $public->id);
        $this->getAs($viewer, '/api/v1/loadouts/'.$private->id)->assertNotFound();
        $this->getAs($viewer, '/api/v1/loadouts/'.$draft->id)->assertNotFound();
        $this->getAs($owner, '/api/v1/loadouts/'.$private->id)->assertOk();
    }

    public function test_vote_is_unique_and_can_be_changed_or_removed(): void
    {
        $owner = $this->hunter();
        $viewer = $this->hunter();
        $loadout = $this->loadout($owner);
        $url = '/api/v1/loadouts/'.$loadout->id.'/vote';

        $this->postAs($viewer, $url, ['value' => 1])->assertOk()
            ->assertJsonPath('data.loadout.score', 1)
            ->assertJsonPath('data.loadout.viewer_vote', 1);
        $this->postAs($viewer, $url, ['value' => -1])->assertOk()
            ->assertJsonPath('data.loadout.score', -1);
        $this->assertDatabaseCount('user_loadout_votes', 1);
        $this->getAs($viewer, '/api/v1/loadouts/'.$loadout->id)
            ->assertJsonPath('data.loadout.viewer_vote', -1);
        $this->postAs($viewer, $url, ['value' => 0])->assertOk()
            ->assertJsonPath('data.loadout.score', 0);
        $this->assertDatabaseCount('user_loadout_votes', 0);
        $this->postAs($owner, $url, ['value' => 1])->assertUnprocessable();
    }

    public function test_popular_and_discussed_sorting_uses_votes_and_comments(): void
    {
        $author = $this->hunter();
        $voter = $this->hunter();
        $quiet = $this->loadout($author, ['title' => 'Quiet']);
        $top = $this->loadout($author, ['title' => 'Popular']);
        $this->postAs($voter, '/api/v1/loadouts/'.$top->id.'/vote', ['value' => 1])->assertOk();
        $this->postAs($voter, '/api/v1/loadouts/'.$top->id.'/comments', ['body' => 'Great idea'])->assertCreated();
        $this->getAs($voter, '/api/v1/loadouts?sort=popular')
            ->assertJsonPath('data.loadouts.0.id', $top->id);
        $this->getAs($voter, '/api/v1/loadouts?sort=discussed')
            ->assertJsonPath('data.loadouts.0.id', $top->id);
    }

    public function test_comments_are_public_to_members_but_only_authors_can_remove_them(): void
    {
        $owner = $this->hunter();
        $poster = $this->hunter();
        $other = $this->hunter();
        $loadout = $this->loadout($owner);
        $comments = '/api/v1/loadouts/'.$loadout->id.'/comments';
        $response = $this->postAs($poster, $comments, ['body' => 'Try bleed ammo!'])
            ->assertCreated()->assertJsonPath('data.comment.body', 'Try bleed ammo!');
        $id = $response->json('data.comment.id');
        $this->getAs($other, $comments)->assertJsonCount(1, 'data.comments');
        $this->deleteAs($other, $comments.'/'.$id)->assertNotFound();
        $this->deleteAs($poster, $comments.'/'.$id)->assertOk();
        $this->getAs($other, $comments)->assertJsonCount(0, 'data.comments');
    }

    public function test_only_owners_can_edit_or_delete_and_unsafe_text_is_rejected(): void
    {
        $owner = $this->hunter();
        $other = $this->hunter();
        $loadout = $this->loadout($owner);
        $url = '/api/v1/loadouts/'.$loadout->id;
        $this->patchAs($other, $url, ['title' => 'Stolen'])->assertNotFound();
        $this->deleteAs($other, $url)->assertNotFound();
        $this->postAs($other, '/api/v1/loadouts', [
            'title' => '<script>bad</script>',
            'primary_weapon' => 'Romero 77',
        ])->assertUnprocessable();
        $this->postAs($other, $url.'/comments', ['body' => 'https://spam.test'])->assertUnprocessable();
        $this->patchAs($owner, $url, ['visibility' => 'private'])->assertOk()
            ->assertJsonPath('data.loadout.is_published', false);
        $this->getAs($other, $url)->assertNotFound();
        $this->deleteAs($owner, $url)->assertOk();
    }

    private function hunter(): User
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

    private function loadout(User $owner, array $extra = []): UserLoadout
    {
        return $owner->loadouts()->create(array_merge([
            'title' => 'Published',
            'primary_weapon' => 'Romero 77',
            'visibility' => 'public',
            'is_published' => true,
            'is_active' => false,
        ], $extra));
    }

    private function getAs(User $user, string $url)
    {
        return $this->withToken($this->token($user))->getJson($url);
    }

    private function postAs(User $user, string $url, array $data)
    {
        return $this->withToken($this->token($user))->postJson($url, $data);
    }

    private function patchAs(User $user, string $url, array $data)
    {
        return $this->withToken($this->token($user))->patchJson($url, $data);
    }

    private function deleteAs(User $user, string $url)
    {
        return $this->withToken($this->token($user))->deleteJson($url);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Community loadout test')['access_token'];
    }
}
