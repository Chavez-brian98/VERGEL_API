<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Appointment::class);
    }

    /**
     * Obtiene las reglas de validación aplicables para agendar una cita manual.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Identificación de cliente: cliente existente o datos para cliente nuevo
            'customer_id' => [
                'nullable',
                'integer',
                'required_without:customer',
                'prohibits:customer',
                Rule::exists('customers', 'id')->withoutTrashed(),
            ],
            'customer' => [
                'nullable',
                'array',
                'required_without:customer_id',
                'prohibits:customer_id',
            ],
            'customer.legal_name' => [
                'required_with:customer',
                'string',
                'max:150',
            ],
            'customer.phone' => [
                'required_with:customer',
                'string',
                'max:20',
            ],
            'customer.address' => [
                'required_with:customer',
                'string',
                'max:255',
            ],
            'customer.municipality' => [
                'required_with:customer',
                'string',
                'max:60',
            ],
            'customer.department' => [
                'nullable',
                'string',
                'max:60',
            ],
            'customer.email' => [
                'nullable',
                'email',
                'max:120',
            ],

            // Colaborador / Técnico asignado
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->withoutTrashed(),
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

            // Estado y detalles
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
            'customer_id.required_without' => 'Debe indicar un cliente existente o proporcionar los datos del nuevo cliente.',
            'customer_id.prohibits' => 'No debe enviar customer_id si proporciona los datos de un nuevo cliente.',
            'customer_id.exists' => 'El cliente especificado no existe o se encuentra inactivo.',
            'customer.required_without' => 'Debe indicar un cliente existente o proporcionar los datos del nuevo cliente.',
            'customer.prohibits' => 'No debe enviar el objeto customer si ya especificó un customer_id.',
            'customer.legal_name.required_with' => 'El nombre del nuevo cliente es obligatorio.',
            'customer.phone.required_with' => 'El teléfono del nuevo cliente es obligatorio.',
            'customer.address.required_with' => 'La dirección del nuevo cliente es obligatoria.',
            'customer.municipality.required_with' => 'El municipio del nuevo cliente es obligatorio.',
            'customer.email.email' => 'El correo electrónico del cliente no tiene un formato válido.',
            'employee_id.required' => 'Debe asignar un colaborador o técnico para la cita.',
            'employee_id.exists' => 'El colaborador asignado no existe.',
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
