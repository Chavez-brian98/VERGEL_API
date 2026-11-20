<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

class AuditLogPolicy
{
    use AuthorizesEmployeeRoles;

    /**
     * Determina si el usuario puede listar los registros de bitácoras de auditoría.
     * Permitido para Administradores y Auditores activos.
     */
    public function viewAny(User $user): bool
    {
        return $this->canViewAuditLogs($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de un registro de bitácora.
     * Permitido para Administradores y Auditores activos.
     */
    public function view(User $user, ?AuditLog $auditLog = null): bool
    {
        return $this->canViewAuditLogs($user);
    }

    /**
     * Verifica si el usuario tiene rol de Administrador o Auditor y es un colaborador activo.
     */
    private function canViewAuditLogs(User $user): bool
    {
        return $this->hasAnyRole($user, ['Admin', 'Administrador', 'Auditor']);
    }
}
