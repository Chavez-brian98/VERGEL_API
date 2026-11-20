<?php

namespace App\Http\Resources\Finance;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FinanceSummaryResource extends JsonResource
{
    /**
     * Transforma los indicadores financieros a su representación camelCase.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $summary = (array) $this->resource;

        return [
            'totalProduction' => (float) $summary['totalProduction'],
            'totalPaid' => (float) $summary['totalPaid'],
            'totalPending' => (float) $summary['totalPending'],
            'partialPaymentsTotal' => (float) $summary['partialPaymentsTotal'],
            'totalPurchases' => (float) $summary['totalPurchases'],
            'profitability' => (float) $summary['profitability'],
        ];
    }
}
