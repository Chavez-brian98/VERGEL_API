<?php

namespace Tests\Feature\Roles;

use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RoleApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = DB::table('roles')->insertGetId([
            'role_name' => 'Administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $employee = Employee::create([
            'full_name' => 'Admin Test',
            'role_id' => $roleId,
            'active' => true,
        ]);

        $this->actingAs(User::factory()->create(['employee_id' => $employee->id]), 'sanctum');
    }

    public function test_it_lists_roles_with_employees_count(): void
    {
        $role = Role::create(['role_name' => 'Colaborador', 'description' => 'Colaboración']);
        Employee::create(['full_name' => 'Empleado Uno', 'role_id' => $role->id, 'active' => true]);

        $response = $this->getJson('/api/v1/roles');

        $response->assertOk()
            ->assertJsonFragment([
                'roleName' => 'Colaborador',
                'employeesCount' => 1,
            ]);
    }

    public function test_it_filters_roles_by_search(): void
    {
        Role::create(['role_name' => 'Jardinero', 'description' => 'Áreas verdes']);

        $response = $this->getJson('/api/v1/roles?search=jardinero');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['roleName' => 'Jardinero']);
    }

    public function test_it_creates_a_role(): void
    {
        $response = $this->postJson('/api/v1/roles', [
            'role_name' => 'Supervisor',
            'description' => 'Supervisa operaciones',
        ]);

        $response->assertCreated()
            ->assertJsonFragment([
                'roleName' => 'Supervisor',
                'description' => 'Supervisa operaciones',
                'employeesCount' => 0,
            ]);

        $this->assertDatabaseHas('roles', ['role_name' => 'Supervisor']);
    }

    public function test_it_validates_create_payload(): void
    {
        $response = $this->postJson('/api/v1/roles', [
            'role_name' => '',
            'description' => str_repeat('a', 300),
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role_name', 'description']);
    }

    public function test_it_rejects_duplicate_role_name(): void
    {
        $response = $this->postJson('/api/v1/roles', ['role_name' => 'Administrador']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role_name']);
    }

    public function test_it_shows_a_role(): void
    {
        $role = Role::create(['role_name' => 'Tecnico', 'description' => 'Soporte técnico']);

        $response = $this->getJson("/api/v1/roles/{$role->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $role->id,
                'roleName' => 'Tecnico',
                'employeesCount' => 0,
            ]);
    }

    public function test_it_updates_a_role(): void
    {
        $role = Role::create(['role_name' => 'Contador', 'description' => 'Contabilidad']);

        $response = $this->putJson("/api/v1/roles/{$role->id}", [
            'role_name' => 'Contador General',
            'description' => 'Acceso total al sistema',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $role->id,
                'roleName' => 'Contador General',
                'description' => 'Acceso total al sistema',
            ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'role_name' => 'Contador General',
        ]);
    }

    public function test_it_deletes_a_role(): void
    {
        $role = Role::create(['role_name' => 'Temporal', 'description' => 'Rol temporal']);

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_it_blocks_deleting_a_role_with_employees(): void
    {
        $role = Role::create(['role_name' => 'ConEmpleados' . uniqid()]);
        Employee::create(['full_name' => 'Empleado Uno', 'role_id' => $role->id, 'active' => true]);

        $response = $this->deleteJson("/api/v1/roles/{$role->id}");

        $response->assertStatus(409)
            ->assertJsonFragment(['codigo' => 409])
            ->assertJsonValidationErrors(['role']);

        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    public function test_it_paginates_roles_using_the_limit_parameter(): void
    {
        DB::table('roles')->insert([
            ['role_name' => 'Rol 1', 'created_at' => now(), 'updated_at' => now()],
            ['role_name' => 'Rol 2', 'created_at' => now(), 'updated_at' => now()],
            ['role_name' => 'Rol 3', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = $this->getJson('/api/v1/roles?limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 4);
    }

    public function test_it_returns_404_for_a_missing_role(): void
    {
        $this->getJson('/api/v1/roles/99999')->assertNotFound();
        $this->putJson('/api/v1/roles/99999', ['role_name' => 'X'])->assertNotFound();
        $this->deleteJson('/api/v1/roles/99999')->assertNotFound();
    }

    public function test_missing_role_error_includes_codigo(): void
    {
        $this->getJson('/api/v1/roles/99999')
            ->assertNotFound()
            ->assertJsonFragment(['codigo' => 404]);
    }
}
