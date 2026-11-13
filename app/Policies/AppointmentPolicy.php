<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * Determina si el usuario puede listar citas.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasActiveRole($user);
    }

    /**
     * Determina si el usuario puede ver el detalle de una cita.
     */
    public function view(User $user, ?Appointment $appointment = null): bool
    {
        if (! $this->hasActiveRole($user)) {
            return false;
        }

        // Si el usuario es Técnico de campo, solo puede consultar citas asignadas a su persona
        if ($user->employee?->role?->role_name === 'Tecnico de campo' && $appointment) {
            return (int) $user->employee_id === (int) $appointment->employee_id;
        }

        return true;
    }

    /**
     * Determina si el usuario puede agendar una nueva cita.
     */
    public function create(User $user): bool
    {
        return $this->canSchedule($user);
    }

    /**
     * Determina si el usuario puede actualizar una cita.
     */
    public function update(User $user, ?Appointment $appointment = null): bool
    {
        return $this->canSchedule($user);
    }

    /**
     * Determina si el usuario puede eliminar o cancelar una cita.
     */
    public function delete(User $user, ?Appointment $appointment = null): bool
    {
        return $this->canSchedule($user);
    }

    /**
     * Verifica si el usuario es un colaborador activo con un rol asignado.
     */
    private function hasActiveRole(User $user): bool
    {
        return (bool) ($user->employee?->active && $user->employee?->role_id);
    }

    /**
     * Verifica si el colaborador activo tiene permisos de agendamiento (Administrador o Recepcionista).
     */
    private function canSchedule(User $user): bool
    {
        if (! $user->employee?->active) {
            return false;
        }

        $roleName = $user->employee?->role?->role_name;

        return in_array($roleName, ['Administrador', 'Recepcionista', 'Admin'], true);
    }
}
