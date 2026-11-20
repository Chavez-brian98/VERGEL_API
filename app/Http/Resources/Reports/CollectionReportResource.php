<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'employeeId' => $this->resource->employee_id === null ? null : (int) $this->resource->employee_id,
            'employeeName' => $this->resource->employee_name ?? 'Sin empleado asignado',
            'paymentsCount' => (int) $this->resource->payments_count,
            'totalCollected' => round((float) $this->resource->total_collected, 2),
            'methods' => $this->resource->methods ?? [],
        ];
    }
}
