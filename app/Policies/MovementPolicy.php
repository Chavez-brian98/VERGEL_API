<?php

namespace App\Policies;

use App\Models\Movement;
use App\Models\User;

class MovementPolicy
{
    /**
     * Determina si el usuario autenticado puede consultar el listado general de movimientos.
     */
    public function viewAny(User $user): bool
    {
        return $this->canReadMovements($user);
    }

    /**
     * Determina si el usuario autenticado puede ver el detalle individual de un movimiento.
     */
    public function view(User $user, Movement $movement): bool
    {
        return $this->canReadMovements($user);
    }

    /**
     * Regla central de lectura:
     * El colaborador debe estar activo y pertenecer a roles administrativos o de supervisión
     * (Administrador, Auditor, Recepcionista). Técnicos de campo quedan estrictamente excluidos.
     */
    private function canReadMovements(User $user): bool
    {
        if (! $user->employee || ! $user->employee->active) {
            return false;
        }

        $roleName = $user->employee->role?->role_name;

        return in_array($roleName, ['Administrador', 'Auditor', 'Recepcionista', 'Admin'], true);
    }
}
