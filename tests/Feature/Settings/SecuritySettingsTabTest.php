<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Models\UserSecurityEvent;
use App\Services\SecurityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    public function test_security_history_loads_older_events_as_json(): void
    {
        $user = User::factory()->create();
        $events = collect();

        foreach (range(1, 14) as $index) {
            $events->push(UserSecurityEvent::create([
                'user_id' => $user->id,
                'event' => 'login_failed',
                'ip_address' => '203.0.113.'.$index,
                'user_agent' => 'Test Browser '.$index,
            ]));
        }

        $initialCursor = $events->reverse()->values()->get(11)->id;

        $response = $this
            ->actingAs($user)
            ->getJson(route('settings.security.events', ['before' => $initialCursor]));

        $response->assertOk()
            ->assertJsonPath('has_more', false)
            ->assertJsonPath('next_cursor', $events->first()->id);

        $this->assertStringContainsString(
            'data-security-event-id="'.$events->get(1)->id.'"',
            (string) $response->json('html')
        );
    }

    public function test_security_log_stores_available_approximate_location_headers(): void
    {
        $user = User::factory()->create();
        $request = Request::create('/login', 'POST', [], [], [], [
            'REMOTE_ADDR' => '203.0.113.10',
            'HTTP_USER_AGENT' => 'Example Browser',
            'HTTP_CF_IPCOUNTRY' => 'DE',
            'HTTP_CF_REGION' => 'Hessen',
            'HTTP_CF_REGION_CODE' => 'HE',
            'HTTP_CF_IPCITY' => 'Frankfurt',
        ]);

        app(SecurityLogService::class)->record($user, 'login_failed', $request);

        $event = $user->securityEvents()->latest('id')->firstOrFail();

        $this->assertSame('DE', data_get($event->meta, 'location.country_code'));
        $this->assertSame('Hessen', data_get($event->meta, 'location.region'));
        $this->assertSame('Frankfurt', data_get($event->meta, 'location.city'));
    }
}
