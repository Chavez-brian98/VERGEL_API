<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use App\Models\Employee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppointmentRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', Appointment::class);
    }

    /**
     * Prepara y normaliza los datos de la solicitud antes de aplicar las reglas de validación.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('appointment_time') && is_string($this->appointment_time)) {
            // Normalizar a HH:MM si viene con segundos HH:MM:SS
            if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $this->appointment_time, $matches)) {
                $this->merge(['appointment_time' => $matches[1]]);
            }
        }
    }

    /**
     * Obtiene las reglas de validación aplicables para actualizar o reprogramar una cita.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Colaborador / Técnico asignado (exclusivo para Técnicos de campo activos)
            'employee_id' => [
                'sometimes',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $employee = Employee::with('role')->find($value);
                    if (! $employee || ! $employee->active || $employee->role?->role_name !== 'Tecnico de campo') {
                        $fail('El colaborador asignado debe ser un Técnico de campo activo.');
                    }
                },
            ],

            // Servicio del catálogo opcional
            'service_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('services', 'id')->withoutTrashed(),
            ],

            // Programación de fecha y hora
            'appointment_date' => [
                'sometimes',
                'date_format:Y-m-d',
            ],
            'appointment_time' => [
                'sometimes',
                'date_format:H:i',
            ],

            // Estado de la cita
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    Appointment::STATUS_PENDING,
                    Appointment::STATUS_CONFIRMED,
                    Appointment::STATUS_COMPLETED,
                    Appointment::STATUS_CANCELLED,
                    Appointment::STATUS_ANNULLED,
                ]),
            ],

            // Observaciones o notas
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
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
            'employee_id.integer' => 'El identificador del colaborador debe ser un número entero.',
            'service_id.integer' => 'El identificador del servicio debe ser un número entero.',
            'service_id.exists' => 'El servicio seleccionado no existe.',
            'appointment_date.date_format' => 'La fecha de la cita debe tener el formato AAAA-MM-DD (ej. 2026-11-15).',
            'appointment_time.date_format' => 'La hora de la cita debe tener el formato HH:MM (ej. 09:30).',
            'status.in' => 'El estado de la cita no es válido.',
            'notes.max' => 'Las notas no pueden exceder los 1000 caracteres.',
        ];
    }
}
