<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class EmployeeService
{
    /**
     * List employees with filters (search, active, role).
     */
    public function search(array $filters = [], int $limit = 15): LengthAwarePaginator
    {
        $query = Employee::query()->with('role');

        if (! empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$search}%"])
                    ->orWhere('dui', 'LIKE', "%{$search}%")
                    ->orWhere('phone', 'LIKE', "%{$search}%");
            });
        }

        if (array_key_exists('active', $filters) && $filters['active'] !== null && $filters['active'] !== '') {
            $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        return $query->orderBy('id', 'desc')->paginate($limit);
    }

    /**
     * Get a single employee by ID.
     */
    public function getById(int $id): Employee
    {
        return Employee::with('role')->findOrFail($id);
    }

    /**
     * Create a new employee.
     */
    public function create(array $data): Employee
    {
        return DB::transaction(function () use ($data) {
            $data['active'] = $data['active'] ?? true;
            $employee = Employee::create($data);

            $this->logAudit('insert', 'employees', $employee->id, null, $employee->toArray());

            return $employee->load('role');
        });
    }

    /**
     * Update an existing employee.
     */
    public function update(int $id, array $data): Employee
    {
        $employee = Employee::findOrFail($id);
        $oldData = $employee->toArray();

        return DB::transaction(function () use ($employee, $data, $oldData) {
            $employee->update($data);
            $this->logAudit('update', 'employees', $employee->id, $oldData, $employee->toArray());

            return $employee->load('role');
        });
    }

    /**
     * Activate or deactivate an employee.
     */
    public function updateStatus(int $id, bool $active): Employee
    {
        $employee = Employee::findOrFail($id);
        $oldData = $employee->toArray();

        return DB::transaction(function () use ($employee, $active, $oldData) {
            $employee->update(['active' => $active]);
            $this->logAudit('update', 'employees', $employee->id, $oldData, $employee->toArray());

            return $employee->load('role');
        });
    }

    /**
     * Soft delete an employee.
     */
    public function delete(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $oldData = $employee->toArray();

        DB::transaction(function () use ($employee, $oldData) {
            $employee->delete();
            $this->logAudit('delete', 'employees', $employee->id, $oldData, null);
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
