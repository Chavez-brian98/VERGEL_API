<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

class QuotePolicy
{
    use AuthorizesEmployeeRoles;

    /**
     * Determina si el usuario puede listar cotizaciones.
     */
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de una cotización.
     */
    public function view(User $user, ?Quote $quote = null): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede crear cotizaciones.
     */
    public function create(User $user): bool
    {
        return $this->isAdminOrRecepcionista($user);
    }

    /**
     * Determina si el usuario puede actualizar una cotización.
     */
    public function update(User $user, ?Quote $quote = null): bool
    {
        return $this->isAdminOrRecepcionista($user);
    }

    /**
     * Determina si el usuario puede cambiar el estado de una cotización.
     */
    public function changeStatus(User $user, ?Quote $quote = null): bool
    {
        return $this->isAdminOrRecepcionista($user);
    }

    /**
     * Determina si el usuario puede eliminar una cotización.
     */
    public function delete(User $user, ?Quote $quote = null): bool
    {
        return $this->isAdminOrRecepcionista($user);
    }

    /**
     * Determina si el usuario puede restaurar una cotización eliminada.
     */
    public function restore(User $user, ?Quote $quote = null): bool
    {
        return $this->isAdminOrRecepcionista($user);
    }

    /**
     * Administrador o Recepcionista (gestión de cotizaciones).
     */
    private function isAdminOrRecepcionista(User $user): bool
    {
        return $this->hasAnyRole($user, ['Admin', 'Administrador', 'Recepcionista']);
    }
}
