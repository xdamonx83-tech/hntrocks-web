<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\AppRemoteConfig;
use App\Models\AppRemoteFeedCard;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppRemoteConfigApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_remote_config_returns_defaults_without_database_config(): void
    {
        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.schema_version', 1)
            ->assertJsonPath('config.android.min_version_code', 8)
            ->assertJsonPath('config.features.feed_remote_cards_enabled', true)
            ->assertJsonPath('feed_cards', []);
    }

    public function test_active_feed_card_is_returned_and_sorted(): void
    {
        AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_test',
            'title_de' => 'Neue App-Steuerung aktiv',
            'body_de' => 'Wir koennen wichtige Hinweise jetzt direkt in der App anzeigen.',
            'cta_label_de' => 'Ansehen',
            'action_url' => 'hntrocks://notifications',
            'style_variant' => 'gold_glass',
            'priority' => 10,
            'is_active' => true,
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config?locale=de')
            ->assertOk()
            ->assertJsonPath('feed_cards.0.id', 'feed_card_test')
            ->assertJsonPath('feed_cards.0.title', 'Neue App-Steuerung aktiv')
            ->assertJsonPath('feed_cards.0.action_url', 'hntrocks://notifications');
    }

    public function test_dismissed_feed_card_is_not_returned_and_dismiss_is_idempotent(): void
    {
        $user = $this->user();
        AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_dismiss_me',
            'title_de' => 'Hinweis',
            'body_de' => 'Text',
            'priority' => 5,
            'is_active' => true,
        ]);

        $this->postAs($user, '/api/v1/app/remote-feed-cards/feed_card_dismiss_me/dismiss')
            ->assertOk()
            ->assertJsonPath('dismissed', true);

        $this->postAs($user, '/api/v1/app/remote-feed-cards/feed_card_dismiss_me/dismiss')
            ->assertOk()
            ->assertJsonPath('dismissed', true);

        $this->getAs($user, '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('feed_cards', []);

        $this->assertDatabaseCount('app_remote_feed_card_dismissals', 1);
    }

    public function test_admin_feed_card_rejects_invalid_action_url(): void
    {
        $admin = $this->user(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/app-remote-feed-cards', [
            'remote_id' => 'feed_card_bad_url',
            'title_de' => 'Bad',
            'body_de' => 'Bad',
            'action_url' => 'https://example.com/phishing',
        ])->assertSessionHasErrors('action_url');
    }

    public function test_remote_config_normalizes_unsafe_theme_tokens(): void
    {
        AppRemoteConfig::query()->create([
            'key' => 'default',
            'is_active' => true,
            'config_json' => [
                'theme' => [
                    'variant' => 'neon_blue',
                    'accent_token' => '#00aaff',
                ],
            ],
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.theme.variant', 'hnt_default')
            ->assertJsonPath('config.theme.accent_token', 'gold');
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
        return ApiAccessToken::createForUser($user, 'Remote config API test')['access_token'];
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
}
