<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCollaborationPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_open_collaboration_previews(): void
    {
        $viewer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($viewer)
            ->get(route('admin.collaboration.projects'))
            ->assertForbidden();

        $this->actingAs($viewer)
            ->get(route('admin.collaboration.chat'))
            ->assertForbidden();
    }

    public function test_admin_gets_real_username_and_only_explicit_demo_projects(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.collaboration.projects'))
            ->assertOk()
            ->assertSee('@'.$admin->username)
            ->assertSee('Vier Beispieldatensätze')
            ->assertSee('DEMO');
    }

    public function test_admin_chat_is_only_a_noninteractive_preview(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.collaboration.chat'))
            ->assertOk()
            ->assertSee('kein Nachrichtenversand')
            ->assertSee('disabled', false);
    }
}
