<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;
use App\Policies\Concerns\AuthorizesEmployeeRoles;

class InspectionPolicy
{
    use AuthorizesEmployeeRoles;

    /**
     * Determina si el usuario puede listar inspecciones.
     */
    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede ver una inspección específica.
     */
    public function view(User $user, ?Inspection $inspection = null): bool
    {
        return $this->canView($user);
    }

    /**
     * Determina si el usuario puede crear inspecciones.
     * Permitido para Administradores, Técnicos de campo y Colaboradores.
     */
    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determina si el usuario puede actualizar una inspección.
     */
    public function update(User $user, ?Inspection $inspection = null): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determina si el usuario puede cambiar el estado de una inspección.
     */
    public function changeStatus(User $user, ?Inspection $inspection = null): bool
    {
        return $this->canManage($user);
    }

    /**
     * Determina si el usuario puede eliminar una inspección.
     * Solo Administradores.
     */
    public function delete(User $user, ?Inspection $inspection = null): bool
    {
        return $this->isAdmin($user);
    }

    /**
     * Regla de gestión técnica: Admin, Tecnico de campo, Colaborador.
     */
    private function canManage(User $user): bool
    {
        return $this->hasAnyRole($user, ['Admin', 'Administrador', 'Tecnico de campo', 'Técnico de campo', 'Colaborador']);
    }
}
