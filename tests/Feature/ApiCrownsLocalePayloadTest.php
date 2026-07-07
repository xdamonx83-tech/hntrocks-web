<?php

namespace Tests\Feature;

use App\Models\ApiAccessToken;
use App\Models\CrownInventoryItem;
use App\Models\CrownShopItem;
use App\Models\CrownTransaction;
use App\Models\CrownWallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ApiCrownsLocalePayloadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_query_locale_en_returns_english_shop_payload(): void
    {
        $user = $this->user();

        $payload = $this->getAs($user, '/api/v1/crowns?locale=en')
            ->assertOk()
            ->json('data');

        $item = $this->shopItemFromPayload($payload, 'avatar_frame_bayou_iron');

        $this->assertSame('Bayou Iron Avatar Frame', $item['name']);
        $this->assertSame('Bayou-Iron Avatarrahmen', $item['name_de']);
        $this->assertSame('Bayou Iron Avatar Frame', $item['name_en']);
        $this->assertSame('A dark rusty frame for your profile image. Purchases land in your inventory and can be prepared there.', $item['description']);
    }

    public function test_query_locale_de_returns_german_shop_payload(): void
    {
        $user = $this->user();

        $payload = $this->getAs($user, '/api/v1/crowns?locale=de')
            ->assertOk()
            ->json('data');

        $item = $this->shopItemFromPayload($payload, 'avatar_frame_bayou_iron');

        $this->assertSame('Bayou-Iron Avatarrahmen', $item['name']);
        $this->assertSame('Ein dunkler, rostiger Rahmen für dein Profilbild. Käufe landen in deinem Inventar und können dort vorbereitet werden.', $item['description']);
    }

    public function test_x_hnt_locale_header_returns_english_shop_payload(): void
    {
        $user = $this->user();

        $payload = $this->withHeader('X-HNT-Locale', 'en')
            ->getAs($user, '/api/v1/crowns')
            ->assertOk()
            ->json('data');

        $item = $this->shopItemFromPayload($payload, 'avatar_frame_bayou_iron');

        $this->assertSame('Bayou Iron Avatar Frame', $item['name']);
    }

    public function test_accept_language_header_returns_english_shop_payload(): void
    {
        $user = $this->user();

        $payload = $this->withHeader('Accept-Language', 'en-US,en;q=0.9,de;q=0.8')
            ->getAs($user, '/api/v1/crowns')
            ->assertOk()
            ->json('data');

        $item = $this->shopItemFromPayload($payload, 'avatar_frame_bayou_iron');

        $this->assertSame('Bayou Iron Avatar Frame', $item['name']);
    }

    public function test_invalid_locale_falls_back_to_current_app_locale_without_leaking(): void
    {
        App::setLocale('de');
        $user = $this->user();

        $payload = $this->getAs($user, '/api/v1/crowns?locale=fr')
            ->assertOk()
            ->json('data');

        $item = $this->shopItemFromPayload($payload, 'avatar_frame_bayou_iron');

        $this->assertSame('Bayou-Iron Avatarrahmen', $item['name']);
        $this->assertSame('de', app()->getLocale());
    }

    public function test_inventory_item_payload_follows_selected_locale(): void
    {
        $user = $this->user();
        $shopItem = $this->shopItem('avatar_frame_bayou_iron');

        CrownInventoryItem::query()->create([
            'user_id' => $user->id,
            'shop_item_id' => $shopItem->id,
            'purchased_at' => now(),
        ]);

        $payload = $this->getAs($user, '/api/v1/crowns?locale=en')
            ->assertOk()
            ->json('data');

        $inventoryItem = collect($payload['inventory_items'])
            ->firstWhere('shop_item_id', $shopItem->id);

        $this->assertNotNull($inventoryItem);
        $this->assertSame('Bayou Iron Avatar Frame', $inventoryItem['item']['name']);
    }

    public function test_known_history_action_returns_english_description(): void
    {
        Carbon::setTestNow('2026-07-07 12:00:00');

        $user = $this->user();
        $wallet = CrownWallet::query()->create([
            'user_id' => $user->id,
            'balance' => 25,
            'lifetime_earned' => 25,
            'lifetime_spent' => 0,
        ]);

        CrownTransaction::query()->create([
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'type' => CrownTransaction::TYPE_CREDIT,
            'action' => 'daily_login_streak',
            'amount' => 25,
            'balance_after' => 25,
            'description' => 'Tägliche Login-Serie: Tag 1',
            'metadata' => ['streak_day' => 1],
            'collected_at' => now(),
        ]);

        $payload = $this->getAs($user, '/api/v1/crowns?locale=en')
            ->assertOk()
            ->json('data');

        $transaction = collect($payload['history'])->firstWhere('action', 'daily_login_streak');

        $this->assertNotNull($transaction);
        $this->assertSame('Daily login', $transaction['description']);
    }

    private function shopItemFromPayload(array $payload, string $key): array
    {
        $item = collect($payload['shop_items'])->firstWhere('key', $key);

        $this->assertNotNull($item, "Shop item [{$key}] was not present in the Crowns payload.");

        return $item;
    }

    private function shopItem(string $key): CrownShopItem
    {
        return CrownShopItem::query()->where('key', $key)->firstOrFail();
    }

    private function getAs(User $user, string $uri)
    {
        return $this->withToken($this->token($user))->getJson($uri);
    }

    private function token(User $user): string
    {
        return ApiAccessToken::createForUser($user, 'Crowns locale API test')['access_token'];
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
