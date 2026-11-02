<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Appointment\StoreAppointmentRequest;
use App\Http\Requests\Appointment\StoreQuoteAppointmentRequest;
use App\Http\Requests\Appointment\UpdateAppointmentRequest;
use App\Http\Resources\Appointments\AppointmentResource;
use App\Services\AppointmentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

    /**
     * Agendar cita desde una cotización.
     *
     * Permite programar directamente una cita de servicio o visita técnica vinculada a una cotización existente.
     * El cliente (`customer_id`) se hereda de manera automática de la cotización y el origen de la cita se establece como 'quote'.
     *
     * @subgroup Escritura
     *
     * @urlParam quote integer required ID de la cotización a vincular. Example: 1
     *
     * @bodyParam employee_id integer required ID del colaborador o técnico de campo asignado para la visita. Example: 2
     * @bodyParam service_id integer ID del servicio de catálogo presupuestado (opcional). Example: 1
     * @bodyParam appointment_date string required Fecha programada de la cita en formato AAAA-MM-DD. Example: 2026-11-20
     * @bodyParam appointment_time string required Hora programada de la cita en formato HH:MM (24h). Example: 10:00
     * @bodyParam status string Estado de la cita (pending, confirmed, completed, cancelled, annulled). Por defecto 'pending'. Example: pending
     * @bodyParam notes string Observaciones o especificaciones de la visita. Example: Ejecución del servicio según cotización aprobada.
     * @bodyParam is_inspection boolean Si es verdadero, genera de forma automática una ficha de inspección técnica vinculada. Example: false
     *
     * @response status=201 {
     *   "data": {
     *     "id": 2,
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
     *     "quoteId": 1,
     *     "appointmentDate": "2026-11-20",
     *     "appointmentTime": "10:00",
     *     "status": "pending",
     *     "origin": "quote",
     *     "notes": "Ejecución del servicio según cotización aprobada.",
     *     "createdAt": "2026-11-01T23:20:00+00:00",
     *     "updatedAt": "2026-11-01T23:20:00+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Cotización no encontrada",
     *   "codigo": 404
     * }
     * @response status=422 {
     *   "message": "El colaborador asignado debe ser un Técnico de campo activo.",
     *   "errors": {
     *     "employee_id": [
     *       "El colaborador asignado debe ser un Técnico de campo activo."
     *     ]
     *   }
     * }
     * @response status=403 {
     *   "message": "This action is unauthorized."
     * }
     * @response status=500 {
     *   "message": "Error interno al agendar la cita desde cotización",
     *   "codigo": 500
     * }
     */
    public function storeFromQuote(StoreQuoteAppointmentRequest $request, $quote): JsonResponse
    {
        try {
            $appointment = $this->appointmentService->createFromQuote($quote, $request->validated());

            return (new AppointmentResource($appointment))
                ->response()
                ->setStatusCode(201);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cotización no encontrada',
                'codigo' => 404,
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating appointment from quote: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al agendar la cita desde cotización',
                'codigo' => 500,
            ], 500);
        }
    }

    /**
     * Actualizar o reprogramar una cita.
     *
     * Permite modificar los datos de una cita existente: reprogramar su fecha y hora, reasignar el técnico de campo,
     * actualizar su estado (pending, confirmed, completed, cancelled, annulled), cambiar el servicio o actualizar notas.
     *
     * Si se reprograma la fecha, hora o técnico, el sistema valida que no existan conflictos de agenda (margen de 5 horas),
     * excluyendo automáticamente la propia cita evaluada para evitar falsas colisiones.
     *
     * @subgroup Escritura
     *
     * @urlParam appointment integer required ID de la cita a actualizar. Example: 1
     *
     * @bodyParam employee_id integer ID del nuevo colaborador o técnico de campo asignado. Example: 4
     * @bodyParam service_id integer ID del nuevo servicio solicitado (o null para quitar). Example: 2
     * @bodyParam appointment_date string Nueva fecha de la cita en formato AAAA-MM-DD. Example: 2026-11-18
     * @bodyParam appointment_time string Nueva hora de la cita en formato HH:MM (24h). Example: 14:00
     * @bodyParam status string Nuevo estado de la cita (pending, confirmed, completed, cancelled, annulled). Example: confirmed
     * @bodyParam notes string Observaciones o motivo de la reprogramación. Example: Reprogramada a solicitud del cliente por viaje de negocios.
     *
     * @response status=200 {
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
     *     "employeeId": 4,
     *     "employee": {
     *       "id": 4,
     *       "fullName": "Carlos Mendoza"
     *     },
     *     "serviceId": 2,
     *     "service": {
     *       "id": 2,
     *       "name": "Fertilización Orgánica",
     *       "price": 35.0
     *     },
     *     "quoteId": null,
     *     "appointmentDate": "2026-11-18",
     *     "appointmentTime": "14:00",
     *     "status": "confirmed",
     *     "origin": "manual",
     *     "notes": "Reprogramada a solicitud del cliente por viaje de negocios.",
     *     "createdAt": "2026-11-01T16:00:00+00:00",
     *     "updatedAt": "2026-11-02T08:00:00+00:00"
     *   }
     * }
     * @response status=404 {
     *   "message": "Cita no encontrada",
     *   "codigo": 404
     * }
     * @response status=422 {
     *   "message": "El técnico de campo ya tiene una cita asignada a las 10:00 en esta fecha. Debe existir un intervalo mínimo de 5 horas entre cada visita.",
     *   "errors": {
     *     "appointment_time": [
     *       "El técnico de campo ya tiene una cita asignada a las 10:00 en esta fecha. Debe existir un intervalo mínimo de 5 horas entre cada visita."
     *     ]
     *   }
     * }
     * @response status=403 {
     *   "message": "This action is unauthorized."
     * }
     * @response status=500 {
     *   "message": "Error interno al actualizar la cita",
     *   "codigo": 500
     * }
     */
    public function update(UpdateAppointmentRequest $request, $appointment): JsonResponse
    {
        try {
            $updated = $this->appointmentService->update($appointment, $request->validated());

            return (new AppointmentResource($updated))
                ->response()
                ->setStatusCode(200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Cita no encontrada',
                'codigo' => 404,
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating appointment: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return response()->json([
                'message' => 'Error interno al actualizar la cita',
                'codigo' => 500,
            ], 500);
        }
    }
}
