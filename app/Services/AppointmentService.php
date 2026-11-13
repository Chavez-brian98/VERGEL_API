<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inspection;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppointmentService
{
    public function __construct(
        private readonly CustomerService $customerService
    ) {}

    /**
     * Obtiene el listado de citas aplicando filtros dinámicos con when() y Eager Loading.
     * Alimenta las vistas mensual, semanal, diaria y en lista del calendario.
     *
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator|Collection<int, Appointment>
     */
    public function listAppointments(array $filters = [], ?User $user = null): LengthAwarePaginator|Collection
    {
        $query = Appointment::query()
            ->with(['customer', 'service', 'employee', 'quote']);

        // Scope de seguridad: Técnicos de campo solo consultan citas asignadas a su identificador de colaborador
        if ($user && $user->employee?->role?->role_name === 'Tecnico de campo') {
            $query->where('employee_id', $user->employee_id);
        }

        // Filtro por rango de fechas (Vista Mensual / Semanal)
        $query->when(! empty($filters['start_date']), function ($q) use ($filters) {
            $q->whereDate('appointment_date', '>=', $filters['start_date']);
        });

        $query->when(! empty($filters['end_date']), function ($q) use ($filters) {
            $q->whereDate('appointment_date', '<=', $filters['end_date']);
        });

        // Filtro por fecha puntual (Vista Diaria)
        $query->when(! empty($filters['date']), function ($q) use ($filters) {
            $q->whereDate('appointment_date', $filters['date']);
        });

        // Filtro por estado
        $query->when(! empty($filters['status']), function ($q) use ($filters) {
            $q->where('status', $filters['status']);
        });

        // Filtro por cliente
        $query->when(! empty($filters['customer_id']), function ($q) use ($filters) {
            $q->where('customer_id', $filters['customer_id']);
        });

        // Filtro por técnico asignado (si no fue ya restringido por el rol de técnico)
        $query->when(! empty($filters['employee_id']) && (! $user || $user->employee?->role?->role_name !== 'Tecnico de campo'), function ($q) use ($filters) {
            $q->where('employee_id', $filters['employee_id']);
        });

        // Filtro por servicio
        $query->when(! empty($filters['service_id']), function ($q) use ($filters) {
            $q->where('service_id', $filters['service_id']);
        });

        // Ordenamiento
        $sortBy = $filters['sort_by'] ?? 'appointment_date';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $sortDirection);

        // Si se ordena por fecha, desempatar cronológicamente por hora
        if ($sortBy === 'appointment_date') {
            $query->orderBy('appointment_time', $sortDirection);
        }

        // Si se solicita la colección completa (ej. para alimentar cuadrícula de vista mensual)
        if (! empty($filters['all'])) {
            return $query->get();
        }

        $perPage = (int) ($filters['per_page'] ?? $filters['limit'] ?? 15);

        return $query->paginate($perPage);
    }

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
                $inspection = Inspection::create([
                    'customer_id' => $customerId,
                    'appointment_id' => $appointment->id,
                    'employee_id' => $appointment->employee_id,
                    'inspection_date' => $appointment->appointment_date?->format('Y-m-d') ?? $data['appointment_date'],
                    'findings' => $appointment->notes,
                ]);

                $this->logAudit('insert', 'inspections', $inspection->id, null, $inspection->toArray());
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

        // Validar regla transversal de anticipo mínimo del 50% en materiales
        $this->validateMaterialAdvance($quoteModel);

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
                $inspection = Inspection::create([
                    'customer_id' => $quoteModel->customer_id,
                    'appointment_id' => $appointment->id,
                    'employee_id' => $appointment->employee_id,
                    'inspection_date' => $appointment->appointment_date?->format('Y-m-d') ?? $data['appointment_date'],
                    'findings' => $notes,
                ]);

                $this->logAudit('insert', 'inspections', $inspection->id, null, $inspection->toArray());
            }

            $this->logAudit('insert', 'appointments', $appointment->id, null, $appointment->toArray());

            return $appointment->load(['customer', 'employee', 'service', 'quote']);
        });
    }

    /**
     * Actualiza y reprograma los datos de una cita existente (fecha, hora, técnico, estado, notas, servicio).
     *
     * @param  array<string, mixed>  $data
     *
     * @throws ModelNotFoundException
     * @throws ValidationException
     */
    public function update(Appointment|int $appointment, array $data): Appointment
    {
        $appointmentModel = $appointment instanceof Appointment
            ? $appointment
            : Appointment::findOrFail($appointment);

        // Si la cita ya está completada, no permitir reprogramación de fecha, hora o técnico
        if ($appointmentModel->status === Appointment::STATUS_COMPLETED) {
            if (isset($data['appointment_date']) || isset($data['appointment_time']) || isset($data['employee_id'])) {
                throw ValidationException::withMessages([
                    'status' => ['No se puede reprogramar una cita que ya ha sido completada.'],
                ]);
            }
        }

        // Si se cambia de técnico, validar que sea un Técnico de campo activo
        if (isset($data['employee_id']) && (int) $data['employee_id'] !== (int) $appointmentModel->employee_id) {
            $this->validateTechnician((int) $data['employee_id']);
        }

        // Determinar estado resultante
        $targetStatus = $data['status'] ?? $appointmentModel->status;
        $isCancelledOrAnnulled = in_array($targetStatus, [Appointment::STATUS_CANCELLED, Appointment::STATUS_ANNULLED], true);

        // Si no se cancela ni anula, verificar cambios que requieran revalidar disponibilidad
        $currentDateStr = $appointmentModel->appointment_date instanceof \DateTimeInterface
            ? $appointmentModel->appointment_date->format('Y-m-d')
            : (string) $appointmentModel->appointment_date;
        $currentTimeStr = is_string($appointmentModel->appointment_time)
            ? substr($appointmentModel->appointment_time, 0, 5)
            : (string) $appointmentModel->appointment_time;

        $dateChanged = isset($data['appointment_date']) && $data['appointment_date'] !== $currentDateStr;
        $timeChanged = isset($data['appointment_time']) && substr($data['appointment_time'], 0, 5) !== $currentTimeStr;
        $employeeChanged = isset($data['employee_id']) && (int) $data['employee_id'] !== (int) $appointmentModel->employee_id;
        $reactivating = in_array($appointmentModel->status, [Appointment::STATUS_CANCELLED, Appointment::STATUS_ANNULLED], true) && ! $isCancelledOrAnnulled;

        if (! $isCancelledOrAnnulled && ($dateChanged || $timeChanged || $employeeChanged || $reactivating)) {
            $targetEmployeeId = (int) ($data['employee_id'] ?? $appointmentModel->employee_id);
            $targetDate = $data['appointment_date'] ?? $currentDateStr;
            $targetTime = $data['appointment_time'] ?? $currentTimeStr;

            $this->validateTechnicianAvailability(
                $targetEmployeeId,
                $targetDate,
                $targetTime,
                $appointmentModel->id
            );
        }

        $isChangingToCompleted = ($data['status'] ?? null) === Appointment::STATUS_COMPLETED
            && $appointmentModel->status !== Appointment::STATUS_COMPLETED;

        $updatedAppointment = DB::transaction(function () use ($appointmentModel, $data) {
            $oldData = $appointmentModel->toArray();

            $appointmentModel->update($data);

            // Si hay inspecciones asociadas y se cambió fecha o técnico, sincronizarlas
            if ($appointmentModel->inspections()->exists()) {
                $inspectionUpdates = [];
                if (isset($data['employee_id'])) {
                    $inspectionUpdates['employee_id'] = $data['employee_id'];
                }
                if (isset($data['appointment_date'])) {
                    $inspectionUpdates['inspection_date'] = $data['appointment_date'];
                }
                if (! empty($inspectionUpdates)) {
                    foreach ($appointmentModel->inspections as $inspection) {
                        $oldInspectionData = $inspection->toArray();
                        $appointmentModel->inspections()->where('id', $inspection->id)->update($inspectionUpdates);
                        $this->logAudit('update', 'inspections', $inspection->id, $oldInspectionData, $inspection->fresh()->toArray());
                    }
                }
            }

            $this->logAudit('update', 'appointments', $appointmentModel->id, $oldData, $appointmentModel->toArray());

            return $appointmentModel->load(['customer', 'employee', 'service', 'quote']);
        });

        // Programación automática de mantenimiento recurrente si la cita fue marcada como completada
        if ($isChangingToCompleted) {
            $this->handleAutomaticMaintenanceScheduling($updatedAppointment);
        }

        return $updatedAppointment;
    }

    /**
     * Gestiona la programación automática del próximo mantenimiento recurrente cuando una cita se marca como completada.
     */
    public function handleAutomaticMaintenanceScheduling(Appointment $appointment): void
    {
        $customer = $appointment->customer ?? Customer::find($appointment->customer_id);
        $customer?->loadMissing('currentPlan');

        // Solo se ejecuta para clientes en estado Activo
        if (! $customer || $customer->status !== 'active') {
            return;
        }

        $frequency = $this->resolveCustomerFrequency($customer);
        if (! $frequency || $frequency['value'] <= 0) {
            return;
        }

        $baseDate = $appointment->appointment_date instanceof \DateTimeInterface
            ? Carbon::instance($appointment->appointment_date)
            : Carbon::parse($appointment->appointment_date);

        $idealDate = $this->calculateIdealNextDate($baseDate, $frequency['value'], $frequency['unit']);
        $idealDateStr = $idealDate->format('Y-m-d');

        $timeStr = is_string($appointment->appointment_time)
            ? substr($appointment->appointment_time, 0, 5)
            : (string) $appointment->appointment_time;

        $employeeId = (int) $appointment->employee_id;

        if ($this->isTechnicianAvailable($employeeId, $idealDateStr, $timeStr)) {
            $newAppointment = Appointment::create([
                'customer_id' => $customer->id,
                'quote_id' => null,
                'service_id' => $appointment->service_id,
                'employee_id' => $employeeId,
                'appointment_date' => $idealDateStr,
                'appointment_time' => $timeStr,
                'status' => Appointment::STATUS_PENDING,
                'origin' => Appointment::ORIGIN_AUTOMATIC,
                'notes' => "Mantenimiento recurrente programado automáticamente según frecuencia ({$frequency['value']} {$frequency['unit']})",
            ]);

            $this->logAudit('insert', 'appointments', $newAppointment->id, null, $newAppointment->toArray());

            $appointment->next_appointment = $newAppointment->load(['customer', 'employee', 'service']);
        } else {
            // Calcular fechas disponibles más cercanas alrededor de la fecha ideal
            $suggestedDates = $this->findNearestAvailableDates($employeeId, $idealDate, $timeStr, 3);

            $appointment->suggested_reschedule_dates = [
                'ideal_date' => $idealDateStr,
                'available_dates' => $suggestedDates,
                'message' => "El técnico de campo no tiene disponibilidad en la fecha ideal de mantenimiento ({$idealDateStr}). Se sugieren las fechas más cercanas.",
            ];
        }
    }

    /**
     * Resuelve la frecuencia configurada del cliente, priorizando su configuración individual
     * o recurriendo a la frecuencia definida en su plan de suscripción actual.
     *
     * @return array{value: int, unit: string}|null
     */
    public function resolveCustomerFrequency(Customer $customer): ?array
    {
        if (! empty($customer->visit_frequency_value) && ! empty($customer->visit_frequency_unit)) {
            return [
                'value' => (int) $customer->visit_frequency_value,
                'unit' => $customer->visit_frequency_unit,
            ];
        }

        $plan = $customer->currentPlan;
        if ($plan && ! empty($plan->frequency_value) && ! empty($plan->frequency_unit)) {
            return [
                'value' => (int) $plan->frequency_value,
                'unit' => $plan->frequency_unit,
            ];
        }

        return null;
    }

    /**
     * Calcula la fecha ideal del siguiente mantenimiento según la frecuencia configurada.
     */
    public function calculateIdealNextDate(Carbon $baseDate, int $value, string $unit): Carbon
    {
        $normalizedUnit = strtolower(trim($unit));

        return match ($normalizedUnit) {
            'days', 'day', 'dias', 'día', 'días' => $baseDate->copy()->addDays($value),
            'weeks', 'week', 'semanas', 'semana' => $baseDate->copy()->addWeeks($value),
            'months', 'month', 'meses', 'mes' => $baseDate->copy()->addMonths($value),
            default => $baseDate->copy()->addDays($value),
        };
    }

    /**
     * Verifica si el técnico de campo se encuentra disponible en una fecha y hora específicas.
     */
    public function isTechnicianAvailable(
        int $employeeId,
        string $date,
        string $time,
        ?int $excludeAppointmentId = null
    ): bool {
        try {
            $this->validateTechnicianAvailability($employeeId, $date, $time, $excludeAppointmentId);

            return true;
        } catch (ValidationException) {
            return false;
        }
    }

    /**
     * Busca las fechas disponibles más cercanas alrededor de una fecha ideal cuando existe conflicto de agenda.
     * Evalúa offsets radiales (ej. -1, +1, -2, +2, -3, +3 días).
     *
     * @return array<int, string>
     */
    public function findNearestAvailableDates(int $employeeId, Carbon $idealDate, string $time, int $limit = 3): array
    {
        $suggestedDates = [];
        $offsets = [-1, 1, -2, 2, -3, 3, -4, 4, -5, 5, -6, 6, -7, 7];

        foreach ($offsets as $offset) {
            $candidateDate = $idealDate->copy()->addDays($offset);
            $candidateDateStr = $candidateDate->format('Y-m-d');

            if ($this->isTechnicianAvailable($employeeId, $candidateDateStr, $time)) {
                $suggestedDates[] = $candidateDateStr;
            }

            if (count($suggestedDates) >= $limit) {
                break;
            }
        }

        sort($suggestedDates);

        return array_values(array_unique($suggestedDates));
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
     * Valida la regla transversal de anticipo de materiales (mínimo 50% pagado)
     * antes de permitir agendar una cita vinculada a una cotización.
     *
     * @throws ValidationException
     */
    public function validateMaterialAdvance(Quote $quote): void
    {
        // Si la cotización ya está totalmente pagada, se cumple el requisito
        if ($quote->status === 'paid') {
            return;
        }

        // 1. Sumar el costo de los materiales (plantas)
        $materialTotal = (float) $quote->items()
            ->where('item_type', 'plant')
            ->sum('total');

        // Si la cotización no incluye materiales (solo mano de obra), no requiere anticipo
        if ($materialTotal <= 0) {
            return;
        }

        // 2. Calcular el 50% requerido de anticipo sobre los materiales
        $requiredAdvance = round($materialTotal * 0.50, 2);

        // 3. Consultar el total de dinero abonado específicamente a esta cotización
        $totalPaid = (float) Payment::query()
            ->where('quote_id', $quote->id)
            ->sum('amount_paid');

        // 4. Validar si el abono cubre al menos el 50% del valor de los materiales
        if ($totalPaid < $requiredAdvance) {
            $shortage = round($requiredAdvance - $totalPaid, 2);
            $totalFormatted = number_format($materialTotal, 2);
            $requiredFormatted = number_format($requiredAdvance, 2);
            $paidFormatted = number_format($totalPaid, 2);
            $shortageFormatted = number_format($shortage, 2);

            throw ValidationException::withMessages([
                'quote' => [
                    "Esta cotización incluye materiales por un total de \${$totalFormatted}. Se requiere un anticipo mínimo del 50% (\${$requiredFormatted}) para programar la cita. Total abonado actualmente: \${$paidFormatted} (Monto pendiente para habilitar agenda: \${$shortageFormatted}).",
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
