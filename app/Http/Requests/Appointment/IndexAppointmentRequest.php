<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAppointmentRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('viewAny', Appointment::class);
    }

    /**
     * Prepara y normaliza los datos de la solicitud antes de aplicar las reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        $mergeData = [];

        // Mapear client_id hacia customer_id si viene en la query
        if ($this->has('client_id') && ! $this->has('customer_id')) {
            $mergeData['customer_id'] = $this->input('client_id');
        }

        // Normalizar bandera booleana para all
        if ($this->has('all')) {
            $value = filter_var($this->input('all'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($value !== null) {
                $mergeData['all'] = $value;
            }
        }

        if (! empty($mergeData)) {
            $this->merge($mergeData);
        }
    }

    /**
     * Obtiene las reglas de validación aplicables para listar y filtrar citas.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Rango de fechas para vistas mensual y semanal
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],

            // Acceso directo a fecha puntual para vista diaria
            'date' => ['nullable', 'date_format:Y-m-d'],

            // Filtro por estado
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_COMPLETED,
                    Appointment::STATUS_CANCELLED,
                    Appointment::STATUS_ANNULLED,
                ]),
            ],

            // Filtro por cliente
            'customer_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->withoutTrashed()],
            'client_id' => ['nullable', 'integer', Rule::exists('customers', 'id')->withoutTrashed()],

            // Filtro por técnico asignado
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')],

            // Filtro por servicio
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')->withoutTrashed()],

            // Ordenamiento
            'sort_by' => ['nullable', 'string', 'in:id,appointment_date,appointment_time,status,created_at'],
            'sort_direction' => ['nullable', 'string', 'in:asc,desc'],

            // Paginación y visualización
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'all' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Obtiene los mensajes personalizados para los errores de validación en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'start_date.date_format' => 'La fecha inicial debe tener el formato AAAA-MM-DD (ej. 2026-11-01).',
            'end_date.date_format' => 'La fecha final debe tener el formato AAAA-MM-DD (ej. 2026-11-30).',
            'end_date.after_or_equal' => 'La fecha final debe ser igual o posterior a la fecha inicial.',
            'date.date_format' => 'La fecha de la cita debe tener el formato AAAA-MM-DD (ej. 2026-11-15).',
            'status.in' => 'El estado especificado no es válido.',
            'customer_id.exists' => 'El cliente especificado no existe o ha sido eliminado.',
            'client_id.exists' => 'El cliente especificado no existe o ha sido eliminado.',
            'employee_id.exists' => 'El colaborador especificado no existe.',
            'service_id.exists' => 'El servicio especificado no existe.',
            'sort_by.in' => 'El campo de ordenamiento no es válido.',
            'sort_direction.in' => 'La dirección de ordenamiento debe ser asc o desc.',
            'per_page.min' => 'La cantidad mínima por página es 1.',
            'per_page.max' => 'La cantidad máxima por página es 100.',
            'limit.min' => 'El límite mínimo por página es 1.',
            'limit.max' => 'El límite máximo por página es 100.',
            'all.boolean' => 'El indicador de lista completa debe ser un valor booleano.',
        ];
    }
}
