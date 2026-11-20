<?php

namespace App\Services\Concerns;

use App\Models\AuditLog;
use App\Models\Employee;

trait LogsAudit
{
    /**
     * Registra una acción en la tabla audit_log si el usuario autenticado tiene un empleado asociado.
     *
     * @param  string  $action  Acción realizada ('insert', 'update', 'delete', 'restore', 'denied')
     * @param  string  $tableName  Nombre de la tabla afectada
     * @param  int  $recordId  ID del registro afectado
     * @param  array<string, mixed>|null  $oldData  Datos anteriores
     * @param  array<string, mixed>|null  $newData  Datos nuevos
     */
    protected function logAudit(string $action, string $tableName, int $recordId, ?array $oldData = null, ?array $newData = null): void
    {
        $employeeId = request()->user()?->employee_id
            ?? auth()->user()?->employee_id
            ?? Employee::first()?->id;

        if (! $employeeId) {
            return;
        }

        AuditLog::create([
            'employee_id' => $employeeId,
            'affected_table' => $tableName,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => ! empty($oldData) ? $oldData : null,
            'new_data' => ! empty($newData) ? $newData : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
