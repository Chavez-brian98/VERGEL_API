<?php

namespace Tests\Feature\Employees;

use App\Models\Employee;
use App\Services\EmployeeService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeServiceTest extends TestCase
{
    use RefreshDatabase;

    private EmployeeService $employeeService;

    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->roleId = DB::table('roles')->insertGetId([
            'role_name' => 'Administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employees')->insert([
            'full_name' => 'Empleado auditor',
            'role_id' => $this->roleId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->employeeService = app(EmployeeService::class);
    }

    public function test_it_creates_an_employee_with_audit_log(): void
    {
        $employee = $this->employeeService->create($this->employeeData());

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Carlos Mendoza',
            'role_id' => $this->roleId,
            'active' => true,
        ]);
        $this->assertDatabaseHas('audit_log', [
            'affected_table' => 'employees',
            'affected_record_id' => $employee->id,
            'action' => 'insert',
        ]);
    }

    public function test_it_searches_employees_by_text_and_active(): void
    {
        Employee::create($this->employeeData([
            'full_name' => 'Ana Activa',
            'active' => true,
        ]));
        Employee::create($this->employeeData([
            'full_name' => 'Ana Inactiva',
            'active' => false,
        ]));

        $result = $this->employeeService->search([
            'search' => 'ANA',
            'active' => 'true',
        ], 15);

        $this->assertSame(1, $result->total());
        $this->assertSame('Ana Activa', $result->first()->full_name);
    }

    public function test_it_filters_employees_by_role(): void
    {
        $otherRoleId = DB::table('roles')->insertGetId([
            'role_name' => 'Jardinero',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Employee::create($this->employeeData(['role_id' => $this->roleId]));
        Employee::create($this->employeeData(['role_id' => $otherRoleId]));

        $result = $this->employeeService->search(['role_id' => $otherRoleId], 15);

        $this->assertSame(1, $result->total());
        $this->assertSame($otherRoleId, $result->first()->role_id);
    }

    public function test_it_updates_an_employee_and_logs_audit(): void
    {
        $employee = Employee::create($this->employeeData());

        $updated = $this->employeeService->update($employee->id, [
            'full_name' => 'Carlos Actualizado',
            'phone' => '7111-1111',
        ]);

        $this->assertSame('Carlos Actualizado', $updated->full_name);
        $this->assertSame('7111-1111', $updated->phone);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $employee->id,
            'action' => 'update',
        ]);
    }

    public function test_it_toggles_employee_status(): void
    {
        $employee = Employee::create($this->employeeData());

        $updated = $this->employeeService->updateStatus($employee->id, false);

        $this->assertFalse($updated->active);
        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'active' => false,
        ]);
    }

    public function test_it_soft_deletes_an_employee(): void
    {
        $employee = Employee::create($this->employeeData());

        $this->employeeService->delete($employee->id);

        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertDatabaseHas('audit_log', [
            'affected_record_id' => $employee->id,
            'action' => 'delete',
        ]);
    }

    public function test_it_throws_when_getting_a_missing_employee(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $this->employeeService->getById(99999);
    }

    private function employeeData(array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Carlos Mendoza',
            'role_id' => $this->roleId,
            'phone' => '7000-0000',
        ], $overrides);
    }
}
