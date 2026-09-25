<?php

namespace Tests\Feature;

use App\Models\AppRemoteConfig;
use App\Models\User;
use App\Services\AppConfig\AppRemoteConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebAppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_needs_no_auth_and_returns_only_five_null_slots(): void
    {
        $this->getJson('/api/v1/appearance')->assertOk()->assertExactJson([
            'backgrounds' => [
                'auth' => null,
                'landing' => null,
                'app' => null,
                'topbar' => null,
                'sidebar' => null,
            ],
        ]);
    }

    public function test_admin_uploads_multiple_image_formats_to_public_disk_and_changes_only_selected_slots(): void
    {
        Storage::fake('public');
        $admin = $this->user(['is_admin' => true]);

        $this->actingAs($admin)->post('/admin/appearance', [
            'backgrounds' => [
                'auth' => UploadedFile::fake()->image('auth.jpg'),
                'landing' => UploadedFile::fake()->image('landing.png'),
            ],
        ])->assertRedirect('/admin/appearance');

        $before = $this->getJson('/api/v1/appearance')->assertOk()->json('backgrounds');
        foreach (['auth', 'landing'] as $slot) {
            $this->assertStringStartsWith('/storage/app-backgrounds/web-'.$slot.'-background-', $before[$slot]);
            Storage::disk('public')->assertExists(substr($before[$slot], strlen('/storage/')));
        }
        $this->assertNull($before['app']);
        $this->assertNull($before['sidebar']);

        $this->actingAs($admin)->post('/admin/appearance', [
            'backgrounds' => ['topbar' => UploadedFile::fake()->image('topbar.webp')],
        ])->assertRedirect('/admin/appearance');

        $after = $this->getJson('/api/v1/appearance')->assertOk()->json('backgrounds');
        $this->assertSame($before['auth'], $after['auth']);
        $this->assertSame($before['landing'], $after['landing']);
        $this->assertNull($after['app']);
        $this->assertNull($after['sidebar']);
        $this->assertStringStartsWith('/storage/app-backgrounds/web-topbar-background-', $after['topbar']);
        Storage::disk('public')->assertExists(substr($after['topbar'], strlen('/storage/')));
    }

    public function test_reset_only_clears_its_slot_and_invalidates_cached_public_response(): void
    {
        Storage::fake('public');
        $admin = $this->user(['is_admin' => true]);
        $this->actingAs($admin)->post('/admin/appearance', [
            'backgrounds' => [
                'auth' => UploadedFile::fake()->image('auth.jpg'),
                'landing' => UploadedFile::fake()->image('landing.png'),
            ],
        ])->assertRedirect();

        $before = $this->getJson('/api/v1/appearance')->json('backgrounds');
        $this->actingAs($admin)->delete('/admin/appearance/auth')->assertRedirect('/admin/appearance');
        $after = $this->getJson('/api/v1/appearance')->assertOk()->json('backgrounds');

        $this->assertNull($after['auth']);
        $this->assertSame($before['landing'], $after['landing']);
        $this->assertSame(2, AppRemoteConfig::query()->where('key', 'web_appearance')->firstOrFail()->config_json['backgrounds']['auth']['version']);
    }

    public function test_non_admin_cannot_view_upload_or_reset(): void
    {
        $this->actingAs($this->user())->get('/admin/appearance')->assertForbidden();
        $this->post('/admin/appearance', ['backgrounds' => ['auth' => UploadedFile::fake()->image('auth.jpg')]])->assertForbidden();
        $this->delete('/admin/appearance/auth')->assertForbidden();
        $this->assertDatabaseCount('app_remote_configs', 0);
    }

    public function test_admin_page_previews_the_public_image_and_shows_fallback_status(): void
    {
        Storage::fake('public');
        $admin = $this->user(['is_admin' => true]);
        $this->actingAs($admin)->post('/admin/appearance', [
            'backgrounds' => ['landing' => UploadedFile::fake()->image('landing.png')],
        ])->assertRedirect();

        $url = $this->getJson('/api/v1/appearance')->json('backgrounds.landing');
        $this->actingAs($admin)->get('/admin/appearance')
            ->assertOk()
            ->assertSee($url)
            ->assertSee('Lokaler Fallback aktiv');
    }

    public function test_invalid_and_oversized_images_are_rejected(): void
    {
        Storage::fake('public');
        $this->actingAs($this->user(['is_admin' => true]));

        foreach (['script.svg', 'animation.gif', 'image.avif'] as $filename) {
            $this->post('/admin/appearance', [
                'backgrounds' => ['auth' => UploadedFile::fake()->create($filename, 1)],
            ])->assertSessionHasErrors('backgrounds.auth');
        }

        $this->post('/admin/appearance', [
            'backgrounds' => ['auth' => UploadedFile::fake()->create('forged.jpg', 1, 'text/plain')],
        ])->assertSessionHasErrors('backgrounds.auth');

        $this->post('/admin/appearance', [
            'backgrounds' => ['auth' => UploadedFile::fake()->image('large.jpg')->size(8193)],
        ])->assertSessionHasErrors('backgrounds.auth');

        $this->assertDatabaseCount('app_remote_configs', 0);
        $this->assertSame([], Storage::disk('public')->allFiles('app-backgrounds'));
    }

    public function test_public_response_does_not_expose_app_config_and_mobile_keys_remain_compatible(): void
    {
        $mobile = app(AppRemoteConfigService::class)->defaults();
        $mobile['appearance']['auth_background'] = ['url' => '/storage/app-backgrounds/auth-background.jpg', 'version' => 4];
        $mobile['appearance']['feed_background'] = ['url' => '/storage/app-backgrounds/feed-background.png', 'version' => 7];
        AppRemoteConfig::query()->create(['key' => 'default', 'is_active' => true, 'config_json' => $mobile]);

        Storage::fake('public');
        $this->actingAs($this->user(['is_admin' => true]))->post('/admin/appearance', [
            'backgrounds' => ['sidebar' => UploadedFile::fake()->image('sidebar.png')],
        ])->assertRedirect();

        $public = $this->getJson('/api/v1/appearance')->assertOk()->json();
        $this->assertSame(['backgrounds'], array_keys($public));
        $this->assertSame(['auth', 'landing', 'app', 'topbar', 'sidebar'], array_keys($public['backgrounds']));
        $this->assertArrayNotHasKey('android', $public);
        $this->assertArrayNotHasKey('features', $public);
        $this->assertArrayNotHasKey('version', $public['backgrounds']);
        $this->assertSame($mobile, AppRemoteConfig::query()->where('key', 'default')->firstOrFail()->config_json);
        $this->assertSame($mobile['appearance']['auth_background'], app(AppRemoteConfigService::class)->activeConfig()['appearance']['auth_background']);
        $this->assertSame($mobile['appearance']['feed_background'], app(AppRemoteConfigService::class)->activeConfig()['appearance']['feed_background']);
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
