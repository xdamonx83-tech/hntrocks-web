<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\AppRemoteConfig;
use App\Models\AppRemoteFeedCard;
use App\Models\AppRemoteFeedCardDismissal;
use App\Models\User;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_admin_can_duplicate_feed_card_as_inactive_copy(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $card = AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_copy_source',
            'title_de' => 'Quelle',
            'title_en' => 'Source',
            'body_de' => 'Text DE',
            'body_en' => 'Text EN',
            'cta_label_de' => 'Öffnen',
            'cta_label_en' => 'Open',
            'action_url' => 'hntrocks://feed',
            'style_variant' => 'warning',
            'priority' => 12,
            'is_active' => true,
            'dismissible' => false,
            'audience_type' => 'user_ids',
            'audience_payload' => ['user_ids' => [1, 2, 3]],
        ]);

        $this->actingAs($admin)
            ->post('/admin/app-remote-feed-cards/'.$card->id.'/duplicate')
            ->assertRedirect();

        $copy = AppRemoteFeedCard::query()
            ->where('remote_id', 'like', 'feed_card_copy_source_copy_%')
            ->firstOrFail();

        $this->assertFalse($copy->is_active);
        $this->assertSame('Quelle', $copy->title_de);
        $this->assertSame('Source', $copy->title_en);
        $this->assertSame('Text DE', $copy->body_de);
        $this->assertSame('Text EN', $copy->body_en);
        $this->assertSame('Öffnen', $copy->cta_label_de);
        $this->assertSame('Open', $copy->cta_label_en);
        $this->assertSame('hntrocks://feed', $copy->action_url);
        $this->assertSame('warning', $copy->style_variant);
        $this->assertSame(12, $copy->priority);
        $this->assertFalse($copy->dismissible);
        $this->assertSame('user_ids', $copy->audience_type);
        $this->assertSame(['user_ids' => [1, 2, 3]], $copy->audience_payload);
        $this->assertSame($admin->id, $copy->created_by);
        $this->assertSame($admin->id, $copy->updated_by);
    }

    public function test_admin_new_version_increments_remote_id_and_avoids_collisions(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $source = AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_launch_v2',
            'title_de' => 'Launch',
            'body_de' => 'Text',
            'is_active' => true,
        ]);

        AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_launch_v3',
            'title_de' => 'Collision',
            'body_de' => 'Text',
        ]);

        $this->actingAs($admin)
            ->post('/admin/app-remote-feed-cards/'.$source->id.'/version')
            ->assertRedirect();

        $version = AppRemoteFeedCard::query()
            ->where('remote_id', 'like', 'feed_card_launch_v3_%')
            ->firstOrFail();

        $this->assertFalse($version->is_active);
        $this->assertSame('Launch', $version->title_de);
    }

    public function test_admin_reset_dismissals_only_deletes_current_remote_id(): void
    {
        $admin = $this->user(['is_admin' => true]);
        $user = $this->user();
        $target = AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_reset_me',
            'title_de' => 'Reset',
            'body_de' => 'Text',
        ]);
        $other = AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_keep_me',
            'title_de' => 'Keep',
            'body_de' => 'Text',
        ]);

        AppRemoteFeedCardDismissal::query()->create([
            'user_id' => $user->id,
            'remote_card_id' => $target->id,
            'remote_id' => $target->remote_id,
            'dismissed_at' => now(),
        ]);
        AppRemoteFeedCardDismissal::query()->create([
            'user_id' => $user->id,
            'remote_card_id' => $other->id,
            'remote_id' => $other->remote_id,
            'dismissed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->post('/admin/app-remote-feed-cards/'.$target->id.'/reset-dismissals')
            ->assertSessionHas('status', 'Dismissals für diese Card wurden zurückgesetzt.');

        $this->assertDatabaseMissing('app_remote_feed_card_dismissals', [
            'remote_id' => 'feed_card_reset_me',
        ]);
        $this->assertDatabaseHas('app_remote_feed_card_dismissals', [
            'remote_id' => 'feed_card_keep_me',
        ]);
    }

    public function test_non_admin_cannot_manage_remote_feed_card_admin_actions(): void
    {
        $user = $this->user();
        $card = AppRemoteFeedCard::query()->create([
            'remote_id' => 'feed_card_admin_only',
            'title_de' => 'Admin',
            'body_de' => 'Text',
        ]);

        $this->actingAs($user)->post('/admin/app-remote-feed-cards/'.$card->id.'/duplicate')->assertForbidden();
        $this->actingAs($user)->post('/admin/app-remote-feed-cards/'.$card->id.'/version')->assertForbidden();
        $this->actingAs($user)->post('/admin/app-remote-feed-cards/'.$card->id.'/reset-dismissals')->assertForbidden();
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

    public function test_remote_config_normalizes_palette_values_and_discards_unknown_keys(): void
    {
        AppRemoteConfig::query()->create([
            'key' => 'default',
            'is_active' => true,
            'config_json' => [
                'theme' => [
                    'palette_enabled' => 1,
                    'palette' => [
                        'primary' => '#ffcc66',
                        'surface' => '#191917',
                        'danger' => 'red',
                        'success' => '#FFF',
                        'unknown' => '#000000',
                    ],
                ],
            ],
        ]);

        $response = $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.theme.palette_enabled', true)
            ->assertJsonPath('config.theme.palette.primary', '#FFCC66')
            ->assertJsonPath('config.theme.palette.surface', '#191917')
            ->assertJsonPath('config.theme.palette.danger', '#B8463A')
            ->assertJsonPath('config.theme.palette.success', '#8FAF72');

        $this->assertArrayNotHasKey('unknown', $response->json('config.theme.palette'));
    }

    public function test_remote_config_normalizes_branding_logo_urls(): void
    {
        config(['app.url' => 'https://hnt.rocks']);

        AppRemoteConfig::query()->create([
            'key' => 'default',
            'is_active' => true,
            'config_json' => [
                'branding' => [
                    'logo_enabled' => '1',
                    'logo_url' => '/storage/app-branding/logo.svg',
                    'logo_dark_url' => 'https://example.com/storage/app-branding/logo-dark.svg',
                ],
            ],
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.branding.logo_enabled', true)
            ->assertJsonPath('config.branding.logo_url', '/storage/app-branding/logo.svg')
            ->assertJsonPath('config.branding.logo_dark_url', null);
    }

    public function test_remote_config_accepts_app_host_branding_logo_url(): void
    {
        config(['app.url' => 'https://preview.hnt.rocks']);

        AppRemoteConfig::query()->create([
            'key' => 'default',
            'is_active' => true,
            'config_json' => [
                'branding' => [
                    'logo_url' => 'https://preview.hnt.rocks/storage/app-branding/logo.webp',
                ],
            ],
        ]);

        $this->getAs($this->user(), '/api/v1/app/remote-config')
            ->assertOk()
            ->assertJsonPath('config.branding.logo_url', 'https://preview.hnt.rocks/storage/app-branding/logo.webp');
    }

    public function test_admin_remote_config_update_can_store_branding_palette_fields_and_logo(): void
    {
        Storage::fake('public');

        $admin = $this->user(['is_admin' => true]);
        $defaults = app(AppRemoteConfigService::class)->defaults();

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'remote_branding_form' => '1',
            'config_json' => json_encode($defaults),
            'palette_enabled' => '1',
            'theme_palette' => [
                'primary' => '#ffcc66',
                'surface' => '#191917',
            ],
            'logo_enabled' => '1',
            'logo_file' => UploadedFile::fake()->create('logo.png', 8, 'image/png'),
        ])->assertRedirect('/admin/app-remote-config');

        $config = AppRemoteConfig::query()->where('key', 'default')->firstOrFail();

        $this->assertTrue($config->is_active);
        $this->assertTrue($config->config_json['theme']['palette_enabled']);
        $this->assertSame('#FFCC66', $config->config_json['theme']['palette']['primary']);
        $this->assertSame('#191917', $config->config_json['theme']['palette']['surface']);
        $this->assertTrue($config->config_json['branding']['logo_enabled']);
        $this->assertStringStartsWith('/storage/app-branding/logo-main-', $config->config_json['branding']['logo_url']);
        $this->assertNotNull($config->config_json['branding']['logo_updated_at']);

        Storage::disk('public')->assertExists(str_replace('/storage/', '', $config->config_json['branding']['logo_url']));
    }

    public function test_non_admin_cannot_store_remote_branding_fields_or_upload_logo(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/admin/app-remote-config', [
            'remote_branding_form' => '1',
            'config_json' => '{}',
            'palette_enabled' => '1',
            'theme_palette' => ['primary' => '#FFCC66'],
            'logo_file' => UploadedFile::fake()->create('logo.svg', 4, 'image/svg+xml'),
        ])->assertForbidden();

        $this->assertDatabaseCount('app_remote_configs', 0);
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
