<?php

namespace Tests\Feature;

use App\Models\AppRemoteConfig;
use App\Models\User;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppRemoteHeroBackgroundsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_public_appearance_endpoint_exposes_page_hero_slots_with_fallbacks(): void
    {
        $config = app(AppRemoteConfigService::class)->defaults();
        $config['appearance']['feed_background'] = [
            'url' => '/storage/app-backgrounds/feed.webp',
            'version' => 2,
        ];
        $config['appearance']['hero_cups'] = [
            'url' => '/storage/app-backgrounds/hero-cups.webp',
            'version' => 3,
        ];
        $config['appearance']['hero_loadout_challenges'] = [
            'url' => '/storage/app-backgrounds/hero-loadouts.webp',
            'version' => 4,
        ];

        AppRemoteConfig::query()->create([
            'key' => AppRemoteConfigService::DEFAULT_KEY,
            'is_active' => true,
            'config_json' => $config,
            'published_at' => now(),
        ]);

        $this->getJson('/api/v1/appearance')
            ->assertOk()
            ->assertJsonPath('backgrounds.app', '/storage/app-backgrounds/feed.webp')
            ->assertJsonPath('backgrounds.hero_cups', '/storage/app-backgrounds/hero-cups.webp')
            ->assertJsonPath('backgrounds.hero_loadout_challenges', '/storage/app-backgrounds/hero-loadouts.webp')
            ->assertJsonPath('backgrounds.hero_guides', null);
    }

    public function test_admin_can_upload_and_reset_a_page_hero_background(): void
    {
        Storage::fake('public');

        $admin = $this->admin();
        $defaults = app(AppRemoteConfigService::class)->defaults();

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'remote_appearance_form' => '1',
            'config_json' => json_encode($defaults),
            'hero_cups_file' => UploadedFile::fake()->image('cups.jpg', 1600, 700),
        ])->assertRedirect('/admin/app-remote-config');

        $stored = AppRemoteConfig::query()->where('key', AppRemoteConfigService::DEFAULT_KEY)->firstOrFail();
        $hero = $stored->config_json['appearance']['hero_cups'];

        $this->assertSame(1, $hero['version']);
        $this->assertStringStartsWith('/storage/app-backgrounds/hero-cups-', $hero['url']);
        Storage::disk('public')->assertExists(str_replace('/storage/', '', $hero['url']));

        $this->getJson('/api/v1/appearance')
            ->assertOk()
            ->assertJsonPath('backgrounds.hero_cups', $hero['url']);

        $this->actingAs($admin)->post('/admin/app-remote-config', [
            'is_active' => '1',
            'remote_appearance_form' => '1',
            'config_json' => json_encode($stored->config_json),
            'hero_cups_clear' => '1',
        ])->assertRedirect('/admin/app-remote-config');

        $stored->refresh();

        $this->assertNull($stored->config_json['appearance']['hero_cups']['url']);
        $this->assertSame(2, $stored->config_json['appearance']['hero_cups']['version']);
    }

    private function admin(): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => 'Admin '.$suffix,
            'username' => 'admin_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
            'is_admin' => true,
        ]);
    }
}
