<?php

namespace App\Http\Resources\Roles;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'roleName' => $this->role_name,
            'description' => $this->description,
            'employeesCount' => (int) ($this->employees_count ?? 0),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}
