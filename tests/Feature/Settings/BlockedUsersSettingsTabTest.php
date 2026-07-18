<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlockedUsersSettingsTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrated_settings_tab_can_block_a_user(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create(['username' => 'blocked-hunter']);

        $response = $this
            ->actingAs($user)
            ->post(route('settings.privacy.blocks.store'), [
                'settings_section' => 'blocked',
                'username' => '@blocked-hunter',
                'reason' => 'Test block',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#blocked');
        $this->assertDatabaseHas('user_blocks', [
            'user_id' => $user->id,
            'blocked_user_id' => $target->id,
            'reason' => 'Test block',
        ]);
    }

    public function test_integrated_settings_tab_keeps_errors_on_the_blocked_tab(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('settings.privacy.blocks.store'), [
                'settings_section' => 'blocked',
                'username' => 'missing-hunter',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#blocked');
        $response->assertSessionHasErrors('username');
    }

    public function test_integrated_settings_tab_can_remove_a_block(): void
    {
        $user = User::factory()->create();
        $target = User::factory()->create();
        $block = UserBlock::create([
            'user_id' => $user->id,
            'blocked_user_id' => $target->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('settings.privacy.blocks.destroy', $block), [
                'settings_section' => 'blocked',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#blocked');
        $this->assertDatabaseMissing('user_blocks', ['id' => $block->id]);
    }
}
