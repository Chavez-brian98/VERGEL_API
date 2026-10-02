<?php

namespace App\Http\Resources\Customers;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legalName' => $this->legal_name,
            'tradeName' => $this->trade_name,
            'taxId' => $this->tax_id,
            'nrc' => $this->nrc,
            'phone' => $this->phone,
            'status' => $this->status,
            'currentPlanName' => $this->whenLoaded('currentPlan', function () {
                return $this->currentPlan ? $this->currentPlan->plan_name : null;
            }),
            'cashBalance' => $this->whenLoaded('wallet', function () {
                return $this->wallet ? (float) $this->wallet->cash_balance : 0.00;
            }),
            'loyaltyPoints' => $this->whenLoaded('loyaltyPoint', function () {
                return $this->loyaltyPoint ? $this->loyaltyPoint->points_balance : 0;
            }),
            'createdAt' => $this->created_at ? $this->created_at->toIso8601String() : null,
        ];
    }
}