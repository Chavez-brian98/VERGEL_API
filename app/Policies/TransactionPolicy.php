<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determina si el usuario autenticado puede listar el historial de transacciones.
     */
    public function viewAny(User $user): bool
    {
        return $this->canAccessHistory($user);
    }

    /**
     * Determina si el usuario autenticado puede ver el detalle de una transacción.
     */
    public function view(User $user, ?Quote $quote = null): bool
    {
        return $this->canAccessHistory($user);
    }

    /**
     * Regla central de acceso al historial:
     * El colaborador debe estar activo y pertenecer a los roles autorizados
     * (Administrador, Auditor, Recepcionista). Los técnicos de campo quedan excluidos.
     */
    private function canAccessHistory(User $user): bool
    {
        if (! $user->employee || ! $user->employee->active) {
            return false;
        }

        $roleName = $user->employee->role?->role_name;

        return in_array($roleName, ['Administrador', 'Auditor', 'Recepcionista', 'Admin'], true);
    }
}
