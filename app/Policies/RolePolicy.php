<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

class RolePolicy
{
    use AuthorizesEmployeeRoles;

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
}
