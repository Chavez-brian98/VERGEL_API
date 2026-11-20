<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

class CustomerPolicy
{
    use AuthorizesEmployeeRoles;

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
}
