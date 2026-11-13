<?php

namespace App\Http\Requests\AuditLog;

use App\Models\AuditLog;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexAuditLogRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', AuditLog::class);
    }

    /**
     * Prepara los datos para validación normalizando alias comunes de parámetros.
     */
    protected function prepareForValidation(): void
    {
        $merge = [];

        if ($this->has('table_name') && ! $this->has('affected_table')) {
            $merge['affected_table'] = $this->input('table_name');
        }

        if ($this->has('desde') && ! $this->has('start_date')) {
            $merge['start_date'] = $this->input('desde');
        }

        if ($this->has('hasta') && ! $this->has('end_date')) {
            $merge['end_date'] = $this->input('hasta');
        }

        if ($this->has('per_page') && ! $this->has('limit')) {
            $merge['limit'] = $this->input('per_page');
        }

        if (! empty($merge)) {
            $this->merge($merge);
        }
    }

    /**
     * Obtiene las reglas de validación aplicables a la solicitud.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'action' => ['nullable', 'string', 'in:insert,update,delete,restore,denied'],
            'affected_table' => ['nullable', 'string', 'max:60'],
            'table_name' => ['nullable', 'string', 'max:60'],
            'affected_record_id' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Obtiene los mensajes personalizados para los errores de validación.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.integer' => 'El identificador de usuario debe ser un número entero.',
            'user_id.exists' => 'El usuario especificado no existe en el sistema.',
            'employee_id.integer' => 'El identificador de colaborador debe ser un número entero.',
            'employee_id.exists' => 'El colaborador especificado no existe en el sistema.',
            'action.string' => 'La acción debe ser una cadena de texto.',
            'action.in' => 'La acción debe ser una de las siguientes: insert, update, delete, restore o denied.',
            'affected_table.string' => 'El nombre de la tabla debe ser una cadena de texto.',
            'affected_table.max' => 'El nombre de la tabla no puede superar los 60 caracteres.',
            'table_name.string' => 'El nombre de la tabla debe ser una cadena de texto.',
            'table_name.max' => 'El nombre de la tabla no puede superar los 60 caracteres.',
            'affected_record_id.integer' => 'El identificador del registro afectado debe ser un número entero.',
            'affected_record_id.min' => 'El identificador del registro afectado debe ser al menos 1.',
            'start_date.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'end_date.date_format' => 'La fecha final debe tener el formato YYYY-MM-DD.',
            'end_date.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha de inicio.',
            'desde.date_format' => 'La fecha inicial (desde) debe tener el formato YYYY-MM-DD.',
            'hasta.date_format' => 'La fecha final (hasta) debe tener el formato YYYY-MM-DD.',
            'hasta.after_or_equal' => 'La fecha final (hasta) debe ser igual o posterior a la fecha inicial.',
            'limit.integer' => 'El límite de registros por página debe ser un número entero.',
            'limit.min' => 'El límite mínimo de registros por página es 1.',
            'limit.max' => 'El límite máximo de registros por página es 100.',
            'per_page.integer' => 'La cantidad de registros por página debe ser un número entero.',
            'per_page.min' => 'La cantidad mínima de registros por página es 1.',
            'per_page.max' => 'La cantidad máxima de registros por página es 100.',
        ];
    }
}
