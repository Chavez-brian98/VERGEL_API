<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Supplier;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    /**
     * Listar y filtrar proveedores con paginación.
     */
    public function search(array $filters = [], int $limit = 15): LengthAwarePaginator
    {
        $query = Supplier::query();

        if (! empty($filters['search'])) {
            $search = mb_strtolower(trim($filters['search']));
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(trade_name) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(email) LIKE ?', ["%{$search}%"])
                    ->orWhere('phone', 'LIKE', "%{$search}%")
                    ->orWhere('tax_id', 'LIKE', "%{$search}%")
                    ->orWhere('nrc', 'LIKE', "%{$search}%")
                    ->orWhereRaw('LOWER(contact_name) LIKE ?', ["%{$search}%"]);
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['economic_activity'])) {
            $activity = mb_strtolower(trim($filters['economic_activity']));
            $query->whereRaw('LOWER(economic_activity) LIKE ?', ["%{$activity}%"]);
        }

        if (! empty($filters['phone'])) {
            $query->where('phone', 'LIKE', '%'.trim($filters['phone']).'%');
        }

        if (! empty($filters['desde'])) {
            $query->whereDate('created_at', '>=', $filters['desde']);
        }

        if (! empty($filters['hasta'])) {
            $query->whereDate('created_at', '<=', $filters['hasta']);
        }

        $allowedSorts = ['id', 'name', 'trade_name', 'status', 'created_at'];
        $sortBy = in_array($filters['sort_by'] ?? null, $allowedSorts, true) ? $filters['sort_by'] : 'id';
        $sortDirection = strtolower($filters['sort_direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($limit);
    }

    /**
     * Obtener proveedor por ID.
     */
    public function getById(int $id): Supplier
    {
        return Supplier::findOrFail($id);
    }

    /**
     * Crear un nuevo proveedor.
     */
    public function create(array $data): Supplier
    {
        return DB::transaction(function () use ($data) {
            $data['status'] = $data['status'] ?? 'active';
            $supplier = Supplier::create($data);

            $this->logAudit('insert', 'suppliers', $supplier->id, [], $supplier->toArray());

            return $supplier;
        });
    }

    /**
     * Actualizar proveedor existente.
     */
    public function update(int $id, array $data): Supplier
    {
        return DB::transaction(function () use ($id, $data) {
            $supplier = Supplier::findOrFail($id);
            $oldValues = $supplier->toArray();

            $supplier->update($data);

            $this->logAudit('update', 'suppliers', $supplier->id, $oldValues, $supplier->fresh()->toArray());

            return $supplier;
        });
    }

    /**
     * Actualizar estado del proveedor.
     */
    public function updateStatus(int $id, string $status): Supplier
    {
        return DB::transaction(function () use ($id, $status) {
            $supplier = Supplier::findOrFail($id);
            $oldValues = ['status' => $supplier->status];

            $supplier->status = $status;
            $supplier->save();

            $this->logAudit('update', 'suppliers', $supplier->id, $oldValues, ['status' => $status]);

            return $supplier;
        });
    }

    /**
     * Eliminar proveedor (borrado lógico).
     */
    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $supplier = Supplier::findOrFail($id);
            $oldValues = $supplier->toArray();

            $supplier->delete();

            $this->logAudit('delete', 'suppliers', $supplier->id, $oldValues, []);
        });
    }

    /**
     * Restaurar proveedor eliminado.
     */
    public function restore(int $id): Supplier
    {
        return DB::transaction(function () use ($id) {
            $supplier = Supplier::withTrashed()->findOrFail($id);
            $supplier->restore();

            $this->logAudit('restore', 'suppliers', $supplier->id, [], $supplier->toArray());

            return $supplier;
        });
    }

    /**
     * Registrar en la tabla audit_log con el ID de empleado autenticado.
     */
    private function logAudit(string $action, string $tableName, int $recordId, ?array $oldValues, ?array $newValues): void
    {
        $employeeId = request()->user()?->employee_id ?? auth()->user()?->employee_id;

        if (! $employeeId) {
            return;
        }

        AuditLog::create([
            'employee_id' => $employeeId,
            'affected_table' => $tableName,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => ! empty($oldValues) ? $oldValues : null,
            'new_data' => ! empty($newValues) ? $newValues : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
