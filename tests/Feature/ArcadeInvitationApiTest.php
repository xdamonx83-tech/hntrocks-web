<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeInvitation;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcadeInvitationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_invitation_api_requires_authentication(): void { $this->game(); $this->getJson('/api/v1/arcade/invitations')->assertUnauthorized(); $this->postJson('/api/v1/arcade/games/hunt-wins/invitations')->assertUnauthorized(); }
    public function test_self_invitation_is_rejected(): void { $user = $this->user(); $this->postAs($user, $this->game(), $user)->assertUnprocessable(); }
    public function test_non_friend_invitation_is_rejected(): void { $this->postAs($this->user(), $this->game(), $this->user())->assertForbidden(); }
    public function test_accepted_friend_invitation_is_allowed(): void { [$a, $b] = $this->friends(); $this->postAs($a, $this->game(), $b)->assertSuccessful()->assertJsonPath('data.status', 'pending'); }
    public function test_blocked_user_invitation_is_rejected_in_either_direction(): void { [$a, $b] = $this->friends(); UserBlock::create(['user_id' => $b->id, 'blocked_user_id' => $a->id]); $this->postAs($a, $this->game(), $b)->assertForbidden(); }
    public function test_disabled_game_is_rejected(): void { [$a, $b] = $this->friends(); $this->postAs($a, $this->game(['status' => 'disabled']), $b)->assertUnprocessable(); }
    public function test_disabled_casual_and_ranked_modes_are_rejected(): void { [$a, $b] = $this->friends(); $game = $this->game(['casual_enabled' => false, 'ranked_enabled' => false]); $this->postAs($a, $game, $b, 'casual')->assertUnprocessable(); $this->postAs($a, $game, $b, 'ranked')->assertUnprocessable(); }
    public function test_duplicate_pending_invitation_is_rejected(): void { [$a, $b] = $this->friends(); $game = $this->game(); $this->postAs($a, $game, $b)->assertSuccessful(); $this->postAs($b, $game, $a)->assertConflict(); $this->assertDatabaseCount('arcade_invitations', 1); }
    public function test_only_invitee_can_accept(): void { [$a, $b] = $this->friends(); $invitation = $this->invitation($a, $b); $this->actionAs($a, $invitation, 'accept')->assertForbidden(); $this->actionAs($b, $invitation, 'accept')->assertSuccessful(); }
    public function test_only_invitee_can_decline(): void { [$a, $b] = $this->friends(); $invitation = $this->invitation($a, $b); $this->actionAs($a, $invitation, 'decline')->assertForbidden(); $this->actionAs($b, $invitation, 'decline')->assertSuccessful(); }
    public function test_only_inviter_can_cancel(): void { [$a, $b] = $this->friends(); $invitation = $this->invitation($a, $b); $this->actionAs($b, $invitation, 'cancel')->assertForbidden(); $this->actionAs($a, $invitation, 'cancel')->assertSuccessful(); }
    public function test_expired_invitation_cannot_be_accepted(): void { [$a, $b] = $this->friends(); $invitation = $this->invitation($a, $b); $invitation->update(['expires_at' => now()->subMinute()]); $this->actionAs($b, $invitation, 'accept')->assertUnprocessable(); $this->assertDatabaseHas('arcade_invitations', ['id' => $invitation->id, 'status' => 'expired']); $this->assertDatabaseCount('arcade_matches', 0); }

    private function invitation(User $a, User $b): ArcadeInvitation { $game = $this->game(); $this->postAs($a, $game, $b)->assertSuccessful(); return ArcadeInvitation::firstOrFail(); }
    private function postAs(User $actor, ArcadeGame $game, User $invitee, string $mode = 'casual') { return $this->withToken($this->token($actor))->postJson("/api/v1/arcade/games/{$game->key}/invitations", ['invitee_id' => $invitee->id, 'mode' => $mode]); }
    private function actionAs(User $actor, ArcadeInvitation $invitation, string $action) { return $this->withToken($this->token($actor))->postJson("/api/v1/arcade/invitations/{$invitation->id}/{$action}"); }
    private function friends(): array { $a = $this->user(); $b = $this->user(); [$one, $two] = Friendship::pairIds($a, $b); Friendship::create(['user_one_id' => $one, 'user_two_id' => $two, 'requester_id' => $a->id, 'recipient_id' => $b->id, 'status' => 'accepted', 'accepted_at' => now()]); return [$a, $b]; }
    private function game(array $extra = []): ArcadeGame { return ArcadeGame::create(array_merge(['key' => 'hunt-wins', 'name_de' => 'Hunt gewinnt', 'name_en' => 'Hunt Wins', 'type' => 'native', 'status' => 'active', 'min_players' => 2, 'max_players' => 2, 'casual_enabled' => true, 'ranked_enabled' => true, 'client_engine_key' => 'hunt-wins'], $extra)); }
    private function user(): User { $id = bin2hex(random_bytes(5)); return User::create(['name' => 'Hunter', 'username' => 'hunter_'.$id, 'email' => $id.'@example.test', 'password' => 'password', 'status' => 'active']); }
    private function token(User $user): string { return ApiAccessToken::createForUser($user, 'Arcade test')['access_token']; }
}
