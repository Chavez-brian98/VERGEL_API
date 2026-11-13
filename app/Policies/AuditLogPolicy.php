<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determina si el usuario puede listar los registros de bitácoras de auditoría.
     * Únicamente los usuarios con rol de Administrador y colaboradores activos tienen acceso.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de un registro de bitácora.
     */
    public function view(User $user, ?AuditLog $auditLog = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Verifica si el usuario tiene el rol de Administrador y es un colaborador activo.
     */
    private function isAdmin(User $user): bool
    {
        if (! $user->employee?->active) {
            return false;
        }

        $roleName = $user->employee?->role?->role_name;

        return in_array($roleName, ['Admin', 'Administrador'], true);
    }
}
