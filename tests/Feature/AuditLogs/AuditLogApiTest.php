<?php

namespace Tests\Feature\AuditLogs;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogApiTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;

    private User $auditorUser;

    private Employee $adminEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::create(['role_name' => 'Administrador']);
        $auditorRole = Role::create(['role_name' => 'Auditor']);

        $this->adminEmployee = Employee::create([
            'full_name' => 'Admin Test',
            'role_id' => $adminRole->id,
            'active' => true,
        ]);

        $this->adminUser = User::factory()->create([
            'employee_id' => $this->adminEmployee->id,
            'email' => 'admin@test.com',
        ]);

        $auditorEmployee = Employee::create([
            'full_name' => 'Auditor Test',
            'role_id' => $auditorRole->id,
            'active' => true,
        ]);

        $this->auditorUser = User::factory()->create([
            'employee_id' => $auditorEmployee->id,
            'email' => 'auditor@test.com',
        ]);
    }

    public function test_unauthenticated_user_cannot_access_audit_logs(): void
    {
        $response = $this->getJson('/api/v1/audit-logs');

        $response->assertUnauthorized();
    }

    public function test_non_admin_user_is_forbidden_from_viewing_audit_logs(): void
    {
        $this->actingAs($this->auditorUser, 'sanctum');

        $response = $this->getJson('/api/v1/audit-logs');

        $response->assertForbidden();
    }

    public function test_admin_can_list_audit_logs_with_pagination(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        AuditLog::create([
            'employee_id' => $this->adminEmployee->id,
            'affected_table' => 'plants',
            'affected_record_id' => 1,
            'action' => 'insert',
            'old_data' => null,
            'new_data' => ['name' => 'Monstera'],
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::create([
            'employee_id' => $this->adminEmployee->id,
            'affected_table' => 'plants',
            'affected_record_id' => 1,
            'action' => 'update',
            'old_data' => ['name' => 'Monstera'],
            'new_data' => ['name' => 'Monstera Variegata'],
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->getJson('/api/v1/audit-logs');

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'action',
                        'affectedTable',
                        'affectedRecordId',
                        'oldData',
                        'newData',
                        'ipAddress',
                        'createdAt',
                        'actor' => [
                            'employeeId',
                            'fullName',
                            'role',
                            'userId',
                            'email',
                        ],
                    ],
                ],
                'links' => ['first', 'last', 'prev', 'next'],
                'meta' => ['current_page', 'from', 'last_page', 'per_page', 'to', 'total'],
            ]);
    }

    public function test_it_filters_audit_logs_by_action_and_table(): void
    {
        $this->actingAs($this->adminUser, 'sanctum');

        AuditLog::create([
            'employee_id' => $this->adminEmployee->id,
            'affected_table' => 'plants',
            'affected_record_id' => 1,
            'action' => 'insert',
            'old_data' => null,
            'new_data' => ['name' => 'Planta A'],
        ]);

        AuditLog::create([
            'employee_id' => $this->adminEmployee->id,
            'affected_table' => 'customers',
            'affected_record_id' => 2,
            'action' => 'delete',
            'old_data' => ['name' => 'Cliente B'],
            'new_data' => null,
        ]);

        $response = $this->getJson('/api/v1/audit-logs?action=delete&table_name=customers');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'delete')
            ->assertJsonPath('data.0.affectedTable', 'customers');
    }
}
