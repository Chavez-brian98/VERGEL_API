<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ChangePlanRequest;
use App\Http\Requests\Customer\CreateCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Http\Resources\Customers\CustomerListResource;
use App\Http\Resources\Customers\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {}

    /**
     *Consultar y Filtrar Clientes
     */
    public function index(Request $request)
    {
        $limit = $request->input('limit', 15);
        $customers = $this->customerService->search($request->all(), $limit);

        return CustomerListResource::collection($customers);
    }

    /**
     * Registrar un Nuevo Cliente
     */
    public function store(CreateCustomerRequest $request)
    {
        try {
            $customer = $this->customerService->create($request->validated());

            return response()->json(new CustomerResource($customer), 201);
        } catch (\Exception $e) {
            Log::error('Error creating customer: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al crear el cliente'], 500);
        }
    }

    /**
     * Obtener detalle completo de un cliente
     */
    public function show($id)
    {
        $customer = $this->customerService->getById($id);

        return new CustomerResource($customer);
    }

    /**
     * Actualizar Información del Cliente
     */
    public function update(UpdateCustomerRequest $request, $id)
    {
        try {
            $customer = $this->customerService->update($id, $request->validated());

            return response()->json(new CustomerResource($customer), 200);
        } catch (\Exception $e) {
            Log::error('Error updating customer: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al actualizar el cliente'], 500);
        }
    }

    /**
     * Inactivación / Cambio de estado (PATCH)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        try {
            $customer = $this->customerService->updateStatus($id, $request->input('status'));

            return response()->json(new CustomerResource($customer));
        } catch (\Exception $e) {
            Log::error('Error updating customer status: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al cambiar el estado'], 500);
        }
    }

    /**
     * Eliminación de Clientes (Soft Delete)
     */
    public function destroy($id)
    {
        try {
            $this->customerService->delete($id);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting customer: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al eliminar el cliente'], 500);
        }
    }

    /**
     * HU-05: Gestión del Plan de Suscripción del Cliente
     */
    public function updatePlan(ChangePlanRequest $request, $id)
    {
        try {
            $customer = $this->customerService->updatePlan($id, $request->input('plan_id'));

            return response()->json(new CustomerResource($customer));
        } catch (ValidationException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        } catch (\Exception $e) {
            Log::error('Error updating customer plan: '.$e->getMessage());

            return response()->json(['message' => 'Error interno al actualizar el plan'], 500);
        }
    }

    /**
     * Obtener el historial de planes
     */
    public function planHistory($id)
    {
        $customer = Customer::findOrFail($id);
        $history = $customer->planHistories()->with('plan')->orderBy('start_date', 'desc')->get();

        return response()->json(['data' => $history]);
    }
}
