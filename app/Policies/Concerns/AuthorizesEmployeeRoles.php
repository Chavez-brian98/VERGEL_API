<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait AuthorizesEmployeeRoles
{
    /**
     * Verifica si el usuario tiene un empleado activo con rol asignado.
     */
    protected function canView(User $user): bool
    {
        return (bool) ($user->employee?->active && $user->employee?->role_id);
    }

    /**
     * Verifica si el usuario es Administrador activo.
     */
    protected function isAdmin(User $user): bool
    {
        return $this->hasAnyRole($user, ['Admin', 'Administrador']);
    }

    /**
     * Verifica si el usuario tiene alguno de los roles indicados y está activo.
     *
     * @param  array<int, string>  $roles
     */
    protected function hasAnyRole(User $user, array $roles): bool
    {
        if (! $user->employee?->active || ! $user->employee?->role_id) {
            return false;
        }

        $roleName = $user->employee?->role?->role_name;

        return in_array($roleName, $roles, true);
    }
}
