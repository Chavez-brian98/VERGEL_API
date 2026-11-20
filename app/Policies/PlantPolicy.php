<?php

namespace App\Policies;

use App\Models\Plant;
use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

class PlantPolicy
{
    use AuthorizesEmployeeRoles;

    /**
     * Determina si el usuario puede listar plantas.
     */
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de una planta.
     */
    public function view(User $user, ?Plant $plant = null): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede crear plantas.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede actualizar una planta.
     */
    public function update(User $user, ?Plant $plant = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar una planta.
     */
    public function delete(User $user, ?Plant $plant = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede restaurar una planta eliminada.
     */
    public function restore(User $user, ?Plant $plant = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar permanentemente una planta.
     */
    public function forceDelete(User $user, ?Plant $plant = null): bool
    {
        return $this->isAdmin($user);
    }
}
