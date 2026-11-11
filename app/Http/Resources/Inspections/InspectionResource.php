<?php

namespace App\Http\Resources\Inspections;

use App\Http\Resources\Customers\CustomerResource;
use App\Http\Resources\Employees\EmployeeResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InspectionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customerId' => $this->customer_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'appointmentId' => $this->appointment_id,
            'employeeId' => $this->employee_id,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'inspectionDate' => $this->inspection_date?->format('Y-m-d'),
            'findings' => $this->findings,
            'terrainWidth' => $this->terrain_width !== null ? (float) $this->terrain_width : null,
            'terrainLength' => $this->terrain_length !== null ? (float) $this->terrain_length : null,
            'totalArea' => $this->total_area !== null ? (float) $this->total_area : null,
            'areaUnit' => $this->area_unit,
            'terrainNotes' => $this->terrain_notes,
            'status' => $this->status,
            'services' => InspectionServiceResource::collection($this->whenLoaded('services')),
            'photos' => InspectionPhotoResource::collection($this->whenLoaded('photos')),
            'createdAt' => $this->created_at?->toIso8601String(),
            'updatedAt' => $this->updated_at?->toIso8601String(),
        ];
    }
}
