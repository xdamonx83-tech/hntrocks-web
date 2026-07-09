<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\LiveLobby;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserProfile;
use App\Services\LiveLobbyNotificationService;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LiveLobbyApiTest extends TestCase
{
    use RefreshDatabase;

    private object $notifications;

    protected function setUp(): void
    {
        parent::setUp();

        $this->notifications = new class extends LiveLobbyNotificationService {
            public int $announcements = 0;
            public function __construct() {}
            public function announce(LiveLobby $lobby): void { $this->announcements++; }
            public function joined(LiveLobby $lobby, User $member): void {}
            public function full(LiveLobby $lobby, User $actor): void {}
        };
        $this->app->instance(LiveLobbyNotificationService::class, $this->notifications);
    }

    public function test_authentication_is_required(): void
    {
        $this->getJson('/api/v1/live-lobbies')->assertUnauthorized();
    }

    public function test_user_can_create_duo_lobby_and_creator_becomes_member(): void
    {
        $user = $this->user();

        $response = $this->postAs($user, '/api/v1/live-lobbies', [
            'mode' => 'duo',
            'platform' => 'pc',
            'voice_required' => true,
            'lobby_code' => 'HUNT-123',
            'mmr_stars' => 5,
        ])->assertCreated()
            ->assertJsonPath('data.mode', 'duo')
            ->assertJsonPath('data.slots_total', 2)
            ->assertJsonPath('data.slots_filled', 1)
            ->assertJsonPath('data.crossplay_pool', 'pc')
            ->assertJsonPath('data.members.0.mmr_stars', 5)
            ->assertJsonPath('data.viewer.is_creator', true)
            ->assertJsonPath('data.contact.lobby_code', 'HUNT-123');

        $lobby = LiveLobby::where('public_id', $response->json('data.id'))->firstOrFail();
        $this->assertDatabaseHas('live_lobby_members', [
            'live_lobby_id' => $lobby->id,
            'user_id' => $user->id,
            'role' => 'creator',
            'mmr_stars' => 5,
        ]);
        $this->assertHunterNumber((int) $response->json('data.members.0.hunter_number'));
        $this->assertTrue($lobby->expires_at->between(now()->addMinutes(14), now()->addMinutes(16)));
        $this->assertSame(1, $this->notifications->announcements);
    }

    public function test_trio_and_console_crossplay_pools_are_derived(): void
    {
        $playstation = $this->createLobby($this->user(), ['mode' => 'trio', 'platform' => 'playstation']);
        $this->assertSame(3, $playstation->slots_total);
        $this->assertSame('console', $playstation->crossplay_pool);

        $xbox = $this->createLobby($this->user(), ['platform' => 'xbox']);
        $this->assertSame('console', $xbox->crossplay_pool);
    }

    public function test_lobby_can_be_created_with_mood_and_resource_contains_it(): void
    {
        $response = $this->postAs($this->user(), '/api/v1/live-lobbies', [
            'mode' => 'duo',
            'platform' => 'pc',
            'mood' => 'bossrush',
        ])->assertCreated()
            ->assertJsonPath('data.mood', 'bossrush');

        $this->assertDatabaseHas('live_lobbies', [
            'public_id' => $response->json('data.id'),
            'mood' => 'bossrush',
        ]);
    }

    public function test_invalid_mood_is_rejected(): void
    {
        $this->postAs($this->user(), '/api/v1/live-lobbies', [
            'mode' => 'duo',
            'platform' => 'pc',
            'mood' => 'speedrun',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('mood');
    }

    public function test_invalid_mmr_stars_are_rejected(): void
    {
        foreach ([0, 7, 'many'] as $value) {
            $this->postAs($this->user(), '/api/v1/live-lobbies', [
                'mode' => 'duo',
                'platform' => 'pc',
                'mmr_stars' => $value,
            ])->assertUnprocessable()
                ->assertJsonValidationErrors('mmr_stars');
        }
    }

    public function test_mood_is_optional_for_existing_requests(): void
    {
        $this->postAs($this->user(), '/api/v1/live-lobbies', [
            'mode' => 'duo',
            'platform' => 'pc',
        ])->assertCreated()
            ->assertJsonPath('data.mood', null);
    }

    public function test_pc_lobby_rejects_console_and_accepts_pc(): void
    {
        $lobby = $this->createLobby($this->user(), ['platform' => 'pc']);

        $this->postAs($this->user(), $this->action($lobby, 'join'), ['platform' => 'xbox'])
            ->assertUnprocessable();

        $this->postAs($this->user(), $this->action($lobby, 'join'), ['platform' => 'pc'])
            ->assertOk()
            ->assertJsonPath('data.slots_filled', 2)
            ->assertJsonPath('data.status', 'full');

        $lobby->refresh();
        $this->assertTrue($lobby->expires_at->between(now()->addMinutes(4), now()->addMinutes(6)));
    }

    public function test_playstation_and_xbox_can_join_each_other(): void
    {
        $playstation = $this->createLobby($this->user(), ['platform' => 'playstation']);
        $this->postAs($this->user(), $this->action($playstation, 'join'), ['platform' => 'xbox'])->assertOk();

        $xbox = $this->createLobby($this->user(), ['platform' => 'xbox']);
        $this->postAs($this->user(), $this->action($xbox, 'join'), ['platform' => 'playstation'])->assertOk();
    }

    public function test_join_can_store_member_mmr_stars(): void
    {
        $lobby = $this->createLobby($this->user(), ['platform' => 'pc']);
        $member = $this->user();

        $this->postAs($member, $this->action($lobby, 'join'), [
            'platform' => 'pc',
            'mmr_stars' => 3,
        ])->assertOk()
            ->assertJsonPath('data.members.1.mmr_stars', 3);

        $this->assertDatabaseHas('live_lobby_members', [
            'live_lobby_id' => $lobby->id,
            'user_id' => $member->id,
            'role' => 'member',
            'mmr_stars' => 3,
        ]);
    }

    public function test_hunter_numbers_are_unique_and_stable_within_a_lobby(): void
    {
        $creator = $this->user();
        $lobby = $this->createLobby($creator, ['mode' => 'trio', 'platform' => 'playstation']);
        $creatorNumber = (int) $lobby->activeMembers()->where('user_id', $creator->id)->value('hunter_number');
        $firstMember = $this->user();
        $secondMember = $this->user();

        $this->postAs($firstMember, $this->action($lobby, 'join'), ['platform' => 'xbox'])->assertOk();
        $firstNumber = (int) $lobby->activeMembers()->where('user_id', $firstMember->id)->value('hunter_number');

        $this->postAs($secondMember, $this->action($lobby, 'join'), ['platform' => 'playstation'])
            ->assertOk()
            ->assertJsonCount(3, 'data.members');
        $secondNumber = (int) $lobby->activeMembers()->where('user_id', $secondMember->id)->value('hunter_number');

        $this->assertHunterNumber($creatorNumber);
        $this->assertHunterNumber($firstNumber);
        $this->assertHunterNumber($secondNumber);
        $this->assertCount(3, array_unique([$creatorNumber, $firstNumber, $secondNumber]));
        $this->assertSame($creatorNumber, (int) $lobby->activeMembers()->where('user_id', $creator->id)->value('hunter_number'));
    }

    public function test_contact_fields_are_hidden_until_viewer_joins(): void
    {
        $lobby = $this->createLobby($this->user(), ['mode' => 'trio', 'lobby_code' => 'SECRET']);
        $viewer = $this->user();

        $this->getAs($viewer, '/api/v1/live-lobbies/'.$lobby->public_id)
            ->assertOk()->assertJsonMissingPath('data.contact');

        $this->postAs($viewer, $this->action($lobby, 'join'), ['platform' => 'pc'])
            ->assertOk()->assertJsonPath('data.contact.lobby_code', 'SECRET');
    }

    public function test_links_and_discord_invites_are_rejected(): void
    {
        foreach (['https://example.com', 'www.example.com', 'discord.gg/hunt', 'discord.com/invite/hunt', 'custom://invite'] as $unsafe) {
            $this->postAs($this->user(), '/api/v1/live-lobbies', [
                'mode' => 'duo',
                'platform' => 'pc',
                'note' => $unsafe,
            ])->assertUnprocessable()->assertJsonValidationErrors('note');
        }
    }

    public function test_creator_leave_closes_lobby_and_only_creator_can_close(): void
    {
        $creator = $this->user();
        $lobby = $this->createLobby($creator);

        $this->postAs($this->user(), $this->action($lobby, 'close'))->assertForbidden();
        $this->postAs($creator, $this->action($lobby, 'leave'))->assertOk()->assertJsonPath('data.status', 'closed');
    }

    public function test_user_may_only_have_one_active_membership(): void
    {
        $user = $this->user();
        $this->createLobby($user);

        $this->postAs($user, '/api/v1/live-lobbies', ['mode' => 'duo', 'platform' => 'pc'])
            ->assertUnprocessable();
    }

    public function test_member_leave_reopens_full_lobby_and_recounts_slots(): void
    {
        $lobby = $this->createLobby($this->user());
        $member = $this->user();
        $this->postAs($member, $this->action($lobby, 'join'), ['platform' => 'pc'])->assertOk();

        $this->postAs($member, $this->action($lobby, 'leave'))
            ->assertOk()
            ->assertJsonPath('data.status', 'open')
            ->assertJsonPath('data.slots_filled', 1);
    }

    public function test_closed_and_expired_lobbies_are_not_listed(): void
    {
        $viewer = $this->user();
        $closed = $this->createLobby($this->user());
        $closed->update(['status' => 'closed', 'closed_at' => now()]);
        $expired = $this->createLobby($this->user());
        $expired->update(['expires_at' => now()->subSecond()]);

        $this->getAs($viewer, '/api/v1/live-lobbies')
            ->assertOk()
            ->assertJsonMissing(['public_id' => $closed->public_id])
            ->assertJsonMissing(['public_id' => $expired->public_id]);

        $this->assertDatabaseHas('live_lobbies', ['id' => $expired->id, 'status' => 'expired']);
    }

    public function test_mentor_filter_is_opt_in_and_false_keeps_the_unfiltered_result(): void
    {
        $mentor = $this->user();
        $this->profile($mentor, ['hunter_dna' => ['mentor' => true]]);
        $mentorLobby = $this->createLobby($mentor);

        $nonMentor = $this->user();
        $this->profile($nonMentor, ['hunter_dna' => ['mentor' => false]]);
        $nonMentorLobby = $this->createLobby($nonMentor);
        $viewer = $this->user();

        foreach (['/api/v1/live-lobbies', '/api/v1/live-lobbies?mentor_hunter=0'] as $uri) {
            $this->getAs($viewer, $uri)
                ->assertOk()
                ->assertJsonFragment(['public_id' => $mentorLobby->public_id])
                ->assertJsonFragment(['public_id' => $nonMentorLobby->public_id]);
        }

        $this->getAs($viewer, '/api/v1/live-lobbies?mentor_hunter=1')
            ->assertOk()
            ->assertJsonFragment(['public_id' => $mentorLobby->public_id])
            ->assertJsonMissing(['public_id' => $nonMentorLobby->public_id]);
    }

    public function test_mentor_filter_respects_private_creator_traits_for_each_viewer(): void
    {
        $publicMentor = $this->user();
        $this->profile($publicMentor, ['hunter_dna' => ['mentor' => true]]);
        $publicLobby = $this->createLobby($publicMentor);

        $privateMentor = $this->user();
        $this->profile($privateMentor, [
            'profile_visibility' => 'private',
            'hunter_dna' => ['mentor' => true],
        ]);
        $privateLobby = $this->createLobby($privateMentor);

        $this->getAs($this->user(), '/api/v1/live-lobbies?mentor_hunter=1')
            ->assertOk()
            ->assertJsonFragment(['public_id' => $publicLobby->public_id])
            ->assertJsonMissing(['public_id' => $privateLobby->public_id]);

        $this->getAs($privateMentor, '/api/v1/live-lobbies?mentor_hunter=1')
            ->assertOk()
            ->assertJsonFragment(['public_id' => $publicLobby->public_id])
            ->assertJsonFragment(['public_id' => $privateLobby->public_id])
            ->assertJsonPath('data.0.creator.mentor_hunter', true)
            ->assertJsonMissingPath('data.0.creator.hunter_dna')
            ->assertJsonMissingPath('data.0.creator.profile');
    }

    public function test_common_ground_matches_profile_lobby_and_creator_traits_without_leaking_hunter_dna(): void
    {
        $creator = $this->user();
        $this->profile($creator, [
            'hunt_role' => 'support',
            'hunter_dna' => [
                'experience' => 'experienced',
                'temper' => 'focused',
                'goals' => ['boss'],
                'mentor' => false,
            ],
        ]);
        $lobby = $this->createLobby($creator, [
            'platform' => 'pc',
            'region' => 'EU',
            'language' => 'de',
            'playstyle' => 'tactical',
        ]);

        $viewer = $this->user();
        $this->profile($viewer, [
            'platform' => 'pc',
            'region' => 'EU',
            'language' => 'de',
            'playstyle' => 'tactical',
            'hunt_role' => 'support',
            'hunter_dna' => [
                'voice' => 'optional',
                'preferred_mode' => 'flexible',
                'experience' => 'experienced',
                'temper' => 'focused',
                'goals' => ['boss'],
                'mentor' => false,
            ],
        ]);

        $response = $this->getAs($viewer, '/api/v1/live-lobbies/'.$lobby->public_id)
            ->assertOk()
            ->assertJsonPath('data.id', $lobby->public_id)
            ->assertJsonPath('data.creator.id', $creator->id)
            ->assertJsonPath('data.creator.mentor_hunter', false)
            ->assertJsonPath('data.common_ground.self', false)
            ->assertJsonPath('data.common_ground.score', 10)
            ->assertJsonPath('data.common_ground.max_score', 11)
            ->assertJsonMissingPath('data.creator.hunter_dna')
            ->assertJsonMissingPath('data.creator.profile')
            ->assertJsonMissingPath('data.common_ground.hunter_dna')
            ->assertJsonStructure(['data' => [
                'id', 'public_id', 'status', 'mode', 'slots_total', 'slots_filled',
                'missing_slots', 'platform', 'crossplay_pool', 'region', 'language',
                'voice_required', 'playstyle', 'mood', 'note', 'creator', 'members', 'viewer',
                'expires_at', 'full_at', 'closed_at', 'created_at', 'common_ground',
            ]]);

        $this->assertSame(
            ['platform', 'region', 'language', 'preferred_mode', 'playstyle', 'hunt_role', 'voice', 'temper', 'experience', 'goals'],
            array_column($response->json('data.common_ground.items'), 'key'),
        );
    }

    public function test_public_creator_mentor_flag_is_exposed_without_hunter_dna(): void
    {
        $creator = $this->user();
        $this->profile($creator, [
            'hunter_dna' => ['mentor' => true],
        ]);
        $lobby = $this->createLobby($creator);
        $viewer = $this->user();

        $this->getAs($viewer, '/api/v1/live-lobbies')
            ->assertOk()
            ->assertJsonPath('data.0.creator.mentor_hunter', true);

        $this->getAs($viewer, '/api/v1/live-lobbies/'.$lobby->public_id)
            ->assertOk()
            ->assertJsonPath('data.creator.mentor_hunter', true)
            ->assertJsonMissingPath('data.creator.hunter_dna')
            ->assertJsonMissingPath('data.creator.profile');
    }

    public function test_live_lobby_announcement_matches_push_lfg_platform_region_and_language(): void
    {
        Cache::flush();

        $creator = $this->user();
        $this->profile($creator, ['platform' => 'pc', 'region' => 'EU', 'language' => 'de']);
        $lobby = $this->createLobby($creator, [
            'mode' => 'trio',
            'platform' => 'playstation',
            'region' => 'EU',
            'language' => 'de',
        ]);

        $matchingPlayStation = $this->userWithPushProfile(['platform' => 'PlayStation', 'region' => 'EU', 'language' => 'de']);
        $matchingXbox = $this->userWithPushProfile(['platform' => 'Xbox', 'region' => 'EU', 'language' => 'de']);
        $matchingCrossplay = $this->userWithPushProfile(['platform' => 'Crossplay', 'region' => 'EU', 'language' => 'de']);
        $matchingPs5 = $this->userWithPushProfile(['platform' => 'PS5', 'region' => 'EU', 'language' => 'de']);
        $this->userWithPushProfile(['platform' => 'pc', 'region' => 'EU', 'language' => 'de']);
        $this->userWithPushProfile(['platform' => 'xbox', 'region' => 'US', 'language' => 'de']);
        $this->userWithPushProfile(['platform' => 'xbox', 'region' => 'EU', 'language' => 'en']);
        $disabled = $this->userWithPushProfile(['platform' => 'xbox', 'region' => 'EU', 'language' => 'de']);
        $disabled->notificationSettings()->create(['lfg' => false]);
        $withoutPush = $this->user();
        $this->profile($withoutPush, ['platform' => 'xbox', 'region' => 'EU', 'language' => 'de']);

        $fakeNotifications = new class extends NotificationService {
            public array $sent = [];

            public function send(?User $recipient, ?User $actor, string $type, string $title, string $body, ?string $actionUrl = null): ?UserNotification
            {
                $this->sent[] = compact('recipient', 'actor', 'type', 'title', 'body', 'actionUrl');

                return null;
            }
        };

        (new LiveLobbyNotificationService($fakeNotifications))->announce($lobby->fresh(['creator', 'activeMembers.user.profile']));

        $recipientIds = collect($fakeNotifications->sent)->pluck('recipient.id')->all();
        $this->assertEqualsCanonicalizing([
            $matchingPlayStation->id,
            $matchingXbox->id,
            $matchingCrossplay->id,
            $matchingPs5->id,
            $withoutPush->id,
        ], $recipientIds);
        $this->assertSame('lfg_live_lobby_ready', $fakeNotifications->sent[0]['type']);
        $this->assertSame('Ein Hunter ist ready', $fakeNotifications->sent[0]['title']);
        $this->assertSame('Trio sucht noch 2 Hunter.', $fakeNotifications->sent[0]['body']);
        $this->assertSame('/ready-lobbies/'.$lobby->public_id, $fakeNotifications->sent[0]['actionUrl']);
    }

    public function test_bootstrap_counts_open_matching_ready_lobbies(): void
    {
        $viewer = $this->user();
        $this->profile($viewer, ['platform' => 'Xbox', 'region' => 'EU', 'language' => 'de']);
        $matching = $this->createLobby($this->user(), ['platform' => 'playstation', 'region' => 'EU', 'language' => 'de']);
        $this->createLobby($this->user(), ['platform' => 'pc', 'region' => 'EU', 'language' => 'de']);
        $this->createLobby($this->user(), ['platform' => 'xbox', 'region' => 'US', 'language' => 'de']);
        $full = $this->createLobby($this->user(), ['platform' => 'xbox', 'region' => 'EU', 'language' => 'de']);
        $full->update(['status' => 'full']);
        $closed = $this->createLobby($this->user(), ['platform' => 'xbox', 'region' => 'EU', 'language' => 'de']);
        $closed->update(['status' => 'closed']);

        $this->getAs($viewer, '/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('counts.active_matching_ready_lobbies', 1);

        $this->postAs($viewer, $this->action($matching, 'join'), ['platform' => 'xbox'])->assertOk();

        $this->getAs($viewer, '/api/v1/bootstrap')
            ->assertOk()
            ->assertJsonPath('counts.active_matching_ready_lobbies', 0);
    }

    public function test_creator_receives_self_common_ground(): void
    {
        $creator = $this->user();
        $this->profile($creator, [
            'profile_visibility' => 'private',
            'platform' => 'pc',
            'hunt_role' => 'support',
            'hunter_dna' => [
                'preferred_mode' => 'flexible',
                'voice' => 'optional',
                'experience' => 'experienced',
                'temper' => 'focused',
                'goals' => ['boss'],
                'mentor' => true,
            ],
        ]);
        $lobby = $this->createLobby($creator);

        $response = $this->getAs($creator, '/api/v1/live-lobbies/'.$lobby->public_id)
            ->assertOk()
            ->assertJsonPath('data.creator.mentor_hunter', true)
            ->assertJsonPath('data.common_ground.self', true);

        $this->assertSame(
            ['platform', 'preferred_mode', 'hunt_role', 'voice', 'temper', 'experience', 'goals', 'mentor'],
            array_column($response->json('data.common_ground.items'), 'key'),
        );
    }

    public function test_private_creator_traits_are_excluded_for_other_viewers(): void
    {
        $creator = $this->user();
        $this->profile($creator, [
            'profile_visibility' => 'private',
            'hunt_role' => 'support',
            'hunter_dna' => [
                'experience' => 'experienced',
                'temper' => 'focused',
                'goals' => ['boss'],
                'mentor' => true,
            ],
        ]);
        $lobby = $this->createLobby($creator, [
            'platform' => 'pc',
            'region' => 'EU',
            'language' => 'de',
            'playstyle' => 'tactical',
        ]);

        $viewer = $this->user();
        $this->profile($viewer, [
            'platform' => 'pc',
            'region' => 'EU',
            'language' => 'de',
            'playstyle' => 'tactical',
            'hunt_role' => 'support',
            'hunter_dna' => [
                'voice' => 'optional',
                'preferred_mode' => 'flexible',
                'experience' => 'experienced',
                'temper' => 'focused',
                'goals' => ['boss'],
                'mentor' => true,
            ],
        ]);

        $response = $this->getAs($viewer, '/api/v1/live-lobbies/'.$lobby->public_id)
            ->assertOk()
            ->assertJsonPath('data.creator.mentor_hunter', false)
            ->assertJsonPath('data.common_ground.self', false)
            ->assertJsonPath('data.common_ground.score', 6)
            ->assertJsonPath('data.common_ground.max_score', 6)
            ->assertJsonMissingPath('data.creator.hunter_dna')
            ->assertJsonMissingPath('data.common_ground.hunter_dna');

        $this->assertSame(
            ['platform', 'region', 'language', 'preferred_mode', 'playstyle', 'voice'],
            array_column($response->json('data.common_ground.items'), 'key'),
        );
    }

    private function createLobby(User $creator, array $overrides = []): LiveLobby
    {
        $response = $this->postAs($creator, '/api/v1/live-lobbies', array_merge([
            'mode' => 'duo',
            'platform' => 'pc',
        ], $overrides))->assertCreated();

        return LiveLobby::where('public_id', $response->json('data.id'))->firstOrFail();
    }

    private function assertHunterNumber(int $value): void
    {
        $this->assertGreaterThanOrEqual(1, $value);
        $this->assertLessThanOrEqual(15, $value);
    }

    private function action(LiveLobby $lobby, string $action): string
    {
        return '/api/v1/live-lobbies/'.$lobby->public_id.'/'.$action;
    }

    private function profile(User $user, array $attributes): UserProfile
    {
        return $user->profile()->create(array_merge([
            'platform' => null,
            'region' => null,
            'language' => null,
            'playstyle' => null,
            'hunt_role' => null,
            'hunter_dna' => null,
        ], $attributes));
    }

    private function userWithPushProfile(array $profileAttributes): User
    {
        $user = $this->user();
        $this->profile($user, $profileAttributes);
        $user->pushDevices()->create([
            'provider' => 'fcm',
            'platform' => 'android',
            'token' => 'token-'.$user->id,
            'token_hash' => hash('sha256', 'token-'.$user->id),
            'last_seen_at' => now(),
        ]);

        return $user;
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
        return ApiAccessToken::createForUser($user, 'Live Lobby API test')['access_token'];
    }

    private function user(): User
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
}
