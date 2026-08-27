<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\AppRemoteConfig;
use App\Models\User;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppRemoteConfigAppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_remote_config_without_backgrounds_returns_optional_fallback_contract(): void
    {
        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.appearance.auth_background.url', null)
            ->assertJsonPath('config.appearance.auth_background.version', 0)
            ->assertJsonPath('config.appearance.feed_background.url', null)
            ->assertJsonPath('config.appearance.feed_background.version', 0)
            ->assertJsonPath('config.features.feed_remote_cards_enabled', true)
            ->assertJsonPath('config.android.min_version_code', 8);
    }

    public function test_remote_config_returns_auth_background_and_version(): void
    {
        $this->storeConfig([
            'appearance' => [
                'auth_background' => [
                    'url' => '/storage/app-backgrounds/auth-event.webp',
                    'version' => 4,
                ],
            ],
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.appearance.auth_background.url', '/storage/app-backgrounds/auth-event.webp')
            ->assertJsonPath('config.appearance.auth_background.version', 4)
            ->assertJsonPath('config.appearance.feed_background.url', null)
            ->assertJsonPath('config.appearance.feed_background.version', 0);
    }

    public function test_remote_config_returns_feed_background_and_version(): void
    {
        $this->storeConfig([
            'appearance' => [
                'feed_background' => [
                    'url' => '/storage/app-backgrounds/feed-event.png',
                    'version' => 7,
                ],
            ],
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.appearance.auth_background.url', null)
            ->assertJsonPath('config.appearance.feed_background.url', '/storage/app-backgrounds/feed-event.png')
            ->assertJsonPath('config.appearance.feed_background.version', 7);
    }

    public function test_remote_config_returns_both_backgrounds_without_removing_existing_fields(): void
    {
        $this->storeConfig([
            'features' => [
                'messages_enabled' => false,
            ],
            'maintenance' => [
                'enabled' => true,
                'message_de' => 'Kurze Wartung',
            ],
            'appearance' => [
                'auth_background' => [
                    'url' => '/storage/app-backgrounds/auth.webp',
                    'version' => 2,
                ],
                'feed_background' => [
                    'url' => '/storage/app-backgrounds/feed.webp',
                    'version' => 3,
                ],
            ],
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.appearance.auth_background.url', '/storage/app-backgrounds/auth.webp')
            ->assertJsonPath('config.appearance.auth_background.version', 2)
            ->assertJsonPath('config.appearance.feed_background.url', '/storage/app-backgrounds/feed.webp')
            ->assertJsonPath('config.appearance.feed_background.version', 3)
            ->assertJsonPath('config.features.messages_enabled', false)
            ->assertJsonPath('config.features.feed_remote_cards_enabled', true)
            ->assertJsonPath('config.maintenance.enabled', true)
            ->assertJsonPath('config.theme.variant', 'hnt_default')
            ->assertJsonPath('config.branding.logo_enabled', false);
    }

    public function test_admin_uploads_both_backgrounds_and_versions_increment(): void
    {
        Storage::fake('public');

        $admin = $this->user(['is_admin' => true]);
        $defaults = app(AppRemoteConfigService::class)->defaults();

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'config_json' => json_encode($defaults),
            'auth_background_file' => UploadedFile::fake()->image('auth.jpg', 1600, 900),
            'feed_background_file' => UploadedFile::fake()->image('feed.png', 1600, 900),
        ])->assertRedirect('/admin/app-remote-config');

        $config = AppRemoteConfig::query()->where('key', 'default')->firstOrFail();
        $auth = $config->config_json['appearance']['auth_background'];
        $feed = $config->config_json['appearance']['feed_background'];

        $this->assertSame(1, $auth['version']);
        $this->assertSame(1, $feed['version']);
        $this->assertStringStartsWith('/storage/app-backgrounds/auth-background-', $auth['url']);
        $this->assertStringStartsWith('/storage/app-backgrounds/feed-background-', $feed['url']);

        Storage::disk('public')->assertExists(str_replace('/storage/', '', $auth['url']));
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $feed['url']));
    }

    public function test_changing_or_clearing_background_increments_its_revision_only(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $service = app(AppRemoteConfigService::class);
        $config = $service->defaults();
        $config['appearance']['auth_background'] = [
            'url' => '/storage/app-backgrounds/auth-old.webp',
            'version' => 5,
        ];
        $config['appearance']['feed_background'] = [
            'url' => '/storage/app-backgrounds/feed-stable.webp',
            'version' => 9,
        ];
        $this->storeConfig($config);

        $config['appearance']['auth_background']['url'] = '/storage/app-backgrounds/auth-new.webp';

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'config_json' => json_encode($config),
        ])->assertRedirect('/admin/app-remote-config');

        $stored = AppRemoteConfig::query()->where('key', 'default')->firstOrFail();
        $this->assertSame(6, $stored->config_json['appearance']['auth_background']['version']);
        $this->assertSame(9, $stored->config_json['appearance']['feed_background']['version']);

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'config_json' => json_encode($stored->config_json),
            'auth_background_clear' => '1',
        ])->assertRedirect('/admin/app-remote-config');

        $stored->refresh();
        $this->assertNull($stored->config_json['appearance']['auth_background']['url']);
        $this->assertSame(7, $stored->config_json['appearance']['auth_background']['version']);
        $this->assertSame(9, $stored->config_json['appearance']['feed_background']['version']);
    }

    public function test_admin_background_upload_rejects_invalid_file_type(): void
    {
        Storage::fake('public');

        $admin = $this->user(['is_admin' => true]);
        $defaults = app(AppRemoteConfigService::class)->defaults();

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'config_json' => json_encode($defaults),
            'auth_background_file' => UploadedFile::fake()->create('payload.txt', 12, 'text/plain'),
        ])->assertSessionHasErrors('auth_background_file');

        $this->assertDatabaseCount('app_remote_configs', 0);
    }

    public function test_admin_update_invalidates_cached_active_config_immediately(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $service = app(AppRemoteConfigService::class);
        $defaults = $service->defaults();
        $this->storeConfig($defaults);

        $this->getAs($admin, '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.appearance.auth_background.url', null);

        $defaults['appearance']['auth_background']['url'] = '/storage/app-backgrounds/auth-cache-refresh.webp';

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'config_json' => json_encode($defaults),
        ])->assertRedirect('/admin/app-remote-config');

        $this->getAs($admin, '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.appearance.auth_background.url', '/storage/app-backgrounds/auth-cache-refresh.webp')
            ->assertJsonPath('config.appearance.auth_background.version', 1);
    }

    public function test_remote_config_rejects_external_or_insecure_background_urls(): void
    {
        $this->storeConfig([
            'appearance' => [
                'auth_background' => [
                    'url' => 'https://example.com/storage/app-backgrounds/auth.webp',
                    'version' => 3,
                ],
                'feed_background' => [
                    'url' => 'http://hnt.rocks/storage/app-backgrounds/feed.webp',
                    'version' => 4,
                ],
            ],
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.appearance.auth_background.url', null)
            ->assertJsonPath('config.appearance.auth_background.version', 3)
            ->assertJsonPath('config.appearance.feed_background.url', null)
            ->assertJsonPath('config.appearance.feed_background.version', 4);
    }

    private function storeConfig(array $config): AppRemoteConfig
    {
        return AppRemoteConfig::query()->create([
            'key' => AppRemoteConfigService::DEFAULT_KEY,
            'is_active' => true,
            'config_json' => $config,
            'published_at' => now(),
        ]);
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Remote config appearance test')['access_token'];
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
