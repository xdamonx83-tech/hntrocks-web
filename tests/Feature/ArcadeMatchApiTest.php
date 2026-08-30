<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\Arcade\ArcadeGame;
use App\Models\Arcade\ArcadeInvitation;
use App\Models\Arcade\ArcadeMatch;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArcadeMatchApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_accepting_invitation_creates_exactly_one_match_and_retry_is_idempotent(): void { [$a, $b, $invitation] = $this->pending(); $this->accept($b, $invitation)->assertSuccessful(); $this->accept($b, $invitation)->assertSuccessful(); $this->assertDatabaseCount('arcade_matches', 1); }
    public function test_acceptance_creates_exactly_two_players_with_unique_seats(): void { [$a, $b, $match] = $this->accepted(); $this->assertCount(2, $match->players); $this->assertSame([1, 2], $match->players->pluck('seat')->sort()->values()->all()); }
    public function test_only_participants_can_view_match_and_unrelated_user_is_forbidden(): void { [$a, $b, $match] = $this->accepted(); $this->getAs($a, "/api/v1/arcade/matches/{$match->id}")->assertOk(); $this->getAs($b, "/api/v1/arcade/matches/{$match->id}")->assertOk(); $this->getAs($this->user(), "/api/v1/arcade/matches/{$match->id}")->assertForbidden(); }
    public function test_participant_can_ready_only_themselves_and_one_ready_keeps_waiting(): void { [$a, $b, $match] = $this->accepted(); $this->ready($a, $match)->assertOk()->assertJsonPath('data.status', 'waiting_ready')->assertJsonPath('data.players.0.ready', true)->assertJsonPath('data.players.1.ready', false); $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'user_id' => $b->id, 'ready_at' => null]); }
    public function test_unrelated_user_cannot_ready_a_participant(): void { [$a, $b, $match] = $this->accepted(); $this->ready($this->user(), $match)->assertForbidden(); $this->assertDatabaseMissing('arcade_match_players', ['match_id' => $match->id, 'status' => 'ready']); }
    public function test_both_ready_starts_with_empty_six_by_seven_board_and_seat_one(): void { [$a, $b, $match] = $this->active(); $match->refresh(); $this->assertSame('active', $match->status->value); $this->assertSame(1, $match->current_seat); $this->assertCount(6, $match->state['board']); foreach ($match->state['board'] as $row) $this->assertSame(array_fill(0, 7, 0), $row); }
    public function test_non_participant_cannot_move(): void { [$a, $b, $match] = $this->active(); $this->move($this->user(), $match, 0)->assertForbidden(); }
    public function test_move_before_active_is_rejected(): void { [$a, $b, $match] = $this->accepted(); $this->move($a, $match, 0)->assertConflict(); }
    public function test_wrong_turn_is_rejected(): void { [$a, $b, $match] = $this->active(); $this->move($b, $match, 0)->assertConflict(); }
    public function test_columns_outside_bounds_are_rejected(): void { [$a, $b, $match] = $this->active(); $this->move($a, $match, -1)->assertUnprocessable(); $this->move($a, $match, 7)->assertUnprocessable(); }
    public function test_tokens_drop_to_bottom_then_stack_and_turns_alternate(): void { [$a, $b, $match] = $this->active(); $this->move($a, $match, 3)->assertOk()->assertJsonPath('data.state.board.5.3', 1)->assertJsonPath('data.current_seat', 2); $this->move($b, $match, 3)->assertOk()->assertJsonPath('data.state.board.4.3', 2)->assertJsonPath('data.current_seat', 1); }
    public function test_full_column_is_rejected(): void { [$a, $b, $match] = $this->active(); foreach ([0, 0, 0, 0, 0, 0] as $index => $column) $this->move($index % 2 === 0 ? $a : $b, $match, $column)->assertOk(); $this->move($a, $match, 0)->assertUnprocessable(); }
    public function test_version_and_move_sequence_increment_monotonically(): void { [$a, $b, $match] = $this->active(); $start = $match->version; $this->move($a, $match, 0)->assertJsonPath('data.version', $start + 1); $this->move($b, $match, 1)->assertJsonPath('data.version', $start + 2); $this->assertSame([1, 2], $match->moves()->pluck('sequence')->all()); }
    public function test_horizontal_winning_match_finishes_and_persists_results(): void { [$a, $b, $match] = $this->active(); $this->play($match, $a, $b, [0, 6, 1, 6, 2, 5, 3]); $match->refresh(); $this->assertSame('finished', $match->status->value); $this->assertSame(1, $match->winner_seat); $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'seat' => 1, 'result' => 'win']); $this->assertDatabaseHas('arcade_match_players', ['match_id' => $match->id, 'seat' => 2, 'result' => 'loss']); }
    public function test_further_move_after_finished_is_rejected(): void { [$a, $b, $match] = $this->active(); $this->play($match, $a, $b, [0, 6, 1, 6, 2, 5, 3]); $this->move($b, $match, 4)->assertConflict(); }
    public function test_draw_finishes_without_winner_and_persists_draw_results(): void { [$a, $b, $match] = $this->active(); $this->play($match, $a, $b, [6,5,4,0,5,2,0,1,2,2,3,2,1,6,2,5,4,3,6,6,4,2,5,4,6,5,6,0,3,1,4,0,0,3,1,1,4,5,0,1,3,3]); $match->refresh(); $this->assertTrue($match->state['draw']); $this->assertNull($match->winner_seat); $this->assertSame(['draw'], $match->players()->get()->map(fn ($player) => $player->result->value)->unique()->values()->all()); }
    public function test_identical_move_retry_creates_one_move_and_returns_authoritative_state(): void { [$a, $b, $match] = $this->active(); $id = 'retry-id'; $first = $this->move($a, $match, 2, $id)->assertOk()->json('data'); $retry = $this->move($a, $match, 2, $id)->assertOk()->json('data'); $this->assertSame($first['version'], $retry['version']); $this->assertSame($first['state'], $retry['state']); $this->assertDatabaseCount('arcade_match_moves', 1); }
    public function test_changed_payload_with_same_client_move_id_conflicts(): void { [$a, $b, $match] = $this->active(); $this->move($a, $match, 2, 'same-id')->assertOk(); $this->move($a, $match, 3, 'same-id')->assertConflict(); $this->assertDatabaseCount('arcade_match_moves', 1); }
    public function test_participant_lists_own_match_but_unrelated_match_is_private(): void { [$a, $b, $match] = $this->accepted(); $stranger = $this->user(); $this->getAs($a, '/api/v1/arcade/matches')->assertOk()->assertJsonPath('data.0.id', $match->id); $this->getAs($stranger, '/api/v1/arcade/matches')->assertOk()->assertJsonCount(0, 'data'); }
    public function test_deleting_users_nulls_historical_user_references_without_deleting_history(): void { [$a, $b, $match] = $this->active(); $this->move($a, $match, 0)->assertOk(); $invitationId = ArcadeInvitation::firstOrFail()->id; $a->delete(); $b->delete(); $this->assertDatabaseHas('arcade_matches', ['id' => $match->id, 'created_by' => null]); $this->assertDatabaseHas('arcade_invitations', ['id' => $invitationId, 'inviter_id' => null, 'invitee_id' => null]); $this->assertSame(2, $match->players()->whereNull('user_id')->count()); $this->assertDatabaseCount('arcade_match_moves', 1); }

    private function pending(): array { $a = $this->user(); $b = $this->user(); [$one, $two] = Friendship::pairIds($a, $b); Friendship::create(['user_one_id' => $one, 'user_two_id' => $two, 'requester_id' => $a->id, 'recipient_id' => $b->id, 'status' => 'accepted', 'accepted_at' => now()]); $game = $this->game(); $this->withToken($this->token($a))->postJson("/api/v1/arcade/games/{$game->key}/invitations", ['invitee_id' => $b->id, 'mode' => 'casual'])->assertSuccessful(); return [$a, $b, ArcadeInvitation::firstOrFail()]; }
    private function accepted(): array { [$a, $b, $invitation] = $this->pending(); $this->accept($b, $invitation)->assertSuccessful(); return [$a, $b, ArcadeMatch::with('players')->firstOrFail()]; }
    private function active(): array { [$a, $b, $match] = $this->accepted(); $this->ready($a, $match)->assertOk(); $this->ready($b, $match)->assertOk(); return [$a, $b, $match->fresh()]; }
    private function play(ArcadeMatch $match, User $a, User $b, array $columns): void { foreach ($columns as $index => $column) $this->move($index % 2 === 0 ? $a : $b, $match, $column, 'play-'.$index)->assertOk(); }
    private function accept(User $user, ArcadeInvitation $invitation) { return $this->withToken($this->token($user))->postJson("/api/v1/arcade/invitations/{$invitation->id}/accept"); }
    private function ready(User $user, ArcadeMatch $match) { return $this->withToken($this->token($user))->postJson("/api/v1/arcade/matches/{$match->id}/ready"); }
    private function move(User $user, ArcadeMatch $match, int $column, ?string $id = null) { return $this->withToken($this->token($user))->postJson("/api/v1/arcade/matches/{$match->id}/moves", ['client_move_id' => $id ?? bin2hex(random_bytes(8)), 'column' => $column]); }
    private function getAs(User $user, string $uri) { return $this->withToken($this->token($user))->getJson($uri); }
    private function game(): ArcadeGame { return ArcadeGame::create(['key' => 'hunt-wins', 'name_de' => 'Hunt gewinnt', 'name_en' => 'Hunt Wins', 'type' => 'native', 'status' => 'active', 'min_players' => 2, 'max_players' => 2, 'casual_enabled' => true, 'ranked_enabled' => true, 'client_engine_key' => 'hunt-wins']); }
    private function user(): User { $id = bin2hex(random_bytes(5)); return User::create(['name' => 'Hunter', 'username' => 'hunter_'.$id, 'email' => $id.'@example.test', 'password' => 'password', 'status' => 'active']); }
    private function token(User $user): string { return ApiAccessToken::createForUser($user, 'Arcade test')['access_token']; }
}
