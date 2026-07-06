<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\CrownTransaction;
use App\Models\CrownWallet;
use App\Models\User;
use App\Services\Economy\CrownsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CrownDailyStreakApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_status_shows_claim_available_on_first_day(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');

        $this->getAs($this->user(), '/api/v1/crowns/daily-streak')
            ->assertOk()
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.claim_available', true)
            ->assertJsonPath('data.claimed_today', false)
            ->assertJsonPath('data.next_streak_day', 1)
            ->assertJsonPath('data.today_amount', 5)
            ->assertJsonPath('data.server_date', '2026-07-06');
    }

    public function test_claim_writes_transaction_and_increases_wallet(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();

        $this->postAs($user, '/api/v1/crowns/daily-streak/claim')
            ->assertOk()
            ->assertJsonPath('data.claimed', true)
            ->assertJsonPath('data.amount', 5)
            ->assertJsonPath('data.streak_day', 1)
            ->assertJsonPath('data.balance', 5)
            ->assertJsonPath('data.lifetime_earned', 5)
            ->assertJsonPath('data.daily_streak.claimed_today', true);

        $this->assertDatabaseHas('crown_transactions', [
            'user_id' => $user->id,
            'type' => CrownTransaction::TYPE_CREDIT,
            'action' => 'daily_login_streak',
            'amount' => 5,
            'description' => 'Tägliche Login-Serie: Tag 1',
        ]);

        $wallet = CrownWallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame(5, $wallet->balance);
        $this->assertSame(5, $wallet->lifetime_earned);
        $this->assertSame(0, $wallet->lifetime_spent);
    }

    public function test_second_claim_same_day_does_not_pay_again(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();

        $this->postAs($user, '/api/v1/crowns/daily-streak/claim')->assertOk();
        $this->postAs($user, '/api/v1/crowns/daily-streak/claim')
            ->assertOk()
            ->assertJsonPath('data.claimed', false)
            ->assertJsonPath('data.already_claimed', true);

        $this->assertDatabaseCount('crown_transactions', 1);
        $this->assertDatabaseHas('crown_wallets', [
            'user_id' => $user->id,
            'balance' => 5,
            'lifetime_earned' => 5,
        ]);
    }

    public function test_claim_on_following_day_increases_streak(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();
        $this->postAs($user, '/api/v1/crowns/daily-streak/claim')->assertOk();

        Carbon::setTestNow('2026-07-07 09:00:00');

        $this->postAs($user, '/api/v1/crowns/daily-streak/claim')
            ->assertOk()
            ->assertJsonPath('data.claimed', true)
            ->assertJsonPath('data.amount', 7)
            ->assertJsonPath('data.streak_day', 2)
            ->assertJsonPath('data.balance', 12);
    }

    public function test_claim_after_missed_day_resets_to_one(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();
        $this->postAs($user, '/api/v1/crowns/daily-streak/claim')->assertOk();

        Carbon::setTestNow('2026-07-08 09:00:00');

        $this->postAs($user, '/api/v1/crowns/daily-streak/claim')
            ->assertOk()
            ->assertJsonPath('data.claimed', true)
            ->assertJsonPath('data.amount', 5)
            ->assertJsonPath('data.streak_day', 1)
            ->assertJsonPath('data.balance', 10);
    }

    public function test_dismiss_pays_nothing_and_only_blocks_popup_today(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();

        $this->postAs($user, '/api/v1/crowns/daily-streak/dismiss')
            ->assertOk()
            ->assertJsonPath('data.daily_streak.dismissed_today', true)
            ->assertJsonPath('data.daily_streak.claim_available', true);

        $this->assertDatabaseCount('crown_transactions', 0);
        $this->assertSame(0, CrownWallet::query()->where('user_id', $user->id)->count());

        Carbon::setTestNow('2026-07-07 09:00:00');

        $this->getAs($user, '/api/v1/crowns/daily-streak')
            ->assertOk()
            ->assertJsonPath('data.dismissed_today', false)
            ->assertJsonPath('data.next_streak_day', 1);
    }

    public function test_crowns_index_includes_daily_streak_without_collecting_it(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();

        $this->getAs($user, '/api/v1/crowns')
            ->assertOk()
            ->assertJsonPath('data.daily_streak.claim_available', true)
            ->assertJsonPath('data.daily_streak.today_amount', 5);

        $this->assertDatabaseMissing('crown_transactions', [
            'user_id' => $user->id,
            'action' => 'daily_login_streak',
        ]);
    }

    public function test_legacy_web_daily_login_route_does_not_pay_old_bonus(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();

        $this->actingAs($user)
            ->from('/crowns')
            ->post('/crowns/daily-login')
            ->assertRedirect('/crowns')
            ->assertSessionHas('error', 'Die tägliche Belohnung ist nur in der App verfügbar.');

        $this->assertDatabaseMissing('crown_transactions', [
            'user_id' => $user->id,
            'action' => 'daily_login',
        ]);
        $this->assertSame(0, CrownWallet::query()->where('user_id', $user->id)->count());
    }

    public function test_disabled_legacy_daily_login_reward_returns_null(): void
    {
        Carbon::setTestNow('2026-07-06 09:00:00');
        $user = $this->user();

        $transaction = app(CrownsService::class)->rewardDailyLogin($user);

        $this->assertNull($transaction);
        $this->assertDatabaseMissing('crown_transactions', [
            'user_id' => $user->id,
            'action' => 'daily_login',
        ]);
        $this->assertSame(0, CrownWallet::query()->where('user_id', $user->id)->count());
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
        return ApiAccessToken::createForUser($user, 'Daily streak API test')['access_token'];
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
