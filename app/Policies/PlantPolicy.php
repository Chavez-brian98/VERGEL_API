<?php

namespace App\Policies;

use App\Models\Plant;
use App\Models\User;

class PlantPolicy
{
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

    /**
     * Verifica si el usuario es un colaborador activo con un rol asignado en el sistema.
     * Al ser dinámico, cualquier rol existente en la base de datos permite la lectura del catálogo.
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
        if (! $user->employee?->active) {
            return false;
        }

        $roleName = $user->employee?->role?->role_name;

        return in_array($roleName, ['Admin', 'Administrador'], true);
    }
}
