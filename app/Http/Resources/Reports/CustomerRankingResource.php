<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerRankingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $billed = round((float) $this->resource->total_billed, 2);
        $paid = round((float) $this->resource->total_paid, 2);

        return [
            'customerId' => (int) $this->resource->customer_id,
            'legalName' => (string) $this->resource->legal_name,
            'tradeName' => $this->resource->trade_name,
            'quotesCount' => (int) $this->resource->quotes_count,
            'totalBilled' => $billed,
            'totalPaid' => $paid,
            'totalPending' => round($billed - $paid, 2),
            'lastSaleDate' => $this->resource->last_sale_date,
        ];
    }
}
