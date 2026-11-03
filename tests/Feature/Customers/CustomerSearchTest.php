<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerSearchTest extends TestCase
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

    public function test_it_returns_all_customers_without_filters(): void
    {
        for ($customerNumber = 1; $customerNumber <= 21; $customerNumber++) {
            Customer::create([
                'legal_name' => "Cliente {$customerNumber}",
                'address' => 'Direccion de prueba',
                'municipality' => 'San Salvador',
                'phone' => "2222-22{$customerNumber}",
            ]);
        }

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/customers?limit=21');

        $response->assertOk()
            ->assertJsonCount(21, 'data');
    }

    public function test_it_filters_customers_when_a_name_is_provided(): void
    {
        Customer::create([
            'legal_name' => 'Cliente Encontrado',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2200',
        ]);
        Customer::create([
            'legal_name' => 'Otro Cliente',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2201',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/customers?search=Encontrado');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['legalName' => 'Cliente Encontrado'])
            ->assertJsonMissing(['legalName' => 'Otro Cliente']);
    }

    public function test_it_filters_customers_by_creation_date_range(): void
    {
        $customerInRange = Customer::create([
            'legal_name' => 'Cliente en rango',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2200',
        ]);
        $customerOutsideRange = Customer::create([
            'legal_name' => 'Cliente fuera de rango',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2201',
        ]);

        $customerInRange->forceFill(['created_at' => '2026-09-15 23:59:59'])->save();
        $customerOutsideRange->forceFill(['created_at' => '2026-09-16 00:00:00'])->save();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/customers?desde=2026-09-15&hasta=2026-09-15');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['legalName' => 'Cliente en rango'])
            ->assertJsonMissing(['legalName' => 'Cliente fuera de rango']);
    }
}
