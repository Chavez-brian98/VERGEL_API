<?php

namespace Tests\Feature\Employees;

use App\Http\Controllers\Api\V1\EmployeeController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_list_employees(): void
    {
        $this->getJson('/api/v1/employees')->assertUnauthorized();
    }

    public function test_guests_cannot_create_employees(): void
    {
        $this->postJson('/api/v1/employees', [])->assertUnauthorized();
    }

    public function test_guests_cannot_delete_employees(): void
    {
        $this->deleteJson('/api/v1/employees/1')->assertUnauthorized();
    }

    public function test_employees_routes_are_registered(): void
    {
        $this->assertTrue(
            collect(app('router')->getRoutes()->getRoutes())
                ->contains(fn ($route) => $route->getActionName() === EmployeeController::class.'@index')
        );
    }
}
