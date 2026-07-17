<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneralSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_update_the_interface_language(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->put(route('account.settings.general.update'), [
                'locale' => 'en',
            ]);

        $response->assertRedirect(route('account.settings.edit').'#general');
        $response->assertSessionHas('locale', 'en');
        $response->assertCookie('locale', 'en');
    }

    public function test_general_settings_reject_an_unsupported_language(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('account.settings.edit').'#general')
            ->put(route('account.settings.general.update'), [
                'locale' => 'fr',
            ]);

        $response->assertRedirect(route('account.settings.edit'));
        $response->assertSessionHasErrors('locale');
    }
}
