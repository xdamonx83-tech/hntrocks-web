<?php

namespace Tests\Feature\Maps;

use App\Models\ApiAccessToken;
use App\Models\HntMap;
use App\Models\HntMapCashSpotSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ApiMapCashSpotSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_upload_a_valid_cash_spot_image_as_pending_submission(): void
    {
        Storage::fake('local');
        $map = $this->map();

        $response = $this->postUpload($map->slug, [
            'x' => 120.5,
            'y' => 240.25,
            'image' => UploadedFile::fake()->image('cash-spot.jpg'),
            'submitter_name' => ' Guest ',
            'submitter_email' => 'guest@example.test',
        ])->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('submission.map_slug', $map->slug)
            ->assertJsonPath('submission.x', 120.5)
            ->assertJsonPath('submission.y', 240.25)
            ->assertJsonPath('submission.status', 'pending');

        $submission = HntMapCashSpotSubmission::findOrFail($response->json('submission.id'));

        $this->assertNull($submission->user_id);
        $this->assertSame(HntMapCashSpotSubmission::STATUS_PENDING, $submission->status);
        $this->assertSame('Guest', $submission->submitter_name);
        Storage::disk('local')->assertExists($submission->path);
    }

    public function test_authenticated_user_upload_sets_user_id(): void
    {
        Storage::fake('local');
        $map = $this->map();
        $user = User::factory()->create();

        $this->withToken($this->token($user))
            ->post($this->endpoint($map->slug), $this->validPayload(), ['Accept' => 'application/json'])
            ->assertCreated();

        $this->assertDatabaseHas('hnt_map_cash_spot_submissions', [
            'hnt_map_id' => $map->id,
            'user_id' => $user->id,
            'status' => HntMapCashSpotSubmission::STATUS_PENDING,
        ]);
    }

    public function test_invalid_bearer_token_returns_unauthorized(): void
    {
        Storage::fake('local');

        $this->withToken('999|invalid')
            ->post($this->endpoint($this->map()->slug), $this->validPayload(), ['Accept' => 'application/json'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_unknown_map_slug_returns_not_found(): void
    {
        Storage::fake('local');

        $this->postUpload('unknown-map', $this->validPayload())->assertNotFound();
    }

    public function test_inactive_map_returns_not_found(): void
    {
        Storage::fake('local');
        $map = $this->map(['is_active' => false]);

        $this->postUpload($map->slug, $this->validPayload())->assertNotFound();
    }

    public function test_coordinates_must_be_inside_the_map(): void
    {
        Storage::fake('local');
        $map = $this->map(['width' => 1000, 'height' => 800]);

        foreach ([['x' => -1, 'y' => 100], ['x' => 1001, 'y' => 100], ['x' => 100, 'y' => -1], ['x' => 100, 'y' => 801]] as $coordinates) {
            $this->postUpload($map->slug, [
                ...$coordinates,
                'image' => UploadedFile::fake()->image('cash-spot.jpg'),
            ])->assertUnprocessable();
        }

        $this->assertDatabaseCount('hnt_map_cash_spot_submissions', 0);
    }

    public function test_image_is_required(): void
    {
        $this->post($this->endpoint($this->map()->slug), ['x' => 100, 'y' => 200])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors('image');
    }

    public function test_non_image_file_is_rejected(): void
    {
        Storage::fake('local');

        $this->postUpload($this->map()->slug, [
            'x' => 100,
            'y' => 200,
            'image' => UploadedFile::fake()->create('payload.php', 10, 'application/x-php'),
        ])->assertUnprocessable()->assertJsonValidationErrors('image');
    }

    public function test_existing_web_upload_route_remains_registered(): void
    {
        $this->assertTrue(Route::has('maps.cash-spots.store'));
        $this->assertTrue(Route::has('api.v1.maps.cash-spots.store'));
    }

    private function postUpload(string $slug, array $payload)
    {
        return $this->post($this->endpoint($slug), $payload, ['Accept' => 'application/json']);
    }

    private function endpoint(string $slug): string
    {
        return route('api.v1.maps.cash-spots.store', ['slug' => $slug]);
    }

    private function validPayload(): array
    {
        return [
            'x' => 100,
            'y' => 200,
            'image' => UploadedFile::fake()->image('cash-spot.png'),
        ];
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Maps upload API test')['access_token'];
    }

    private function map(array $overrides = []): HntMap
    {
        return HntMap::query()->create(array_merge([
            'slug' => 'upload-map-'.fake()->unique()->word(),
            'name' => 'Upload Test Map',
            'width' => 2048,
            'height' => 2048,
            'is_active' => true,
        ], $overrides));
    }
}
