<?php

namespace App\Http\Requests\Appointment;

use App\Models\Appointment;
use Illuminate\Foundation\Http\FormRequest;

class ShowAppointmentRequest extends FormRequest
{
    /**
     * Determina si el usuario autenticado está autorizado para realizar esta solicitud.
     */
    public function authorize(): bool
    {
        $appointmentParam = $this->route('appointment') ?? $this->route('id');
        $appointment = $appointmentParam instanceof Appointment
            ? $appointmentParam
            : Appointment::find($appointmentParam);

        return (bool) $this->user()?->can('view', $appointment ?? Appointment::class);
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
