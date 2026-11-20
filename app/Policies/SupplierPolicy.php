<?php

namespace App\Policies;

use App\Models\Supplier;
use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

class SupplierPolicy
{
    use AuthorizesEmployeeRoles;

    /**
     * Determina si el usuario puede listar proveedores.
     */
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de un proveedor.
     */
    public function view(User $user, ?Supplier $supplier = null): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede crear proveedores.
     */
    public function create(User $user): bool
    {
        return $this->isAdminOrContador($user);
    }

    /**
     * Determina si el usuario puede actualizar un proveedor.
     */
    public function update(User $user, ?Supplier $supplier = null): bool
    {
        return $this->isAdminOrContador($user);
    }

    /**
     * Determina si el usuario puede cambiar el estado de un proveedor.
     */
    public function changeStatus(User $user, ?Supplier $supplier = null): bool
    {
        return $this->isAdminOrContador($user);
    }

    /**
     * Determina si el usuario puede eliminar un proveedor.
     */
    public function delete(User $user, ?Supplier $supplier = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Determina si el usuario puede restaurar un proveedor eliminado.
     */
    public function restore(User $user, ?Supplier $supplier = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Administrador o Contador (gestión de compras y proveedores).
     */
    private function isAdminOrContador(User $user): bool
    {
        return $this->hasAnyRole($user, ['Admin', 'Administrador', 'Contador']);
    }
}
