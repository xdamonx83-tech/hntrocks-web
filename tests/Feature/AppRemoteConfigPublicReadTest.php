<?php

namespace Tests\Feature;

use App\Models\AppRemoteConfig;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AppRemoteConfigPublicReadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_existing_remote_config_endpoint_is_readable_before_login(): void
    {
        AppRemoteConfig::query()->create([
            'key' => AppRemoteConfigService::DEFAULT_KEY,
            'is_active' => true,
            'config_json' => [
                'appearance' => [
                    'auth_background' => [
                        'url' => '/storage/app-backgrounds/auth-login.webp',
                        'version' => 12,
                    ],
                    'feed_background' => [
                        'url' => '/storage/app-backgrounds/feed-event.webp',
                        'version' => 8,
                    ],
                ],
            ],
        ]);

        $this->getJson('/api/v1/app/remote-config?locale=de&app_version_code=8')
            ->assertOk()
            ->assertJsonPath('message', 'Remote config loaded.')
            ->assertJsonPath('config.appearance.auth_background.url', '/storage/app-backgrounds/auth-login.webp')
            ->assertJsonPath('config.appearance.auth_background.version', 12)
            ->assertJsonPath('config.appearance.feed_background.url', '/storage/app-backgrounds/feed-event.webp')
            ->assertJsonPath('config.appearance.feed_background.version', 8)
            ->assertJsonPath('feed_cards', []);
    }

    public function test_remote_feed_card_dismiss_stays_protected_without_token(): void
    {
        $this->postJson('/api/v1/app/remote-feed-cards/test-card/dismiss')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }
}
