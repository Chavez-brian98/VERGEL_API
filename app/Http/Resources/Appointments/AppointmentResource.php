<?php

namespace App\Http\Resources\Appointments;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transforma el recurso a un arreglo para la respuesta JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', function () {
                return [
                    'id' => $this->customer->id,
                    'legalName' => $this->customer->legal_name,
                    'tradeName' => $this->customer->trade_name,
                    'phone' => $this->customer->phone,
                    'address' => $this->customer->address,
                    'municipality' => $this->customer->municipality,
                    'department' => $this->customer->department,
                    'email' => $this->customer->email,
                ];
            }),
            'employeeId' => $this->employee_id,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'fullName' => $this->employee->full_name,
                ];
            }),
            'serviceId' => $this->service_id,
            'service' => $this->whenLoaded('service', function () {
                return $this->service ? [
                    'id' => $this->service->id,
                    'name' => $this->service->name,
                    'price' => (float) $this->service->price,
                ] : null;
            }),
            'quoteId' => $this->quote_id,
            'appointmentDate' => $this->appointment_date?->format('Y-m-d') ?? $this->appointment_date,
            'appointmentTime' => is_string($this->appointment_time)
                ? substr($this->appointment_time, 0, 5)
                : $this->appointment_time,
            'status' => $this->status,
            'origin' => $this->origin,
            'notes' => $this->notes,
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
