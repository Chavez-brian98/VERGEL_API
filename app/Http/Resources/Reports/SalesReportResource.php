<?php

namespace App\Http\Resources\Reports;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SalesReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sales = round((float) $this->resource->total_sales, 2);
        $paid = round((float) $this->resource->total_paid, 2);
        $count = (int) $this->resource->quotes_count;

        return [
            'period' => (string) $this->resource->period,
            'quotesCount' => $count,
            'totalSales' => $sales,
            'totalPaid' => $paid,
            'totalPending' => round($sales - $paid, 2),
            'averageTicket' => $count > 0 ? round($sales / $count, 2) : 0.0,
        ];
    }
}
