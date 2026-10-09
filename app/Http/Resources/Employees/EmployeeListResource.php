<?php

namespace App\Http\Resources\Employees;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fullName' => $this->full_name,
            'dui' => $this->dui,
            'phone' => $this->phone,
            'active' => (bool) $this->active,
            'roleName' => $this->whenLoaded('role', function () {
                return $this->role ? $this->role->role_name : null;
            }),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
