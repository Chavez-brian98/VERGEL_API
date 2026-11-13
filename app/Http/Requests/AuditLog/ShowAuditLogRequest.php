<?php

namespace App\Http\Requests\AuditLog;

use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;

class ShowAuditLogRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('view', AuditLog::class);
    }

    /**
     * Obtiene las reglas de validación aplicables a la solicitud.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
