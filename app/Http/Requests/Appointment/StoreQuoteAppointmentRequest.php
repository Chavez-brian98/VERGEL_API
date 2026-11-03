<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use App\Models\Employee;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteAppointmentRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Appointment::class);
    }

    /**
     * Obtiene las reglas de validación aplicables para agendar una cita vinculada a una cotización.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Colaborador / Técnico asignado (exclusivo para Técnicos de campo activos)
            'employee_id' => [
                'required',
                'integer',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $employee = Employee::with('role')->find($value);
                    if (! $employee || ! $employee->active || $employee->role?->role_name !== 'Tecnico de campo') {
                        $fail('El colaborador asignado debe ser un Técnico de campo activo.');
                    }
                },
            ],

            // Servicio del catálogo opcional
            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')->withoutTrashed(),
            ],

            // Programación de fecha y hora
            'appointment_date' => [
                'required',
                'date_format:Y-m-d',
            ],
            'appointment_time' => [
                'required',
                'date_format:H:i',
            ],

            // Estado y observaciones
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
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'is_inspection' => [
                'sometimes',
                'boolean',
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
            'employee_id.required' => 'Debe asignar un colaborador o técnico para la cita.',
            'service_id.exists' => 'El servicio seleccionado no existe.',
            'appointment_date.required' => 'La fecha de la cita es obligatoria.',
            'appointment_date.date_format' => 'La fecha de la cita debe tener el formato AAAA-MM-DD (ej. 2026-11-15).',
            'appointment_time.required' => 'La hora de la cita es obligatoria.',
            'appointment_time.date_format' => 'La hora de la cita debe tener el formato HH:MM (ej. 09:30).',
            'status.in' => 'El estado de la cita no es válido.',
            'notes.max' => 'Las notas no pueden exceder los 1000 caracteres.',
            'is_inspection.boolean' => 'El campo de inspección debe ser verdadero o falso.',
        ];
    }
}
