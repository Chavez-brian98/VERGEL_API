<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Resources\Appointments\AppointmentResource;
use App\Services\AppointmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @group Citas y Agenda
 *
 * Endpoints para la gestión y agendamiento de citas, visitas técnicas e inspecciones operativas.
 */
class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService $appointmentService
    ) {}

    /**
     * Agendar cita manualmente.
     *
     * Permite agendar una nueva cita directamente desde el calendario sin necesidad de una cotización previa.
     * Es ideal para clientes que llaman, ingresan por campañas publicitarias o requieren una inspección técnica inicial.
     * Soporta tanto clientes registrados previamente como la creación automática de clientes no registrados.
     *
     * @subgroup Escritura
     *
     * @bodyParam customer_id integer ID del cliente existente en el sistema. Requerido si no se envía 'customer'. Example: 1
     * @bodyParam customer object Objeto con información del nuevo cliente. Requerido si no se envía 'customer_id'.
     * @bodyParam customer.legal_name string required Nombre completo o razón social del cliente. Example: Juan Carlos Pérez
     * @bodyParam customer.phone string required Teléfono principal de contacto. Example: 7890-1234
     * @bodyParam customer.address string required Dirección física de la propiedad o servicio. Example: Residencial Cumbres de Cuscatlán, Pol. D #14
     * @bodyParam customer.municipality string required Municipio donde se ubica el cliente. Example: Antiguo Cuscatlán
     * @bodyParam customer.department string Departamento donde se ubica el cliente. Example: La Libertad
     * @bodyParam customer.email string Correo electrónico de contacto. Example: juan.perez@example.com
     * @bodyParam employee_id integer required ID del colaborador o técnico de campo asignado para atender la cita. Example: 2
     * @bodyParam service_id integer ID del servicio de catálogo solicitado (opcional si es visita general). Example: 1
     * @bodyParam appointment_date string required Fecha programada de la cita en formato AAAA-MM-DD. Example: 2026-11-15
     * @bodyParam appointment_time string required Hora programada de la cita en formato HH:MM (24h). Example: 09:30
     * @bodyParam status string Estado de la cita (pending, confirmed, completed, cancelled, annulled). Por defecto 'pending'. Example: pending
     * @bodyParam notes string Observaciones o requerimientos especiales indicados por el cliente. Example: Cliente solicita inspección inicial para diseño de jardín frontal.
     * @bodyParam is_inspection boolean Si es verdadero, genera de forma automática una ficha de inspección inicial vinculada a la cita. Example: true
     *
     * @response status=201 {
     *   "data": {
     *     "id": 1,
     *     "customerId": 1,
     *     "customer": {
     *       "id": 1,
     *       "legalName": "Juan Carlos Pérez",
     *       "tradeName": null,
     *       "phone": "7890-1234",
     *       "address": "Residencial Cumbres de Cuscatlán, Pol. D #14",
     *       "municipality": "Antiguo Cuscatlán",
     *       "department": "La Libertad",
     *       "email": "juan.perez@example.com"
     *     },
     *     "employeeId": 2,
     *     "employee": {
     *       "id": 2,
     *       "fullName": "Carlos Mendoza"
     *     },
     *     "serviceId": 1,
     *     "service": {
     *       "id": 1,
     *       "name": "Poda y Mantenimiento de Césped",
     *       "price": 45.0
     *     },
     *     "quoteId": null,
     *     "appointmentDate": "2026-11-15",
     *     "appointmentTime": "09:30",
     *     "status": "pending",
     *     "origin": "manual",
     *     "notes": "Cliente solicita inspección inicial para diseño de jardín frontal.",
     *     "createdAt": "2026-11-01T16:00:00+00:00",
     *     "updatedAt": "2026-11-01T16:00:00+00:00"
     *   }
     * }
     * @response status=422 {
     *   "message": "Debe indicar un cliente existente o proporcionar los datos del nuevo cliente.",
     *   "errors": {
     *     "customer_id": [
     *       "Debe indicar un cliente existente o proporcionar los datos del nuevo cliente."
     *     ]
     *   }
     * }
     * @response status=403 {
     *   "message": "This action is unauthorized."
     * }
     * @response status=500 {
     *   "message": "Error interno al agendar la cita",
     *   "codigo": 500
     * }
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->create($request->validated());

            return (new AppointmentResource($appointment))
                ->response()
                ->setStatusCode(201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating appointment: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al agendar la cita',
                'codigo' => 500,
            ], 500);
        }
    }
}
