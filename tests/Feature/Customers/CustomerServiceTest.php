<?php

namespace Tests\Feature\Customers;

use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $customerService;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = DB::table('roles')->insertGetId([
            'role_name' => 'Administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employees')->insert([
            'full_name' => 'Empleado de pruebas',
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->customerService = app(CustomerService::class);
    }

    public function test_it_creates_a_customer_with_wallet_loyalty_points_and_audit_log(): void
    {
        $customer = $this->customerService->create($this->customerData());

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'legal_name' => 'Cliente de prueba',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('wallets', [
            'customer_id' => $customer->id,
            'cash_balance' => 0,
            'reserved_subscription_balance' => 0,
        ]);
        $this->assertDatabaseHas('loyalty_points', [
            'customer_id' => $customer->id,
            'points_balance' => 0,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'customers',
            'affected_record_id' => $customer->id,
            'action' => 'insert',
        ]);
    }

    public function test_it_creates_a_customer_without_audit_when_there_are_no_employees(): void
    {
        DB::table('employees')->delete();

        $customer = $this->customerService->create($this->customerData());

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
        $this->assertDatabaseCount('audit_log', 0);
    }

    public function test_it_searches_customers_by_text_and_status_with_pagination(): void
    {
        Customer::create($this->customerData([
            'legal_name' => 'Cliente activo',
            'status' => 'active',
        ]));
        Customer::create($this->customerData([
            'legal_name' => 'Cliente inactivo',
            'status' => 'inactive',
        ]));

        $result = $this->customerService->search([
            'search' => 'ACTIVO',
            'status' => 'active',
        ], 1);

        $this->assertSame(1, $result->total());
        $this->assertCount(1, $result->items());
        $this->assertSame('Cliente activo', $result->first()->legal_name);
    }

    public function test_it_updates_customer_status(): void
    {
        $customer = Customer::create($this->customerData());

        $updatedCustomer = $this->customerService->updateStatus($customer->id, 'inactive');

        $this->assertSame('inactive', $updatedCustomer->status);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $customer->id,
            'action' => 'update',
        ]);
    }

    public function test_it_soft_deletes_a_customer_and_registers_the_action(): void
    {
        $customer = Customer::create($this->customerData());

        $this->customerService->delete($customer->id);

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $customer->id,
            'action' => 'delete',
        ]);
        $this->assertCount(0, Customer::query()->whereKey($customer->id)->get());
    }

    private function customerData(array $overrides = []): array
    {
        return array_merge([
            'legal_name' => 'Cliente de prueba',
            'address' => 'Direccion de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-2222',
        ], $overrides);
    }
}
