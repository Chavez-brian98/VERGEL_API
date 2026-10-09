<?php

namespace App\Http\Resources\Employees;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'dui' => $this->dui,
            'address' => $this->address,
            'municipality' => $this->municipality,
            'department' => $this->department,
            'birthDate' => $this->birth_date ? $this->birth_date->toDateString() : null,
            'hireDate' => $this->hire_date ? $this->hire_date->toDateString() : null,
            'phone' => $this->phone,
            'active' => (bool) $this->active,
            'role' => $this->whenLoaded('role', function () {
                return $this->role ? [
                    'id' => $this->role->id,
                    'roleName' => $this->role->role_name,
                ] : null;
            }),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'updatedAt' => $this->updated_at ? $this->updated_at->toIso8601String() : null,
        ];
    }
}
