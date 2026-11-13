<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

class InspectionPolicy
{
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
        if (! $this->canView($user)) {
            return false;
        }

        $roleName = $user->employee?->role?->role_name;

        return in_array($roleName, ['Admin', 'Administrador'], true);
    }

    /**
     * Regla de lectura: cualquier empleado activo con rol asignado.
     */
    private function canView(User $user): bool
    {
        $employee = $user->employee;

        if (! $employee || ! $employee->role_id) {
            return false;
        }

        if (isset($employee->active) && ! $employee->active) {
            return false;
        }

        return true;
    }

    /**
     * Regla de gestión técnica: Admin, Tecnico de campo, Colaborador.
     */
    private function canManage(User $user): bool
    {
        if (! $this->canView($user)) {
            return false;
        }

        $roleName = $user->employee?->role?->role_name;

        return in_array($roleName, ['Admin', 'Administrador', 'Tecnico de campo', 'Técnico de campo', 'Colaborador'], true);
    }
}
