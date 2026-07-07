<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Cup;
use App\Models\CupChatMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CupChatApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authentication_is_required(): void
    {
        $cup = $this->cup($this->user());

        $this->getJson('/api/v1/cups/'.$cup->slug.'/chat')->assertUnauthorized();
        $this->postJson('/api/v1/cups/'.$cup->slug.'/chat', ['body' => 'Hello'])->assertUnauthorized();
    }

    public function test_authenticated_user_can_read_cup_chat_messages_with_author_data(): void
    {
        $owner = $this->user(['name' => 'Cup Owner', 'username' => 'cup_owner']);
        $viewer = $this->user();
        $cup = $this->cup($owner);

        $older = $this->message($cup, $owner, 'First callout', now()->subMinute());
        $newer = $this->message($cup, $viewer, 'Second callout', now());

        $this->getAs($viewer, '/api/v1/cups/'.$cup->slug.'/chat')
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('can_write', true)
            ->assertJsonPath('messages.0.id', $older->id)
            ->assertJsonPath('messages.0.body', 'First callout')
            ->assertJsonPath('messages.0.user.id', $owner->id)
            ->assertJsonPath('messages.0.user.name', 'Cup Owner')
            ->assertJsonPath('messages.0.user.username', 'cup_owner')
            ->assertJsonPath('messages.0.cup.slug', $cup->slug)
            ->assertJsonPath('messages.0.is_own', false)
            ->assertJsonPath('messages.1.id', $newer->id)
            ->assertJsonPath('messages.1.is_own', true)
            ->assertJsonStructure([
                'messages' => [
                    ['id', 'body', 'created_at', 'created_time', 'is_own', 'can_write', 'cup', 'user'],
                ],
            ]);
    }

    public function test_authenticated_user_can_post_cup_chat_message(): void
    {
        $owner = $this->user();
        $user = $this->user(['name' => 'Posting Hunter', 'username' => 'posting_hunter']);
        $cup = $this->cup($owner);

        $this->postAs($user, '/api/v1/cups/'.$cup->slug.'/chat', [
            'body' => 'See you at extraction.',
        ])->assertCreated()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('can_write', true)
            ->assertJsonPath('message.body', 'See you at extraction.')
            ->assertJsonPath('message.user.id', $user->id)
            ->assertJsonPath('message.user.username', 'posting_hunter')
            ->assertJsonPath('message.cup.slug', $cup->slug)
            ->assertJsonPath('message.is_own', true);

        $this->assertDatabaseHas('cup_chat_messages', [
            'cup_id' => $cup->id,
            'user_id' => $user->id,
            'body' => 'See you at extraction.',
        ]);
    }

    public function test_empty_body_is_rejected(): void
    {
        $cup = $this->cup($this->user());

        $this->postAs($this->user(), '/api/v1/cups/'.$cup->slug.'/chat', [
            'body' => '',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_too_long_body_is_rejected(): void
    {
        $cup = $this->cup($this->user());

        $this->postAs($this->user(), '/api/v1/cups/'.$cup->slug.'/chat', [
            'body' => str_repeat('x', 1201),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('body');
    }

    public function test_messages_are_scoped_to_the_requested_cup(): void
    {
        $user = $this->user();
        $targetCup = $this->cup($user, ['slug' => 'target-cup', 'title' => 'Target Cup']);
        $otherCup = $this->cup($user, ['slug' => 'other-cup', 'title' => 'Other Cup']);

        $targetMessage = $this->message($targetCup, $user, 'Target only');
        $this->message($otherCup, $user, 'Other only');

        $this->getAs($user, '/api/v1/cups/'.$targetCup->slug.'/chat')
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('messages.0.id', $targetMessage->id)
            ->assertJsonPath('messages.0.body', 'Target only')
            ->assertJsonMissing(['body' => 'Other only']);
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function postAs(User $user, string $uri, array $payload = [])
    {
        return $this->withToken($this->token($user))->postJson($uri, $payload);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Cup Chat API test')['access_token'];
    }

    private function user(array $attributes = []): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create(array_merge([
            'name' => 'Hunter '.$suffix,
            'username' => 'hunter_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ], $attributes));
    }

    private function cup(User $owner, array $attributes = []): Cup
    {
        $suffix = bin2hex(random_bytes(5));

        return Cup::query()->create(array_merge([
            'owner_id' => $owner->id,
            'title' => 'Cup '.$suffix,
            'slug' => 'cup-'.$suffix,
            'team_size' => 3,
            'status' => 'active',
            'visibility' => 'public',
        ], $attributes));
    }

    private function message(Cup $cup, User $user, string $body, mixed $createdAt = null): CupChatMessage
    {
        $attributes = [
            'cup_id' => $cup->id,
            'user_id' => $user->id,
            'body' => $body,
        ];

        if ($createdAt !== null) {
            $attributes['created_at'] = $createdAt;
            $attributes['updated_at'] = $createdAt;
        }

        return CupChatMessage::query()->create($attributes);
    }
}
