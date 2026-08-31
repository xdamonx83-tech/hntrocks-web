<?php

namespace Tests\Feature;

use App\Events\UserNotificationCreated;
use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeInvitation;
use App\Models\Arcade\ArcadeMatch;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\Notifications\PushPayloadResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ArcadeNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_received_uses_generic_type_payload_action_and_realtime_event(): void
    {
        Event::fake([UserNotificationCreated::class]);
        [$inviter, $invitee] = $this->friends();
        $game = $this->game();

        $this->invite($inviter, $invitee, $game)->assertSuccessful();

        $invitation = ArcadeInvitation::firstOrFail();
        $notification = UserNotification::query()->where('type', 'arcade_invitation_received')->firstOrFail();

        $this->assertSame($invitee->id, $notification->user_id);
        $this->assertSame($inviter->id, $notification->actor_id);
        $this->assertSame('/arcade', $notification->action_url);
        $this->assertSame('hunt-wins', $notification->data['game_slug']);
        $this->assertSame('Hunt Wins', $notification->data['game_name']);
        $this->assertSame('Hunt gewinnt', $notification->data['game_name_de']);
        $this->assertSame($invitation->id, $notification->data['invitation_id']);

        Event::assertDispatched(UserNotificationCreated::class, fn (UserNotificationCreated $event): bool =>
            $event->notification->id === $notification->id
        );

        $eventPayload = (new UserNotificationCreated($notification->fresh(['actor.profile'])))->broadcastWith();
        $this->assertSame('hunt-wins', $eventPayload['data']['game_slug']);
        $this->assertSame('arcade', $eventPayload['target']);
        $this->assertSame('hunt-wins', $eventPayload['payload']['game_slug']);
        $this->assertSame($invitation->id, $eventPayload['payload']['invitation_id']);

        $this->withToken($this->token($invitee))
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('data.0.type', 'arcade_invitation_received')
            ->assertJsonPath('data.0.data.game_slug', 'hunt-wins')
            ->assertJsonPath('data.0.action_url', '/arcade');
    }

    public function test_invitation_accepted_notifies_original_inviter_once_and_targets_match(): void
    {
        [$inviter, $invitee, $invitation] = $this->pending();

        $this->accept($invitee, $invitation)->assertSuccessful();
        $this->accept($invitee, $invitation)->assertSuccessful();

        $match = ArcadeMatch::firstOrFail();
        $notifications = UserNotification::query()
            ->where('type', 'arcade_invitation_accepted')
            ->where('user_id', $inviter->id)
            ->get();

        $this->assertCount(1, $notifications);
        $notification = $notifications->first();
        $this->assertSame($invitee->id, $notification->actor_id);
        $this->assertSame($invitation->id, $notification->data['invitation_id']);
        $this->assertSame($match->id, $notification->data['match_id']);
        $this->assertSame("/arcade/matches/{$match->id}", $notification->action_url);

        $push = app(PushPayloadResolver::class)->forNotification($notification);
        $this->assertSame('arcade_match', $push['target']);
        $this->assertSame($match->id, $push['match_id']);
        $this->assertSame('hunt-wins', $push['game_slug']);
    }

    public function test_ready_notifies_only_other_player_and_retry_does_not_duplicate(): void
    {
        [$first, $second, $match] = $this->accepted();

        $this->ready($first, $match)->assertOk();
        $this->ready($first, $match)->assertOk();

        $this->assertSame(
            1,
            UserNotification::query()
                ->where('type', 'arcade_opponent_ready')
                ->where('user_id', $second->id)
                ->where('actor_id', $first->id)
                ->count(),
        );
        $this->assertSame(
            0,
            UserNotification::query()
                ->where('type', 'arcade_opponent_ready')
                ->where('user_id', $first->id)
                ->where('actor_id', $first->id)
                ->count(),
        );

        $this->ready($second, $match)->assertOk();
        $this->assertSame(
            1,
            UserNotification::query()
                ->where('type', 'arcade_opponent_ready')
                ->where('user_id', $first->id)
                ->where('actor_id', $second->id)
                ->count(),
        );
    }

    public function test_finished_win_creates_one_won_and_one_lost_notification_even_on_move_retry(): void
    {
        [$winner, $loser, $match] = $this->active();
        $columns = [0, 6, 1, 6, 2, 5, 3];

        foreach ($columns as $index => $column) {
            $actor = $index % 2 === 0 ? $winner : $loser;
            $this->move($actor, $match, $column, 'win-'.$index)->assertOk();
        }

        $this->move($winner, $match, 3, 'win-6')->assertOk();
        $match->refresh();

        $won = UserNotification::query()->where('type', 'arcade_match_won')->where('user_id', $winner->id)->get();
        $lost = UserNotification::query()->where('type', 'arcade_match_lost')->where('user_id', $loser->id)->get();

        $this->assertCount(1, $won);
        $this->assertCount(1, $lost);
        $this->assertSame($match->id, $won->first()->data['match_id']);
        $this->assertSame('win', $won->first()->data['result']);
        $this->assertSame('loss', $lost->first()->data['result']);
        $this->assertSame("/arcade/matches/{$match->id}", $won->first()->action_url);
    }

    public function test_draw_notifies_both_players_once(): void
    {
        [$first, $second, $match] = $this->active();
        $columns = [6,5,4,0,5,2,0,1,2,2,3,2,1,6,2,5,4,3,6,6,4,2,5,4,6,5,6,0,3,1,4,0,0,3,1,1,4,5,0,1,3,3];

        foreach ($columns as $index => $column) {
            $actor = $index % 2 === 0 ? $first : $second;
            $this->move($actor, $match, $column, 'draw-'.$index)->assertOk();
        }

        $this->assertSame(2, UserNotification::query()->where('type', 'arcade_match_draw')->count());
        $this->assertSame(1, UserNotification::query()->where('type', 'arcade_match_draw')->where('user_id', $first->id)->count());
        $this->assertSame(1, UserNotification::query()->where('type', 'arcade_match_draw')->where('user_id', $second->id)->count());
    }

    public function test_second_game_reuses_same_generic_notification_type_without_product_code_changes(): void
    {
        [$inviter, $invitee] = $this->friends();
        $game = $this->game([
            'key' => 'bayou-duel',
            'name_de' => 'Bayou Duell',
            'name_en' => 'Bayou Duel',
            'client_engine_key' => 'hunt-wins',
        ]);

        $this->invite($inviter, $invitee, $game)->assertSuccessful();

        $notification = UserNotification::query()->where('type', 'arcade_invitation_received')->firstOrFail();
        $this->assertSame('bayou-duel', $notification->data['game_slug']);
        $this->assertSame('Bayou Duel', $notification->data['game_name']);
        $this->assertSame('Bayou Duell', $notification->data['game_name_de']);
    }

    private function pending(): array
    {
        [$inviter, $invitee] = $this->friends();
        $game = $this->game();
        $this->invite($inviter, $invitee, $game)->assertSuccessful();

        return [$inviter, $invitee, ArcadeInvitation::firstOrFail()];
    }

    private function accepted(): array
    {
        [$inviter, $invitee, $invitation] = $this->pending();
        $this->accept($invitee, $invitation)->assertSuccessful();

        return [$inviter, $invitee, ArcadeMatch::with('players')->firstOrFail()];
    }

    private function active(): array
    {
        [$first, $second, $match] = $this->accepted();
        $this->ready($first, $match)->assertOk();
        $this->ready($second, $match)->assertOk();

        return [$first, $second, $match->fresh()];
    }

    private function invite(User $actor, User $invitee, ArcadeGame $game)
    {
        return $this->withToken($this->token($actor))->postJson(
            "/api/v1/arcade/games/{$game->key}/invitations",
            ['invitee_id' => $invitee->id, 'mode' => 'casual'],
        );
    }

    private function accept(User $user, ArcadeInvitation $invitation)
    {
        return $this->withToken($this->token($user))
            ->postJson("/api/v1/arcade/invitations/{$invitation->id}/accept");
    }

    private function ready(User $user, ArcadeMatch $match)
    {
        return $this->withToken($this->token($user))
            ->postJson("/api/v1/arcade/matches/{$match->id}/ready");
    }

    private function move(User $user, ArcadeMatch $match, int $column, string $clientMoveId)
    {
        return $this->withToken($this->token($user))->postJson(
            "/api/v1/arcade/matches/{$match->id}/moves",
            ['client_move_id' => $clientMoveId, 'column' => $column],
        );
    }

    private function friends(): array
    {
        $first = $this->user('Krispie');
        $second = $this->user('Hunter');
        [$one, $two] = Friendship::pairIds($first, $second);
        Friendship::create([
            'user_one_id' => $one,
            'user_two_id' => $two,
            'requester_id' => $first->id,
            'recipient_id' => $second->id,
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);

        return [$first, $second];
    }

    private function game(array $extra = []): ArcadeGame
    {
        return ArcadeGame::create(array_merge([
            'key' => 'hunt-wins',
            'name_de' => 'Hunt gewinnt',
            'name_en' => 'Hunt Wins',
            'type' => 'native',
            'status' => 'active',
            'min_players' => 2,
            'max_players' => 2,
            'casual_enabled' => true,
            'ranked_enabled' => true,
            'client_engine_key' => 'hunt-wins',
        ], $extra));
    }

    private function user(string $name): User
    {
        $id = bin2hex(random_bytes(5));

        return User::create([
            'name' => $name,
            'username' => strtolower($name).'_'.$id,
            'email' => $id.'@example.test',
            'password' => 'password',
            'status' => 'active',
        ]);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Arcade notification test')['access_token'];
    }
}
