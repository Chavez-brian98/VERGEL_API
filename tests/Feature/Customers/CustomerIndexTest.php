<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerIndexTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Admin')->firstOrFail();

        $adminEmployee = Employee::create([
            'full_name' => 'Admin Test',
            'role_id' => $adminRole->id,
            'active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'employee_id' => $adminEmployee->id,
        ]);
    }

    public function test_it_returns_all_non_deleted_customers(): void
    {
        $customer = Customer::create([
            'legal_name' => 'Cliente de prueba',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2222',
        ]);

        $deletedCustomer = Customer::create([
            'legal_name' => 'Cliente eliminado',
            'address' => 'Direccion eliminada',
            'municipality' => 'San Salvador',
            'phone' => '2222-2223',
        ]);
        $deletedCustomer->delete();

        $response = $this->actingAs($this->adminUser, 'sanctum')->getJson('/api/v1/customers');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $customer->id,
                'legalName' => 'Cliente de prueba',
            ])
            ->assertJsonMissing([
                'legalName' => 'Cliente eliminado',
            ]);
    }
}
