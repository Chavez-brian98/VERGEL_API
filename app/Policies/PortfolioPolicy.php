<?php

namespace App\Policies;

use App\Models\User;

/**
 * Autorización del módulo de Cartera (billetera y EcoPuntos).
 *
 * Sigue el mismo esquema RBAC que CustomerPolicy: lectura para cualquier
 * empleado activo con un rol asignado y escritura exclusiva para Admin.
 */
class PortfolioPolicy
{
    /**
     * Determina si el usuario puede consultar el resumen de cartera.
     */
    public function view(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede consultar los movimientos de cartera.
     */
    public function viewMovements(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede registrar transacciones de billetera o puntos.
     */
    public function manage(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Verifica si el usuario es un empleado activo con un rol asignado en el sistema.
     */
    private function canView(User $user): bool
    {
        return (bool) ($user->employee?->active && $user->employee?->role_id);
    }

    /**
     * Verifica si el usuario tiene el rol de Administrador.
     */
    private function isAdmin(User $user): bool
    {
        if (! $this->canView($user)) {
            return false;
        }

        $roleName = $user->employee?->role?->role_name;

        return in_array($roleName, ['Admin', 'Administrador'], true);
    }
}
