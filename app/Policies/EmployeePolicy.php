<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Determina si el usuario puede listar empleados.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de un empleado.
     */
    public function view(User $user, ?Employee $employee = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede crear empleados.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede actualizar un empleado.
     */
    public function update(User $user, ?Employee $employee = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede cambiar el estado de un empleado.
     */
    public function changeStatus(User $user, ?Employee $employee = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar un empleado.
     */
    public function delete(User $user, ?Employee $employee = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede restaurar un empleado eliminado.
     */
    public function restore(User $user, ?Employee $employee = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar permanentemente un empleado.
     */
    public function forceDelete(User $user, ?Employee $employee = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Verifica si el usuario tiene el rol de Administrador.
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
