<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    /**
     * Determina si el usuario puede listar roles.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de un rol.
     */
    public function view(User $user, ?Role $role = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede crear roles.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede actualizar un rol.
     */
    public function update(User $user, ?Role $role = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar un rol.
     */
    public function delete(User $user, ?Role $role = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede restaurar un rol eliminado.
     */
    public function restore(User $user, ?Role $role = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar permanentemente un rol.
     */
    public function forceDelete(User $user, ?Role $role = null): bool
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
