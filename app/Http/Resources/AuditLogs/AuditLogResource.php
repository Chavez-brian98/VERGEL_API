<?php

namespace App\Http\Resources\AuditLogs;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * Transformar el recurso en un arreglo para respuesta JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'affectedTable' => $this->affected_table,
            'affectedRecordId' => (int) $this->affected_record_id,
            'oldData' => $this->old_data,
            'newData' => $this->new_data,
            'ipAddress' => $this->ip_address,
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
            'actor' => $this->whenLoaded('employee', function () {
                if (! $this->employee) {
                    return null;
                }

                return [
                    'employeeId' => $this->employee->id,
                    'fullName' => $this->employee->full_name,
                    'role' => $this->employee->role?->role_name,
                    'userId' => $this->employee->user?->id,
                    'email' => $this->employee->user?->email,
                ];
            }),
        ];
    }
}
