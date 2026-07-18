<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecuritySettingsTabTest extends TestCase
{
    use RefreshDatabase;

    public function test_integrated_security_tab_can_update_the_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('settings.security.password'), [
                'settings_section' => 'security',
                'settings_action' => 'security_password',
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#security');
        $this->assertTrue(Hash::check('NewPassword456', $user->refresh()->password));
    }

    public function test_integrated_security_errors_return_to_the_security_tab_and_form(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('settings.security.password'), [
                'settings_section' => 'security',
                'settings_action' => 'security_password',
                'current_password' => 'wrong-password',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#security');
        $response->assertSessionHasErrors(['current_password'], null, 'security_password');
    }

    public function test_integrated_security_tab_can_start_two_factor_setup(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('OldPassword123'),
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('settings.security.two-factor.setup'), [
                'settings_section' => 'security',
                'settings_action' => 'security_2fa_setup',
                'current_password' => 'OldPassword123',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#security');
        $response->assertSessionHas('two_factor_setup_secret');
    }
}
