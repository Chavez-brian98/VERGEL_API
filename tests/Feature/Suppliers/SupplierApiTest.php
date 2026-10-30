<?php

namespace Tests\Feature\Suppliers;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $contadorUser;
    private User $colaboradorUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Admin')->firstOrFail();
        $contadorRole = Role::where('role_name', 'Contador')->firstOrFail();
        $colaboradorRole = Role::where('role_name', 'Colaborador')->firstOrFail();

        $this->adminUser = $this->createUserWithRole('Admin User', $adminRole);
        $this->contadorUser = $this->createUserWithRole('Contador User', $contadorRole);
        $this->colaboradorUser = $this->createUserWithRole('Colaborador User', $colaboradorRole);
    }

    private function createUserWithRole(string $fullName, Role $role, bool $active = true): User
    {
        $employee = Employee::create([
            'full_name' => $fullName,
            'role_id' => $role->id,
            'active' => $active,
        ]);

        return User::factory()->create([
            'employee_id' => $employee->id,
        ]);
    }

    public function test_guest_cannot_access_suppliers(): void
    {
        $this->getJson('/api/v1/suppliers')->assertUnauthorized();
        $this->postJson('/api/v1/suppliers', [])->assertUnauthorized();
    }

    public function test_colaborador_can_list_and_view_suppliers(): void
    {
        Supplier::factory()->count(3)->create();

        $response = $this->actingAs($this->colaboradorUser, 'sanctum')
            ->getJson('/api/v1/suppliers');

        $response->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta']);
    }

    public function test_colaborador_cannot_create_or_update_or_delete_suppliers(): void
    {
        $supplier = Supplier::factory()->create();

        $this->actingAs($this->colaboradorUser, 'sanctum')
            ->postJson('/api/v1/suppliers', [
                'name' => 'Proveedor Test',
                'phone' => '7123-4567',
            ])->assertForbidden();

        $this->actingAs($this->colaboradorUser, 'sanctum')
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Proveedor Editado',
                'phone' => '7123-4567',
            ])->assertForbidden();

        $this->actingAs($this->colaboradorUser, 'sanctum')
            ->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertForbidden();
    }

    public function test_admin_and_contador_can_create_supplier(): void
    {
        $payload = [
            'name' => 'Vivero Santa Tecla S.A.',
            'trade_name' => 'Santa Tecla Garden',
            'tax_id' => '0614-121290-101-2',
            'nrc' => '123456-7',
            'economic_activity' => 'Vivero y Plantas ornamentales',
            'phone' => '2288-9900',
            'email' => 'ventas@santatecla.com',
            'address' => 'Santa Tecla, La Libertad',
            'contact_name' => 'Roberto Gómez',
            'contact_phone' => '7890-1234',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->contadorUser, 'sanctum')
            ->postJson('/api/v1/suppliers', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Vivero Santa Tecla S.A.')
            ->assertJsonPath('data.taxId', '0614-121290-101-2');

        $this->assertDatabaseHas('suppliers', [
            'name' => 'Vivero Santa Tecla S.A.',
            'phone' => '2288-9900',
        ]);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'insert',
            'affected_table' => 'suppliers',
        ]);
    }

    public function test_admin_and_contador_can_update_supplier(): void
    {
        $supplier = Supplier::factory()->create([
            'name' => 'Vivero Antiguo',
            'phone' => '2222-1111',
        ]);

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/v1/suppliers/{$supplier->id}", [
                'name' => 'Vivero Renovado',
                'phone' => '2222-3333',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Vivero Renovado')
            ->assertJsonPath('data.phone', '2222-3333');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'name' => 'Vivero Renovado',
        ]);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'update',
            'affected_table' => 'suppliers',
            'affected_record_id' => $supplier->id,
        ]);
    }

    public function test_admin_and_contador_can_update_supplier_status(): void
    {
        $supplier = Supplier::factory()->create(['status' => 'active']);

        $response = $this->actingAs($this->contadorUser, 'sanctum')
            ->patchJson("/api/v1/suppliers/{$supplier->id}/status", [
                'status' => 'inactive',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->assertDatabaseHas('suppliers', [
            'id' => $supplier->id,
            'status' => 'inactive',
        ]);
    }

    public function test_only_admin_can_delete_supplier(): void
    {
        $supplier = Supplier::factory()->create();

        // Contador no puede eliminar
        $this->actingAs($this->contadorUser, 'sanctum')
            ->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertForbidden();

        // Admin sí puede eliminar
        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/suppliers/{$supplier->id}")
            ->assertOk();

        $this->assertSoftDeleted('suppliers', [
            'id' => $supplier->id,
        ]);

        $this->assertDatabaseHas('audit_log', [
            'action' => 'delete',
            'affected_table' => 'suppliers',
            'affected_record_id' => $supplier->id,
        ]);
    }

    public function test_supplier_filters_and_search(): void
    {
        Supplier::factory()->create([
            'name' => 'Abonos y Fertilizantes El Roble',
            'status' => 'active',
            'economic_activity' => 'Fertilizantes',
        ]);

        Supplier::factory()->create([
            'name' => 'Vivero Las Flores',
            'status' => 'inactive',
            'economic_activity' => 'Vivero',
        ]);

        // Filtrar por búsqueda 'El Roble'
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/suppliers?search=Roble');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Abonos y Fertilizantes El Roble');

        // Filtrar por status 'inactive'
        $responseInactive = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/v1/suppliers?status=inactive');

        $responseInactive->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Vivero Las Flores');
    }
}
