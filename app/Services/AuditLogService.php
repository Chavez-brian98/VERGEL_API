<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AuditLogService
{
    /**
     * Listar y filtrar registros de bitácoras de auditoría con soporte de paginación.
     *
     * @param  array<string, mixed>  $filters
     */
    public function listLogs(array $filters): LengthAwarePaginator
    {
        $limit = (int) ($filters['limit'] ?? $filters['per_page'] ?? 15);

        return AuditLog::query()
            ->with(['employee.user', 'employee.role'])
            ->when($filters['action'] ?? null, function ($query, $action) {
                $query->where('action', $action);
            })
            ->when($filters['affected_table'] ?? $filters['table_name'] ?? null, function ($query, $table) {
                $query->where('affected_table', $table);
            })
            ->when($filters['affected_record_id'] ?? null, function ($query, $recordId) {
                $query->where('affected_record_id', $recordId);
            })
            ->when($filters['employee_id'] ?? null, function ($query, $employeeId) {
                $query->where('employee_id', $employeeId);
            })
            ->when($filters['user_id'] ?? null, function ($query, $userId) {
                $query->whereHas('employee.user', function ($q) use ($userId) {
                    $q->where('id', $userId);
                });
            })
            ->when($filters['start_date'] ?? $filters['desde'] ?? null, function ($query, $startDate) {
                $query->whereDate('created_at', '>=', $startDate);
            })
            ->when($filters['end_date'] ?? $filters['hasta'] ?? null, function ($query, $endDate) {
                $query->whereDate('created_at', '<=', $endDate);
            })
            ->latest('created_at')
            ->latest('id')
            ->paginate($limit);
    }

    /**
     * Obtener el detalle de un registro de bitácora por su identificador único.
     *
     * @throws ModelNotFoundException
     */
    public function getById(int $id): AuditLog
    {
        return AuditLog::with(['employee.user', 'employee.role'])->findOrFail($id);
    }
}
