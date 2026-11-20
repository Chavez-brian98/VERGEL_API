<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'supplier' => [
                'name' => $this->resource->supplier_name,
                'taxId' => $this->resource->supplier_tax_id,
                'nrc' => $this->resource->supplier_nrc,
            ],
            'documentsCount' => (int) $this->resource->documents_count,
            'amounts' => [
                'taxed' => round((float) $this->resource->taxed, 2),
                'vat' => round((float) $this->resource->vat, 2),
                'total' => round((float) $this->resource->total, 2),
            ],
        ];
    }
}
