<?php

namespace Tests\Feature\Roles;

use App\Models\Employee;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RoleServiceTest extends TestCase
{
    use RefreshDatabase;

    private RoleService $roleService;

    private Role $seedRole;

    protected function setUp(): void
    {
        parent::setUp();

        $roleId = DB::table('roles')->insertGetId([
            'role_name' => 'Administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employees')->insert([
            'full_name' => 'Empleado auditor',
            'role_id' => $roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seedRole = Role::findOrFail($roleId);
        $this->roleService = app(RoleService::class);
    }

    public function test_it_creates_a_role_with_audit_log(): void
    {
        $role = $this->roleService->create([
            'role_name' => 'Supervisor',
            'description' => 'Supervisa operaciones',
        ]);

        $this->assertDatabaseHas('roles', [
            'id' => $role->id,
            'role_name' => 'Supervisor',
        ]);
        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'roles',
            'affected_record_id' => $role->id,
            'action' => 'insert',
        ]);
    }

    public function test_it_searches_roles_by_name_or_description(): void
    {
        Role::create(['role_name' => 'Supervisor', 'description' => 'Supervisa operaciones']);
        Role::create(['role_name' => 'Jardinero', 'description' => 'Mantenimiento de áreas verdes']);

        $result = $this->roleService->search(['search' => 'SUPERVISOR'], 15);

        $this->assertSame(1, $result->total());
        $this->assertSame('Supervisor', $result->first()->role_name);
    }

    public function test_it_updates_a_role_and_logs_audit(): void
    {
        $role = Role::create(['role_name' => 'Supervisor', 'description' => 'Supervisa operaciones']);

        $updated = $this->roleService->update($role->id, [
            'role_name' => 'Supervisor General',
            'description' => 'Supervisa todas las operaciones',
        ]);

        $this->assertSame('Supervisor General', $updated->role_name);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $role->id,
            'action' => 'update',
        ]);
    }

    public function test_it_deletes_a_role_without_employees(): void
    {
        $role = Role::create(['role_name' => 'Temporal', 'description' => 'Rol temporal']);

        $this->roleService->delete($role->id);

        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $role->id,
            'action' => 'delete',
        ]);
    }

    public function test_it_blocks_deletion_when_there_are_employees(): void
    {
        Employee::create([
            'full_name' => 'Empleado del rol',
            'role_id' => $this->seedRole->id,
        ]);

        $this->expectException(ValidationException::class);

        $this->roleService->delete($this->seedRole->id);
    }

    public function test_it_throws_when_getting_a_missing_role(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->roleService->getById(99999);
    }

    public function test_it_throws_when_updating_a_missing_role(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->roleService->update(99999, ['role_name' => 'X']);
    }

    public function test_it_throws_when_deleting_a_missing_role(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->roleService->delete(99999);
    }
}
