<?php

namespace Tests\Feature;

use App\Models\CrownInventoryItem;
use App\Models\CrownShopItem;
use App\Models\HunterPrestigeHistory;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\HunterPrestigeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HunterPrestigeTest extends TestCase
{
    use RefreshDatabase;

    public function test_level_cap_and_xp_progress_use_cycle_not_lifetime(): void
    {
        $service = app(GamificationService::class);
        $this->assertSame(50, $service->levelForXp(12250));
        $this->assertSame(50, $service->levelForXp(100000));
        $this->assertSame(1, $service->levelForXp(0));

        $user = User::factory()->create([
            'xp_total' => 100000, 'xp_cycle' => 0, 'level' => 1,
        ]);
        $this->assertSame(250, $user->xpToNextLevel());
        $this->assertSame(0, $service->progressPercent($user));
        $user->xp_cycle = 12250;
        $user->level = 50;
        $this->assertSame(0, $user->xpToNextLevel());
        $this->assertSame(100, $service->progressPercent($user));
    }

    public function test_xp_choice_resets_cycle_and_boost_expires_on_next_prestige(): void
    {
        $user = User::factory()->create([
            'xp_total' => 12500, 'xp_cycle' => 12250, 'level' => 50,
        ]);
        $service = app(HunterPrestigeService::class);
        $first = $service->start($user, 'xp');
        $this->assertSame(1, $first->prestige_number);
        $this->assertSame('xp', $first->reward_type);
        $this->assertSame(12500, $user->fresh()->xp_total);
        $this->assertSame(0, $user->fresh()->xp_cycle);
        $this->assertTrue($user->fresh()->prestige_xp_boost);

        $awards = app(GamificationService::class);
        for ($i = 0; $i < 10; $i++) {
            $awards->award($user, 'prestige_test', points: 2, oncePerSource: false);
        }
        $this->assertSame(12522, $user->fresh()->xp_total);
        $this->assertSame(22, $user->fresh()->xp_cycle);
        $this->assertSame(0, $user->fresh()->prestige_xp_bonus_remainder);

        $user->refresh();
        $user->level = 50;
        $user->xp_cycle = HunterPrestigeService::CYCLE_CAP;
        $user->save();
        $next = $service->start($user, 'xp');
        $this->assertSame(2, $next->prestige_number);
        $this->assertSame(2, HunterPrestigeHistory::where('user_id', $user->id)->count());
    }

    public function test_cosmetic_choice_never_awards_owned_cosmetic(): void
    {
        CrownShopItem::query()->update(['is_active' => false]);
        $user = User::factory()->create([
            'xp_total' => 12250, 'xp_cycle' => 12250, 'level' => 50,
        ]);
        $one = CrownShopItem::query()->create([
            'key' => 'prestige-test-owned', 'type' => 'avatar_frame', 'slot' => 'avatar_frame',
            'name_de' => 'Owned', 'price' => 100, 'rarity' => 'common', 'is_active' => true,
        ]);
        $two = CrownShopItem::query()->create([
            'key' => 'prestige-test-unowned', 'type' => 'title', 'slot' => 'profile_title',
            'name_de' => 'Unowned', 'price' => 100, 'rarity' => 'common', 'is_active' => true,
        ]);
        CrownInventoryItem::create(['user_id' => $user->id, 'shop_item_id' => $one->id]);

        $service = app(HunterPrestigeService::class);
        $history = $service->start($user, 'cosmetic');
        $this->assertSame($two->id, $history->shop_item_id);
        $this->assertTrue(CrownInventoryItem::where('user_id', $user->id)->where('shop_item_id', $two->id)->exists());
        $this->assertFalse($user->fresh()->prestige_xp_boost);
        $this->assertSame(1, CrownInventoryItem::where('user_id', $user->id)->where('shop_item_id', $one->id)->count());
    }

    public function test_cosmetic_choice_is_blocked_when_all_available_items_are_owned(): void
    {
        $user = User::factory()->create([
            'xp_total' => 12250, 'xp_cycle' => 12250, 'level' => 50,
        ]);
        $service = app(HunterPrestigeService::class);
        foreach ($service->cosmeticCandidates($user)->get() as $item) {
            CrownInventoryItem::create(['user_id' => $user->id, 'shop_item_id' => $item->id]);
        }
        $this->assertSame(0, $service->availableCosmetics($user));
        $this->expectException(ValidationException::class);
        $service->start($user, 'cosmetic');
    }

    public function test_start_requires_level_50_and_is_only_one_prestige_per_cycle(): void
    {
        $user = User::factory()->create(['level' => 49, 'xp_cycle' => 12000]);
        $service = app(HunterPrestigeService::class);
        try {
            $service->start($user, 'xp');
            $this->fail('Prestige accepted below Level 50');
        } catch (ValidationException) {
            $this->assertSame(0, HunterPrestigeHistory::where('user_id', $user->id)->count());
        }

        $user->level = 50;
        $user->xp_cycle = 12250;
        $user->save();
        $service->start($user, 'xp');

        $this->expectException(ValidationException::class);
        $service->start($user, 'xp');
    }
}
