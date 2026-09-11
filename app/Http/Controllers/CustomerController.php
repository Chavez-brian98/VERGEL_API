<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchCustomerRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $customerService
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->customerService->getAll());
    }

    public function search(SearchCustomerRequest $request): JsonResponse
    {
        $customers = $this->customerService->search($request->validated());

        return response()->json([
            'data' => $customers,
        ], 200);
    }

    public function create(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->customerService->create($request->validated());

        return response()->json([
            'message' => 'Customer created successfully.',
            'data' => $customer,
        ], 201);
    }
}