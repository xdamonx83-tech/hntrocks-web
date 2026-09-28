<?php

namespace Tests\Feature;

use App\Models\MediaAsset;
use App\Models\Moment;
use App\Models\User;
use App\Services\MediaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class MomentVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://hnt.rocks',
            'hunthub.theme.enabled' => false,
            'hunthub.theme.preview_live' => false,
        ]);

        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_guest_can_view_public_moment_but_not_registered_or_private(): void
    {
        $owner = $this->user();

        $public = $this->moment($owner, 'public');
        $registered = $this->moment($owner, 'registered');
        $private = $this->moment($owner, 'private');

        $this->get(route('moments.show', $public))->assertOk();
        $this->get(route('moments.show', $registered))->assertNotFound();
        $this->get(route('moments.show', $private))->assertNotFound();
    }

    public function test_registered_user_can_view_registered_but_not_someone_elses_private_moment(): void
    {
        $owner = $this->user();
        $viewer = $this->user();

        $registered = $this->moment($owner, 'registered');
        $private = $this->moment($owner, 'private');

        $this->actingAs($viewer)->get(route('moments.show', $registered))->assertOk();
        $this->actingAs($viewer)->get(route('moments.show', $private))->assertNotFound();
    }

    public function test_owner_can_view_own_private_moment(): void
    {
        $owner = $this->user();
        $private = $this->moment($owner, 'private');

        $this->actingAs($owner)->get(route('moments.show', $private))->assertOk();
    }

    public function test_guest_cannot_view_future_public_moment(): void
    {
        $owner = $this->user();
        $future = $this->moment($owner, 'public', ['published_at' => now()->addHour()]);

        $this->get(route('moments.show', $future))->assertNotFound();
    }

    public function test_legacy_moment_query_redirects_to_the_requested_visible_moment(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $requested = $this->moment($owner, 'registered');

        $this->actingAs($viewer)
            ->get('/moments?moment='.$requested->id)
            ->assertRedirect(route('moments.show', $requested));
    }

    public function test_legacy_moment_query_does_not_bypass_private_visibility(): void
    {
        $owner = $this->user();
        $viewer = $this->user();
        $private = $this->moment($owner, 'private');

        $this->actingAs($viewer)
            ->get('/moments?moment='.$private->id)
            ->assertNotFound();
    }

    public function test_sitemap_contains_only_public_published_moments(): void
    {
        $owner = $this->user();

        $public = $this->moment($owner, 'public');
        $registered = $this->moment($owner, 'registered');
        $private = $this->moment($owner, 'private');
        $future = $this->moment($owner, 'public', ['published_at' => now()->addHour()]);

        $response = $this->get('/sitemap.xml')->assertOk();

        $response->assertSee(route('moments.show', $public), false);
        $response->assertDontSee(route('moments.show', $registered), false);
        $response->assertDontSee(route('moments.show', $private), false);
        $response->assertDontSee(route('moments.show', $future), false);
    }

    public function test_non_public_media_is_private_and_only_served_with_signed_url(): void
    {
        $owner = $this->user();
        $moment = $this->moment($owner, 'private');
        $moment->load('media');

        $this->assertSame('local', $moment->media->disk);
        Storage::disk('local')->assertExists($moment->media->path);
        Storage::disk('public')->assertMissing($moment->media->path);

        $signedUrl = $moment->media->url();
        $this->assertStringContainsString('/moments/r/'.$moment->id.'/media/'.$moment->media->id.'/original', $signedUrl);

        $this->get($signedUrl)->assertOk();

        $this->get(route('moments.media.show', [
            'moment' => $moment,
            'asset' => $moment->media,
            'variant' => 'original',
        ]))->assertForbidden();
    }

    public function test_media_service_moves_existing_private_moment_media_off_public_disk(): void
    {
        $owner = $this->user();
        $moment = $this->moment($owner, 'public');
        $asset = $moment->media()->firstOrFail();

        $moment->update(['visibility' => 'private']);
        app(MediaService::class)->syncMomentMediaVisibility($moment->fresh(), 'private');

        $asset->refresh();

        $this->assertSame('local', $asset->disk);
        $this->assertSame('private', $asset->visibility);
        Storage::disk('local')->assertExists($asset->path);
        Storage::disk('public')->assertMissing($asset->path);
    }

    private function moment(User $owner, string $visibility, array $attributes = []): Moment
    {
        $disk = $visibility === 'public' ? 'public' : 'local';
        $path = 'moments/tests/'.Str::uuid().'.mp4';
        Storage::disk($disk)->put($path, 'fake video bytes');

        $asset = MediaAsset::query()->create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $owner->id,
            'context' => 'moments',
            'disk' => $disk,
            'path' => $path,
            'type' => 'video',
            'mime_type' => 'video/mp4',
            'original_name' => 'moment.mp4',
            'extension' => 'mp4',
            'size_bytes' => 16,
            'visibility' => $visibility,
            'status' => 'ready',
        ]);

        $moment = Moment::query()->create(array_merge([
            'user_id' => $owner->id,
            'media_asset_id' => $asset->id,
            'caption' => 'Visibility test',
            'description' => 'Moment visibility test.',
            'visibility' => $visibility,
            'status' => 'published',
            'processing_status' => 'ready',
            'published_at' => now()->subMinute(),
        ], $attributes));

        $asset->update([
            'attachable_type' => $moment->getMorphClass(),
            'attachable_id' => $moment->id,
        ]);

        return $moment->fresh();
    }

    private function user(): User
    {
        $suffix = bin2hex(random_bytes(5));

        return User::query()->create([
            'name' => 'Moment test '.$suffix,
            'username' => 'moment_test_'.$suffix,
            'email' => $suffix.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }
}
