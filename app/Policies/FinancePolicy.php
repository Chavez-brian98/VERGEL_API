<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorización del módulo de Finanzas (indicadores, cobros y compras).
 *
 * Finanzas es un módulo de solo lectura restringido a los perfiles
 * Admin / Administrador: el resto de roles activos quedan excluidos.
 */
class FinancePolicy
{
    /**
     * Determina si el usuario autenticado puede consultar el módulo de finanzas.
     */
    public function view(User $user): bool
    {
        $employee = $user->employee;

        if (! $employee || ! $employee->role_id) {
            return false;
        }

        if (isset($employee->active) && ! $employee->active) {
            return false;
        }

        $roleName = $employee->role?->role_name;

        return in_array($roleName, ['Admin', 'Administrador'], true);
    }
}
