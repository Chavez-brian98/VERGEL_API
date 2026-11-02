<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Inspection;
use App\Models\Quote;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
     *
     * @throws ValidationException
     */
    public function create(array $data): Appointment
    {
        // Validaciones de negocio reutilizables
        $this->validateTechnician((int) $data['employee_id']);
        $this->validateTechnicianAvailability(
            (int) $data['employee_id'],
            $data['appointment_date'],
            $data['appointment_time']
        );

        return DB::transaction(function () use ($data) {
            $customerId = $data['customer_id'] ?? null;

            if (! empty($data['customer'])) {
                // Delegación estricta de la creación del cliente al CustomerService oficial
                // NOTA: CustomerService inicializa de forma atómica el cliente, su billetera y sus puntos de fidelidad.
                // Si en el futuro se requiere clasificar al prospecto con una etiqueta de origen o campaña publicitaria,
                // se recomienda agregar dicho atributo en CustomerService.
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
     * Agenda una cita de servicio o inspección directamente vinculada a una cotización existente.
     * Hereda automáticamente el customer_id y asigna el origen 'quote'.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function createFromQuote(Quote|int $quote, array $data): Appointment
    {
        $quoteModel = $quote instanceof Quote ? $quote : Quote::findOrFail($quote);

        if (! $quoteModel->customer_id) {
            throw ValidationException::withMessages([
                'quote' => ['La cotización seleccionada no cuenta con un cliente asociado válido.'],
            ]);
        }

        // Validar que la cotización no tenga ya una cita activa programada
        $this->validateQuoteHasNoActiveAppointment($quoteModel);

        // Validaciones de negocio reutilizables
        $this->validateTechnician((int) $data['employee_id']);
        $this->validateTechnicianAvailability(
            (int) $data['employee_id'],
            $data['appointment_date'],
            $data['appointment_time']
        );

        return DB::transaction(function () use ($quoteModel, $data) {
            $notes = ! empty($data['notes'])
                ? $data['notes']
                : "Cita programada desde cotización {$quoteModel->quote_number}";

            $appointment = Appointment::create([
                'customer_id' => $quoteModel->customer_id,
                'quote_id' => $quoteModel->id,
                'service_id' => $data['service_id'] ?? null,
                'employee_id' => $data['employee_id'],
                'appointment_date' => $data['appointment_date'],
                'appointment_time' => $data['appointment_time'],
                'status' => $data['status'] ?? Appointment::STATUS_PENDING,
                'origin' => Appointment::ORIGIN_QUOTE,
                'notes' => $notes,
            ]);

            // Si se marcó como inspección inicial, crear el registro asociado en inspections
            if (! empty($data['is_inspection'])) {
                Inspection::create([
                    'customer_id' => $quoteModel->customer_id,
                    'appointment_id' => $appointment->id,
                    'employee_id' => $appointment->employee_id,
                    'inspection_date' => $appointment->appointment_date?->format('Y-m-d') ?? $data['appointment_date'],
                    'findings' => $notes,
                ]);
            }

            $this->logAudit('insert', 'appointments', $appointment->id, null, $appointment->toArray());

            return $appointment->load(['customer', 'employee', 'service', 'quote']);
        });
    }

    /**
     * Valida que la cotización no tenga actualmente una cita activa (en estado pendiente o confirmada).
     *
     * @throws ValidationException
     */
    public function validateQuoteHasNoActiveAppointment(Quote $quote): void
    {
        $activeAppointment = Appointment::query()
            ->where('quote_id', $quote->id)
            ->whereIn('status', [Appointment::STATUS_PENDING, Appointment::STATUS_CONFIRMED])
            ->first();

        if ($activeAppointment) {
            $dateFormatted = $activeAppointment->appointment_date?->format('Y-m-d') ?? $activeAppointment->appointment_date;
            $timeFormatted = is_string($activeAppointment->appointment_time)
                ? substr($activeAppointment->appointment_time, 0, 5)
                : $activeAppointment->appointment_time;

            throw ValidationException::withMessages([
                'quote' => [
                    "Esta cotización ya tiene una cita activa programada para el {$dateFormatted} a las {$timeFormatted}. Si requiere cambiar la fecha u horario, utilice la opción de reprogramar.",
                ],
            ]);
        }
    }

    /**
     * Valida que el colaborador asignado exista, esté activo y cuente con el rol 'Tecnico de campo'.
     *
     * @throws ValidationException
     */
    public function validateTechnician(int $employeeId): void
    {
        $employee = Employee::with('role')->find($employeeId);

        if (! $employee || ! $employee->active || $employee->role?->role_name !== 'Tecnico de campo') {
            throw ValidationException::withMessages([
                'employee_id' => ['El colaborador asignado debe ser un Técnico de campo activo.'],
            ]);
        }
    }

    /**
     * Valida la disponibilidad del técnico, exigiendo un intervalo mínimo de 5 horas entre citas del mismo día.
     * Citas en estado cancelada o anulada no bloquean la agenda.
     *
     * @throws ValidationException
     */
    public function validateTechnicianAvailability(
        int $employeeId,
        string $date,
        string $time,
        ?int $excludeAppointmentId = null
    ): void {
        $newMinutes = ((int) substr($time, 0, 2) * 60) + (int) substr($time, 3, 2);

        $existingAppointments = Appointment::query()
            ->where('employee_id', $employeeId)
            ->whereDate('appointment_date', $date)
            ->whereNotIn('status', [Appointment::STATUS_CANCELLED, Appointment::STATUS_ANNULLED])
            ->when($excludeAppointmentId, fn ($q) => $q->where('id', '!=', $excludeAppointmentId))
            ->get();

        foreach ($existingAppointments as $appointment) {
            $existingTimeStr = is_string($appointment->appointment_time)
                ? $appointment->appointment_time
                : (string) $appointment->appointment_time;

            $existingMinutes = ((int) substr($existingTimeStr, 0, 2) * 60) + (int) substr($existingTimeStr, 3, 2);
            $diffMinutes = abs($newMinutes - $existingMinutes);

            if ($diffMinutes < 300) {
                $existingFormatted = substr($existingTimeStr, 0, 5);
                throw ValidationException::withMessages([
                    'appointment_time' => [
                        "El técnico de campo ya tiene una cita asignada a las {$existingFormatted} en esta fecha. Debe existir un intervalo mínimo de 5 horas entre cada visita.",
                    ],
                ]);
            }
        }
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
