<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Inspection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class AppointmentService
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    /**
     * Obtiene una cita por su identificador único.
     *
     * @throws ModelNotFoundException
     */
    public function getById(int $id): Appointment
    {
        return Appointment::with(['customer', 'employee', 'service', 'quote'])->findOrFail($id);
    }

    /**
     * Agenda una nueva cita de manera manual directamente desde el calendario.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            $customerId = $data['customer_id'] ?? null;

            if (! empty($data['customer'])) {
                $newCustomer = $this->customerService->create($data['customer']);
                $customerId = $newCustomer->id;
            }

            $appointment = Appointment::create([
                'customer_id' => $customerId,
                'quote_id' => null, // Cita manual generada sin requerir cotización previa
                'service_id' => $data['service_id'] ?? null,
                'employee_id' => $data['employee_id'],
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'status' => $data['status'] ?? Appointment::STATUS_PENDING,
                'origin' => Appointment::ORIGIN_MANUAL,
                'notes' => $data['notes'] ?? null,
            ]);

            // Si se marcó como inspección inicial, crear el registro asociado en inspections
            if (! empty($data['is_inspection'])) {
                Inspection::create([
                    'customer_id' => $customerId,
                    'appointment_id' => $appointment->id,
                    'employee_id' => $appointment->employee_id,
                    'inspection_date' => $appointment->appointment_date?->format('Y-m-d') ?? $data['appointment_date'],
                    'findings' => $appointment->notes,
                ]);
            }

            $this->logAudit('insert', 'appointments', $appointment->id, null, $appointment->toArray());

            return $appointment->load(['customer', 'employee', 'service']);
        });
    }

    /**
     * Registra un evento en la bitácora de auditoría.
     */
    private function logAudit(string $action, string $table, int $recordId, ?array $oldData, ?array $newData): void
    {
        $employeeId = auth()->user()?->employee_id ?? Employee::first()?->id;
        if (! $employeeId) {
            return;
        }

        AuditLog::create([
            'employee_id' => $employeeId,
            'affected_table' => $table,
            'affected_record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => request()->ip(),
        ]);
    }
}
