<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlantRankingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'plantId' => (int) $this->resource->plant_id,
            'name' => (string) $this->resource->name,
            'categoryType' => $this->resource->category_type,
            'unitsSold' => round((float) $this->resource->units_sold, 2),
            'salesTotal' => round((float) $this->resource->sales_total, 2),
            'quotesCount' => (int) $this->resource->quotes_count,
            'averageUnitPrice' => round((float) $this->resource->average_unit_price, 2),
        ];
    }
}
