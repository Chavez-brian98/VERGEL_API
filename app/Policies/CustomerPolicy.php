<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    /**
     * Determina si el usuario puede listar clientes.
     */
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de un cliente.
     */
    public function view(User $user, ?Customer $customer = null): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede crear clientes.
     */
    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede actualizar un cliente.
     */
    public function update(User $user, ?Customer $customer = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede cambiar el estado de un cliente.
     */
    public function changeStatus(User $user, ?Customer $customer = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede asignar o cambiar el plan de un cliente.
     */
    public function assignPlan(User $user, ?Customer $customer = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar un cliente.
     */
    public function delete(User $user, ?Customer $customer = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede restaurar un cliente eliminado.
     */
    public function restore(User $user, ?Customer $customer = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede eliminar permanentemente un cliente.
     */
    public function forceDelete(User $user, ?Customer $customer = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Verifica si el usuario es un empleado activo con un rol asignado en el sistema.
     * Al ser dinámico, cualquier rol existente en la base de datos permite la consulta de clientes.
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
