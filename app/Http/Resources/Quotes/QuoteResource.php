<?php

namespace App\Http\Resources\Quotes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quoteNumber' => $this->quote_number,
            'customer' => $this->whenLoaded('customer', function () {
                return $this->customer ? [
                    'id' => $this->customer->id,
                    'legalName' => $this->customer->legal_name,
                    'tradeName' => $this->customer->trade_name,
                ] : null;
            }),
            'employee' => $this->whenLoaded('employee', function () {
                return $this->employee ? [
                    'id' => $this->employee->id,
                    'fullName' => $this->employee->full_name,
                ] : null;
            }),
            'appliedPlan' => $this->whenLoaded('appliedPlan', function () {
                return $this->appliedPlan ? [
                    'id' => $this->appliedPlan->id,
                    'planCode' => $this->appliedPlan->plan_code,
                    'planName' => $this->appliedPlan->plan_name,
                ] : null;
            }),
            'basePrice' => (float) $this->base_price,
            'discountedSubtotal' => (float) $this->discounted_subtotal,
            'includesVat' => (bool) $this->includes_vat,
            'loyaltyPointsUsed' => (int) $this->loyalty_points_used,
            'loyaltyPointsEarned' => (int) $this->loyalty_points_earned,
            'total' => (float) $this->total,
            'status' => $this->status,
            'items' => QuoteItemResource::collection($this->whenLoaded('items')),
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
    }
}
