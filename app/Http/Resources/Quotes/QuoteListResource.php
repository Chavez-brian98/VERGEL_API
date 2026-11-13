<?php

namespace App\Http\Resources\Quotes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quoteNumber' => $this->quote_number,
            'customerName' => $this->whenLoaded('customer', fn () => $this->customer?->legal_name),
            'employeeName' => $this->whenLoaded('employee', fn () => $this->employee?->full_name),
            'appliedPlanName' => $this->whenLoaded('appliedPlan', fn () => $this->appliedPlan?->plan_name),
            'basePrice' => (float) $this->base_price,
            'total' => (float) $this->total,
            'status' => $this->status,
            'itemsCount' => $this->whenCounted('items'),
            'createdAt' => $this->created_at?->toISOString(),
        ];
    }
}
