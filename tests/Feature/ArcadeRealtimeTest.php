<?php

namespace Tests\Feature;

use App\Events\ArcadeMatchUpdated;
use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeMatch;
use App\Models\User;
use App\Services\Arcade\Engines\HuntWinsEngine;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ArcadeRealtimeTest extends TestCase
{
    use DatabaseMigrations;

    public function test_valid_move_emits_match_update_after_commit_with_current_version(): void
    {
        Event::fake([ArcadeMatchUpdated::class]);
        [$player, $other, $match] = $this->activeMatch();
        $this->withToken($this->token($player))->postJson("/api/v1/arcade/matches/{$match->id}/moves", ['client_move_id' => 'realtime-1', 'column' => 0])->assertOk();
        Event::assertDispatched(ArcadeMatchUpdated::class, function (ArcadeMatchUpdated $event) use ($match): bool {
            $current = $match->fresh();
            return $event->match->id === $match->id && $event->match->version === $current->version && $event->broadcastWith()['version'] === $current->version;
        });
    }

    public function test_private_match_channel_allows_participant_and_rejects_unrelated_user(): void
    {
        [$player, $other, $match] = $this->activeMatch();
        $payload = ['socket_id' => '1234.5678', 'channel_name' => 'private-arcade.match.'.$match->id];
        $this->withToken($this->token($player))->postJson('/api/v1/broadcasting/auth', $payload)->assertSuccessful();
        $this->withToken($this->token($this->user()))->postJson('/api/v1/broadcasting/auth', $payload)->assertForbidden();
    }

    private function activeMatch(): array
    {
        $player = $this->user(); $other = $this->user();
        $game = ArcadeGame::create(['key' => 'hunt-wins', 'name_de' => 'Hunt gewinnt', 'name_en' => 'Hunt Wins', 'type' => 'native', 'status' => 'active', 'min_players' => 2, 'max_players' => 2, 'casual_enabled' => true, 'ranked_enabled' => true, 'client_engine_key' => 'hunt-wins']);
        $match = ArcadeMatch::create(['game_id' => $game->id, 'mode' => 'casual', 'status' => 'active', 'state' => (new HuntWinsEngine)->initialize(), 'version' => 2, 'current_seat' => 1, 'created_by' => $player->id, 'started_at' => now()]);
        $match->players()->createMany([['user_id' => $player->id, 'seat' => 1, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()], ['user_id' => $other->id, 'seat' => 2, 'status' => 'ready', 'joined_at' => now(), 'ready_at' => now()]]);
        return [$player, $other, $match];
    }
    private function user(): User { $id = bin2hex(random_bytes(5)); return User::create(['name' => 'Hunter', 'username' => 'hunter_'.$id, 'email' => $id.'@example.test', 'password' => 'password', 'status' => 'active']); }
    private function token(User $user): string { return ApiAccessToken::createForUser($user, 'Arcade test')['access_token']; }
}
