<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

/**
 * Autorización del módulo de Reportes.
 *
 * Los reportes son de solo lectura y están restringidos a Admin / Administrador.
 */
class ReportsPolicy
{
    use AuthorizesEmployeeRoles;

    /**
     * Determina si el usuario autenticado puede consultar los reportes.
     */
    public function view(User $user): bool
    {
        return $this->isAdmin($user);
    }
}
