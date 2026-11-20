<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfitabilityReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'period' => (string) $this->resource->period,
            'sales' => round((float) $this->resource->sales, 2),
            'purchases' => round((float) $this->resource->purchases, 2),
            'expenses' => round((float) $this->resource->expenses, 2),
            'utility' => round((float) $this->resource->utility, 2),
            'marginPercentage' => round((float) $this->resource->margin_percentage, 2),
        ];
    }
}
