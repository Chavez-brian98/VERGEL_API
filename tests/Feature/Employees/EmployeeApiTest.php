<?php

namespace Tests\Feature\Employees;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    private int $roleId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create(), 'sanctum');

        $this->roleId = DB::table('roles')->insertGetId([
            'role_name' => 'Administrador',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_it_lists_employees_with_role_name(): void
    {
        Employee::create($this->employeeData());
        Employee::create($this->employeeData([
            'full_name' => 'Empleado Inactivo',
            'phone' => '7222-2222',
            'active' => false,
        ]));

        $response = $this->getJson('/api/v1/employees');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'fullName' => 'Carlos Mendoza',
                'roleName' => 'Administrador',
            ]);
    }

    public function test_it_filters_employees_by_active_status(): void
    {
        Employee::create($this->employeeData());
        Employee::create($this->employeeData([
            'full_name' => 'Empleado Inactivo',
            'phone' => '7222-2222',
            'active' => false,
        ]));

        $response = $this->getJson('/api/v1/employees?active=0');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['fullName' => 'Empleado Inactivo', 'active' => false]);
    }

    public function test_it_creates_an_employee(): void
    {
        $response = $this->postJson('/api/v1/employees', $this->employeeData([
            'dui' => '01234567-8',
            'birth_date' => '1990-01-15',
            'hire_date' => '2024-03-01',
        ]));

        $response->assertCreated()
            ->assertJsonFragment([
                'fullName' => 'Carlos Mendoza',
                'dui' => '01234567-8',
                'birthDate' => '1990-01-15',
                'active' => true,
            ])
            ->assertJsonPath('data.role.roleName', 'Administrador');

        $this->assertDatabaseHas('employees', [
            'full_name' => 'Carlos Mendoza',
            'role_id' => $this->roleId,
        ]);
    }

    public function test_it_validates_create_payload(): void
    {
        $response = $this->postJson('/api/v1/employees', [
            'full_name' => '',
            'role_id' => 99999,
            'birth_date' => 'not-a-date',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['full_name', 'role_id', 'birth_date']);
    }

    public function test_it_rejects_duplicate_dui(): void
    {
        Employee::create($this->employeeData(['dui' => '01234567-8']));

        $response = $this->postJson('/api/v1/employees', $this->employeeData(['dui' => '01234567-8']));

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['dui']);
    }

    public function test_it_shows_an_employee(): void
    {
        $employee = Employee::create($this->employeeData());

        $response = $this->getJson("/api/v1/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $employee->id,
                'fullName' => 'Carlos Mendoza',
                'active' => true,
            ]);
    }

    public function test_it_updates_an_employee(): void
    {
        $employee = Employee::create($this->employeeData());

        $response = $this->putJson("/api/v1/employees/{$employee->id}", [
            'full_name' => 'Carlos Actualizado',
            'phone' => '7111-1111',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $employee->id,
                'fullName' => 'Carlos Actualizado',
                'phone' => '7111-1111',
            ]);

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'full_name' => 'Carlos Actualizado',
        ]);
    }

    public function test_it_updates_employee_status(): void
    {
        $employee = Employee::create($this->employeeData());

        $response = $this->patchJson("/api/v1/employees/{$employee->id}/status", [
            'active' => false,
        ]);

        $response->assertOk()->assertJsonFragment(['active' => false]);
    }

    public function test_it_validates_status_payload(): void
    {
        $employee = Employee::create($this->employeeData());

        $response = $this->patchJson("/api/v1/employees/{$employee->id}/status", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['active']);
    }

    public function test_it_soft_deletes_an_employee(): void
    {
        $employee = Employee::create($this->employeeData());

        $response = $this->deleteJson("/api/v1/employees/{$employee->id}");

        $response->assertNoContent();
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
    }

    public function test_it_paginates_employees_using_the_limit_parameter(): void
    {
        Employee::create($this->employeeData(['phone' => '7000-0001']));
        Employee::create($this->employeeData(['phone' => '7000-0002']));
        Employee::create($this->employeeData(['phone' => '7000-0003']));

        $response = $this->getJson('/api/v1/employees?limit=2');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_it_returns_404_for_a_missing_employee(): void
    {
        $this->getJson('/api/v1/employees/99999')->assertNotFound();
        $this->putJson('/api/v1/employees/99999', ['full_name' => 'X'])->assertNotFound();
        $this->patchJson('/api/v1/employees/99999/status', ['active' => true])->assertNotFound();
        $this->deleteJson('/api/v1/employees/99999')->assertNotFound();
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
