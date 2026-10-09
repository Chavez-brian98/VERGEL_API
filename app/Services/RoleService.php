<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RoleService
{
    /**
     * List roles with filters (search).
     */
    public function search(array $filters = [], int $limit = 15): LengthAwarePaginator
    {
        $query = Role::query()->withCount('employees');

        if (! empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(role_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(description) LIKE ?', ["%{$search}%"]);
            });
        }

        return $query->orderBy('id', 'desc')->paginate($limit);
    }

    /**
     * Get a single role by ID.
     */
    public function getById(int $id): Role
    {
        return Role::withCount('employees')->findOrFail($id);
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        return DB::transaction(function () use ($data) {
            $role = Role::create($data);

            $this->logAudit('insert', 'roles', $role->id, null, $role->toArray());

            return $role->loadCount('employees');
        });
    }

    /**
     * Update an existing role.
     */
    public function update(int $id, array $data): Role
    {
        $role = Role::findOrFail($id);
        $oldData = $role->toArray();

        return DB::transaction(function () use ($role, $data, $oldData) {
            $role->update($data);
            $this->logAudit('update', 'roles', $role->id, $oldData, $role->toArray());

            return $role->loadCount('employees');
        });
    }

    /**
     * Delete a role, blocking it when there are associated employees.
     */
    public function delete(int $id): void
    {
        $role = Role::findOrFail($id);

        $employees = $role->employees()->count();

        if ($employees > 0) {
            throw ValidationException::withMessages([
                'role' => ["No se puede eliminar el rol porque tiene {$employees} empleado(s) asociado(s). Reasigne o elimine dichos empleados primero."],
            ]);
        }

        $oldData = $role->toArray();

        DB::transaction(function () use ($role, $oldData) {
            $role->delete();
            $this->logAudit('delete', 'roles', $role->id, $oldData, null);
        });
    }

    /**
     * Audit log helper.
     */
    private function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData): void
    {
        $employee = Employee::first();
        if (! $employee) {
            return;
        }

        AuditLog::create([
            'employee_id' => auth()->id() ?? $employee->id,
            'affected_table' => $table,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
        ]);
    }
}
