<?php

namespace Tests\Feature\Customers;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Role;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Casos de aceptación C01 - C06 de la matriz de permisos del módulo de Clientes.
 *
 * C01 Anónimo            -> 401 Unauthorized
 * C02 Perfil Consulta    -> 200 en consultas, 403 en escritura
 * C03 Perfil Gestión     -> acceso total
 * C04 No encontrado      -> 404 Not Found
 * C05 Datos inválidos    -> 422 Unprocessable Entity
 * C06 Bitácora           -> auditoría de acciones e intentos rechazados
 */
class CustomerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $consultaUser;

    private User $contadorUser;

    private User $userWithoutRole;

    private User $inactiveUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Admin')->firstOrFail();
        $contadorRole = Role::where('role_name', 'Contador')->firstOrFail();
        $consultaRole = Role::firstOrCreate(
            ['role_name' => 'Consulta'],
            ['description' => 'Perfil de solo consulta sobre el módulo de clientes.']
        );

        $this->adminUser = $this->createUserWithRole('Gestión de Clientes', $adminRole);
        $this->consultaUser = $this->createUserWithRole('Consulta de Clientes', $consultaRole);
        $this->contadorUser = $this->createUserWithRole('Contador de Clientes', $contadorRole);

        $this->userWithoutRole = User::factory()->create();

        $this->inactiveUser = $this->createUserWithRole('Empleado Inactivo', $adminRole, false);
    }

    /* ---------------------------------------------------------------------
     | C01 - Sin sesión (anónimo): todas las solicitudes devuelven 401
     | ------------------------------------------------------------------- */

    public function test_guests_cannot_list_customers(): void
    {
        $this->getJson('/api/v1/customers')->assertUnauthorized();
    }

    public function test_guests_cannot_view_a_customer(): void
    {
        $customer = $this->createCustomer();

        $this->getJson("/api/v1/customers/{$customer->id}")->assertUnauthorized();
    }

    public function test_guests_cannot_create_customers(): void
    {
        $this->postJson('/api/v1/customers', $this->customerPayload())->assertUnauthorized();
    }

    public function test_guests_cannot_update_customers(): void
    {
        $customer = $this->createCustomer();

        $this->putJson("/api/v1/customers/{$customer->id}", [
            'legal_name' => 'Cliente Modificado',
            'address' => 'Dirección modificada',
            'municipality' => 'San Salvador',
        ])->assertUnauthorized();
    }

    public function test_guests_cannot_change_customer_status(): void
    {
        $customer = $this->createCustomer();

        $this->patchJson("/api/v1/customers/{$customer->id}/status", [
            'status' => 'inactive',
        ])->assertUnauthorized();
    }

    public function test_guests_cannot_delete_customers(): void
    {
        $customer = $this->createCustomer();

        $this->deleteJson("/api/v1/customers/{$customer->id}")->assertUnauthorized();
    }

    public function test_guests_cannot_change_customer_plan(): void
    {
        $customer = $this->createCustomer();
        $plan = $this->createPlan();

        $this->postJson("/api/v1/customers/{$customer->id}/plan", [
            'plan_id' => $plan->id,
        ])->assertUnauthorized();
    }

    public function test_guests_cannot_view_customer_plan_history(): void
    {
        $customer = $this->createCustomer();

        $this->getJson("/api/v1/customers/{$customer->id}/plan-history")->assertUnauthorized();
    }

    /* ---------------------------------------------------------------------
     | C02 - Perfil "Consulta de Clientes": solo lectura
     | ------------------------------------------------------------------- */

    public function test_consulta_role_can_list_and_view_customers(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson('/api/v1/customers')
            ->assertOk()
            ->assertJsonFragment(['id' => $customer->id]);

        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertOk()
            ->assertJsonPath('data.legalName', $customer->legal_name);

        $this->actingAs($this->consultaUser, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}/plan-history")
            ->assertOk();
    }

    public function test_consulta_role_cannot_create_customers(): void
    {
        $this->actingAs($this->consultaUser, 'sanctum')
            ->postJson('/api/v1/customers', $this->customerPayload())
            ->assertForbidden()
            ->assertJsonPath('message', 'No tiene permisos para realizar esta acción.');

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_consulta_role_cannot_update_customers(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->putJson("/api/v1/customers/{$customer->id}", [
                'legal_name' => 'Cliente Modificado',
                'address' => 'Dirección modificada',
                'municipality' => 'San Salvador',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'legal_name' => $customer->legal_name,
        ]);
    }

    public function test_consulta_role_cannot_change_customer_status(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->patchJson("/api/v1/customers/{$customer->id}/status", ['status' => 'inactive'])
            ->assertForbidden();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'active',
        ]);
    }

    public function test_consulta_role_cannot_delete_customers(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertForbidden();

        $this->assertNotSoftDeleted('customers', ['id' => $customer->id]);
    }

    public function test_consulta_role_cannot_change_customer_plan(): void
    {
        $customer = $this->createCustomer();
        $plan = $this->createPlan();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->postJson("/api/v1/customers/{$customer->id}/plan", ['plan_id' => $plan->id])
            ->assertForbidden();

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'current_plan_id' => null,
        ]);
    }

    public function test_any_active_role_can_consult_customers_but_not_manage_them(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->contadorUser, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertOk();

        $this->actingAs($this->contadorUser, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertForbidden();
    }

    public function test_user_without_role_or_with_inactive_employee_is_forbidden(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->userWithoutRole, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertForbidden();

        $this->actingAs($this->inactiveUser, 'sanctum')
            ->getJson("/api/v1/customers/{$customer->id}")
            ->assertForbidden();

        $this->actingAs($this->inactiveUser, 'sanctum')
            ->postJson('/api/v1/customers', $this->customerPayload())
            ->assertForbidden();
    }

    /* ---------------------------------------------------------------------
     | C03 - Perfil "Gestión de Clientes": acceso total
     | ------------------------------------------------------------------- */

    public function test_admin_can_create_a_customer(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/customers', $this->customerPayload());

        $response->assertCreated()
            ->assertJsonFragment(['legalName' => 'Cliente de prueba']);

        $this->assertDatabaseHas('customers', ['legal_name' => 'Cliente de prueba']);
    }

    public function test_admin_can_update_a_customer(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/v1/customers/{$customer->id}", [
                'legal_name' => 'Cliente actualizado',
                'address' => 'Dirección actualizada',
                'municipality' => 'Santa Tecla',
            ])
            ->assertOk()
            ->assertJsonFragment(['legalName' => 'Cliente actualizado']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'legal_name' => 'Cliente actualizado',
        ]);
    }

    public function test_admin_can_change_customer_status(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/v1/customers/{$customer->id}/status", ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonFragment(['status' => 'inactive']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'inactive',
        ]);
    }

    public function test_admin_can_change_customer_plan(): void
    {
        $customer = $this->createCustomer();
        $plan = $this->createPlan();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/customers/{$customer->id}/plan", ['plan_id' => $plan->id])
            ->assertOk()
            ->assertJsonPath('currentPlan.id', $plan->id);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'current_plan_id' => $plan->id,
        ]);
    }

    public function test_admin_can_delete_a_customer(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertNoContent();

        $this->assertSoftDeleted('customers', ['id' => $customer->id]);
    }

    /* ---------------------------------------------------------------------
     | C04 - Recursos inexistentes devuelven 404
     | ------------------------------------------------------------------- */

    public function test_it_returns_404_when_the_customer_does_not_exist(): void
    {
        $missingId = 9999;

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/customers/{$missingId}")
            ->assertNotFound();

        $this->actingAs($this->adminUser, 'sanctum')
            ->putJson("/api/v1/customers/{$missingId}", [
                'legal_name' => 'Cliente inexistente',
                'address' => 'Dirección',
                'municipality' => 'San Salvador',
            ])
            ->assertNotFound();

        $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/v1/customers/{$missingId}/status", ['status' => 'inactive'])
            ->assertNotFound();

        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/customers/{$missingId}")
            ->assertNotFound();

        $this->actingAs($this->adminUser, 'sanctum')
            ->getJson("/api/v1/customers/{$missingId}/plan-history")
            ->assertNotFound();
    }

    /* ---------------------------------------------------------------------
     | C05 - Datos inválidos devuelven 422
     | ------------------------------------------------------------------- */

    public function test_it_returns_422_when_the_payload_is_invalid(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/customers', [
                'legal_name' => '',
                'address' => '',
                'municipality' => '',
                'phone' => '2222-1111-3333-4444-5555',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['legal_name', 'address', 'municipality', 'phone']);

        $this->assertDatabaseCount('customers', 0);
    }

    public function test_it_returns_422_when_the_status_is_invalid(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/v1/customers/{$customer->id}/status", ['status' => 'pendiente'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'status' => 'active',
        ]);
    }

    public function test_it_returns_422_when_the_plan_does_not_exist(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson("/api/v1/customers/{$customer->id}/plan", ['plan_id' => 9999])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['plan_id']);
    }

    /* ---------------------------------------------------------------------
     | C06 - Bitácora (audit_log)
     | ------------------------------------------------------------------- */

    public function test_it_registers_successful_actions_in_the_audit_log(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/customers', $this->customerPayload())
            ->assertCreated();

        $createdId = Customer::where('legal_name', 'Cliente de prueba')->value('id');

        $this->assertNotNull($createdId);
        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'customers',
            'affected_record_id' => $createdId,
            'action' => 'insert',
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->patchJson("/api/v1/customers/{$customer->id}/status", ['status' => 'inactive'])
            ->assertOk();

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'customers',
            'affected_record_id' => $customer->id,
            'action' => 'update',
        ]);

        $this->actingAs($this->adminUser, 'sanctum')
            ->deleteJson("/api/v1/customers/{$customer->id}")
            ->assertNoContent();

        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'customers',
            'affected_record_id' => $customer->id,
            'action' => 'delete',
        ]);
    }

    public function test_it_registers_rejected_attempts_in_the_audit_log(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->postJson('/api/v1/customers', $this->customerPayload())
            ->assertForbidden();

        $this->assertDatabaseHas('audit_log', [
            'employee_id' => $this->consultaUser->employee_id,
            'affected_table' => 'customers',
            'action' => 'denied',
        ]);

        $entry = AuditLog::where('action', 'denied')->latest('id')->first();

        $this->assertNotNull($entry);
        $this->assertSame('denied', $entry->new_data['result']);
        $this->assertSame('create', $entry->new_data['ability']);
        $this->assertSame('customers', $entry->new_data['resource']);
        $this->assertSame($this->consultaUser->email, $entry->new_data['user_email']);
        $this->assertSame('Consulta', $entry->new_data['role']);
        $this->assertArrayNotHasKey('password', $entry->new_data);
        $this->assertArrayNotHasKey('token', $entry->new_data);
        $this->assertNotNull($entry->created_at);
    }

    public function test_it_registers_rejected_update_attempts_with_the_record_id(): void
    {
        $customer = $this->createCustomer();

        $this->actingAs($this->consultaUser, 'sanctum')
            ->patchJson("/api/v1/customers/{$customer->id}/status", ['status' => 'inactive'])
            ->assertForbidden();

        $this->assertDatabaseHas('audit_log', [
            'employee_id' => $this->consultaUser->employee_id,
            'affected_table' => 'customers',
            'affected_record_id' => $customer->id,
            'action' => 'denied',
        ]);
    }

    /* ---------------------------------------------------------------------
     | Policy (unidad de permisos)
     | ------------------------------------------------------------------- */

    public function test_policy_grants_read_to_any_active_role_and_writes_only_to_admin(): void
    {
        $customer = $this->createCustomer();

        $this->assertTrue($this->adminUser->can('viewAny', Customer::class));
        $this->assertTrue($this->adminUser->can('view', $customer));
        $this->assertTrue($this->adminUser->can('create', Customer::class));
        $this->assertTrue($this->adminUser->can('update', $customer));
        $this->assertTrue($this->adminUser->can('delete', $customer));

        $this->assertTrue($this->consultaUser->can('viewAny', Customer::class));
        $this->assertTrue($this->consultaUser->can('view', $customer));
        $this->assertFalse($this->consultaUser->can('create', Customer::class));
        $this->assertFalse($this->consultaUser->can('update', $customer));
        $this->assertFalse($this->consultaUser->can('delete', $customer));

        $this->assertFalse($this->userWithoutRole->can('viewAny', Customer::class));
        $this->assertFalse($this->userWithoutRole->can('view', $customer));
        $this->assertFalse($this->inactiveUser->can('viewAny', Customer::class));
        $this->assertFalse($this->inactiveUser->can('create', Customer::class));
    }

    /* ---------------------------------------------------------------------
     | Helpers
     | ------------------------------------------------------------------- */

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

    private function createCustomer(): Customer
    {
        return Customer::create([
            'legal_name' => 'Cliente original',
            'address' => 'Dirección de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-0000',
            'status' => 'active',
        ]);
    }

    private function createPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'plan_code' => 'PLAN-'.uniqid(),
            'plan_name' => 'Plan de prueba',
            'frequency_value' => 30,
            'frequency_unit' => 'days',
            'visit_count' => 1,
            'active' => true,
        ]);
    }

    private function customerPayload(): array
    {
        return [
            'legal_name' => 'Cliente de prueba',
            'address' => 'Dirección de prueba',
            'municipality' => 'San Salvador',
            'phone' => '2222-1111',
        ];
    }
}
