<?php

namespace Tests\Feature\Plants;

use App\Models\Employee;
use App\Models\Plant;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlantPolicyAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $financialUser;

    private User $fieldUser;

    private User $userWithoutRole;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('role_name', 'Admin')->firstOrFail();
        $financialRole = Role::where('role_name', 'Contador')->firstOrFail();
        $fieldRole = Role::where('role_name', 'Colaborador')->firstOrFail();

        $adminEmp = Employee::create([
            'full_name' => 'Admin Test',
            'role_id' => $adminRole->id,
            'active' => true,
        ]);
        $this->adminUser = User::factory()->create([
            'employee_id' => $adminEmp->id,
        ]);

        $finEmp = Employee::create([
            'full_name' => 'Financiero Test',
            'role_id' => $financialRole->id,
            'active' => true,
        ]);
        $this->financialUser = User::factory()->create([
            'employee_id' => $finEmp->id,
        ]);

        $fieldEmp = Employee::create([
            'full_name' => 'Campo Test',
            'role_id' => $fieldRole->id,
            'active' => true,
        ]);
        $this->fieldUser = User::factory()->create([
            'employee_id' => $fieldEmp->id,
        ]);

        $this->userWithoutRole = User::factory()->create();
    }

    public function test_view_any_policy_permits_authorized_roles(): void
    {
        $this->assertTrue($this->adminUser->can('viewAny', Plant::class));
        $this->assertTrue($this->financialUser->can('viewAny', Plant::class));
        $this->assertTrue($this->fieldUser->can('viewAny', Plant::class));
        $this->assertFalse($this->userWithoutRole->can('viewAny', Plant::class));
    }

    public function test_view_policy_permits_authorized_roles(): void
    {
        $plant = Plant::create([
            'name' => 'Monstera Policy',
            'price' => 30.00,
            'active' => true,
        ]);

        $this->assertTrue($this->adminUser->can('view', $plant));
        $this->assertTrue($this->financialUser->can('view', $plant));
        $this->assertTrue($this->fieldUser->can('view', $plant));
        $this->assertFalse($this->userWithoutRole->can('view', $plant));
    }

    public function test_policy_permits_newly_created_dynamic_roles_to_view_only(): void
    {
        $newRole = Role::create([
            'role_name' => 'Diseñador Paisajista',
            'description' => 'Especialista en diseño botánico',
        ]);

        $newEmp = Employee::create([
            'full_name' => 'Paisajista Test',
            'role_id' => $newRole->id,
            'active' => true,
        ]);

        $newCollabUser = User::factory()->create([
            'employee_id' => $newEmp->id,
        ]);

        // Puede ver plantas dinámicamente al tener un rol activo existente
        $this->assertTrue($newCollabUser->can('viewAny', Plant::class));
        $this->assertTrue($newCollabUser->can('view', Plant::class));

        // Pero no puede gestionar (crear, editar, eliminar)
        $this->assertFalse($newCollabUser->can('create', Plant::class));
        $this->assertFalse($newCollabUser->can('update', Plant::class));
        $this->assertFalse($newCollabUser->can('delete', Plant::class));
    }

    public function test_policy_denies_inactive_employees(): void
    {
        $role = Role::where('role_name', 'Admin')->firstOrFail();
        $inactiveEmp = Employee::create([
            'full_name' => 'Inactivo Test',
            'role_id' => $role->id,
            'active' => false,
        ]);

        $inactiveUser = User::factory()->create([
            'employee_id' => $inactiveEmp->id,
        ]);

        $this->assertFalse($inactiveUser->can('viewAny', Plant::class));
        $this->assertFalse($inactiveUser->can('create', Plant::class));
    }

    public function test_create_and_update_policy_permits_only_admin(): void
    {
        $this->assertTrue($this->adminUser->can('create', Plant::class));
        $this->assertTrue($this->adminUser->can('update', Plant::class));
        $this->assertTrue($this->adminUser->can('delete', Plant::class));

        $this->assertFalse($this->financialUser->can('create', Plant::class));
        $this->assertFalse($this->financialUser->can('update', Plant::class));
        $this->assertFalse($this->financialUser->can('delete', Plant::class));

        $this->assertFalse($this->fieldUser->can('create', Plant::class));
        $this->assertFalse($this->fieldUser->can('update', Plant::class));
        $this->assertFalse($this->fieldUser->can('delete', Plant::class));

        $this->assertFalse($this->userWithoutRole->can('create', Plant::class));
    }

    public function test_index_request_blocks_user_without_role_with_403(): void
    {
        $response = $this->actingAs($this->userWithoutRole, 'sanctum')
            ->getJson('/api/v1/plants');

        $response->assertForbidden();
    }

    public function test_store_request_blocks_non_admin_roles_with_403(): void
    {
        $payload = [
            'name' => 'Nueva Planta Policy',
            'price' => 15.00,
        ];

        $this->actingAs($this->financialUser, 'sanctum')
            ->postJson('/api/v1/plants', $payload)
            ->assertForbidden();

        $this->actingAs($this->fieldUser, 'sanctum')
            ->postJson('/api/v1/plants', $payload)
            ->assertForbidden();
    }

    public function test_store_request_allows_admin(): void
    {
        $payload = [
            'name' => 'Planta Autorizada Policy Admin',
            'price' => 18.50,
        ];

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->postJson('/api/v1/plants', $payload);

        $response->assertCreated();
    }
}
