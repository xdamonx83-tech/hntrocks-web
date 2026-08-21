<?php

namespace Tests\Feature;

use App\Http\Controllers\React\ReactAppController;
use Illuminate\Http\Request;
use Tests\TestCase;

class ReactProfileSpaRouteTest extends TestCase
{
    public function test_all_react_profile_sections_resolve_to_react_app_controller(): void
    {
        foreach (['overview', 'hunter-dna', 'projects', 'permissions', 'friends', 'social', 'twitch'] as $section) {
            $route = app('router')->getRoutes()->match(Request::create('/pages/user/'.$section, 'GET'));

            $this->assertSame(ReactAppController::class, $route->getActionName());
            $this->assertSame('react.profile.section', $route->getName());
        }
    }
}
