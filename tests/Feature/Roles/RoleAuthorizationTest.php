<?php

namespace Tests\Feature\Roles;

use App\Http\Controllers\Api\V1\RoleController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_roles(): void
    {
        $this->getJson('/api/v1/roles')->assertUnauthorized();
    }

    public function test_guests_cannot_create_roles(): void
    {
        $this->postJson('/api/v1/roles', [])->assertUnauthorized();
    }

    public function test_guests_cannot_delete_roles(): void
    {
        $this->deleteJson('/api/v1/roles/1')->assertUnauthorized();
    }

    public function test_roles_routes_are_registered(): void
    {
        $this->assertTrue(
            collect(app('router')->getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->getActionName() === RoleController::class.'@index')
        );
    }
}
