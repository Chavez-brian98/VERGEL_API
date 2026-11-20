<?php

namespace App\Http\Resources\Portfolio;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPortfolioSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'customerId' => $this->id,
            'legalName' => $this->legal_name,
            'nrc' => $this->nrc,
            'currentPlanName' => $this->currentPlan?->plan_name,
            'cashBalance' => $this->wallet ? (float) $this->wallet->cash_balance : 0.0,
            'reservedSubscriptionBalance' => $this->wallet
                ? (float) $this->wallet->reserved_subscription_balance
                : 0.0,
            'loyaltyPoints' => $this->loyaltyPoint
                ? (int) $this->loyaltyPoint->points_balance
                : 0,
        ];
    }
}
